<?php
session_start();
include("database.php");

if (!isset($_SESSION['username'])) {
  header("Location: login.php");
  exit();
}

$customer = $_SESSION['username'];
$message = "";
$messageType = "";

// Get customer_id from username
$cust_stmt = $conn->prepare("SELECT customer_id, first_name, middle_name, last_name, email, phone_number FROM customer WHERE username = ?");
$cust_stmt->bind_param("s", $customer);
$cust_stmt->execute();
$cust_result = $cust_stmt->get_result();
$cust_row = $cust_result->fetch_assoc();
$customer_id = $cust_row['customer_id'];
$fullname = trim($cust_row['first_name'] . ' ' . $cust_row['middle_name'] . ' ' . $cust_row['last_name']);
$email = $cust_row['email'];
$phone = $cust_row['phone_number'];

// Cancel reservation
if (isset($_POST['cancel_reservation_id'])) {
  $reservation_id = $_POST['cancel_reservation_id'];
  $cancel_stmt = $conn->prepare("DELETE FROM reservation WHERE reservation_id = ? AND customer_id = ?");
  $cancel_stmt->bind_param("ii", $reservation_id, $customer_id);
  $cancel_stmt->execute();
  $message = "Reservation cancelled successfully!";
  $messageType = "success";
}

// Make reservation with food order and payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['table_id'])) {
  $table_id = $_POST['table_id'];
  $date = $_POST['reservation_date'];
  $time = $_POST['reservation_time'];
  $event_type = $_POST['event_type'];
  $total_hours = $_POST['total_hours'] ?? 2;
  $status = 'Pending';
  $payment_method = $_POST['payment_method'] ?? 'Cash on Pickup';
  $paypal_order_id = $_POST['paypal_order_id'] ?? null;
  
  // Get table info
  $table_stmt = $conn->prepare("SELECT capacity, price_per_hour FROM tables WHERE table_id = ?");
  $table_stmt->bind_param("i", $table_id);
  $table_stmt->execute();
  $table_result = $table_stmt->get_result();
  $table_row = $table_result->fetch_assoc();
  $guest_count = $table_row['capacity'];
  $price_per_hour = $table_row['price_per_hour'] ?? 0;
  $total_price = $price_per_hour * $total_hours;
  $table_stmt->close();

  // Calculate food order total
  $food_total = 0;
  $ordered_items = [];
  if (isset($_POST['food_items']) && is_array($_POST['food_items'])) {
    foreach ($_POST['food_items'] as $product_id => $quantity) {
      if ($quantity > 0) {
        $product_stmt = $conn->prepare("SELECT price FROM product WHERE product_id = ?");
        $product_stmt->bind_param("i", $product_id);
        $product_stmt->execute();
        $product_result = $product_stmt->get_result();
        $product = $product_result->fetch_assoc();
        $food_total += $product['price'] * $quantity;
        $ordered_items[$product_id] = $quantity;
        $product_stmt->close();
      }
    }
  }

  $grand_total = $total_price + $food_total;

  // Start transaction
  $conn->begin_transaction();
  
  try {
    // Insert reservation
    $stmt = $conn->prepare("INSERT INTO reservation (customer_id, table_id, reservation_date, reservation_time, event_type, status, total_hours, total_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissssid", $customer_id, $table_id, $date, $time, $event_type, $status, $total_hours, $grand_total);
    $stmt->execute();
    $reservation_id = $conn->insert_id;
    $stmt->close();
    
    $order_id = null;
    
    // Insert food orders if any
    if (!empty($ordered_items)) {
      $order_date = date('Y-m-d');
      $order_time = date('H:i:s');
      
      // Create order
      $order_stmt = $conn->prepare("INSERT INTO orders (customer_id, order_date, order_time, total_amount) VALUES (?, ?, ?, ?)");
      $order_stmt->bind_param("issd", $customer_id, $order_date, $order_time, $food_total);
      $order_stmt->execute();
      $order_id = $conn->insert_id;
      $order_stmt->close();
      
      // Insert order details into order_item table
      foreach ($ordered_items as $product_id => $quantity) {
        $product_stmt = $conn->prepare("SELECT price FROM product WHERE product_id = ?");
        $product_stmt->bind_param("i", $product_id);
        $product_stmt->execute();
        $product_result = $product_stmt->get_result();
        $product = $product_result->fetch_assoc();
        $price = $product['price'];
        $total_price_item = $price * $quantity;
        $product_stmt->close();
        
        $detail_stmt = $conn->prepare("INSERT INTO order_item (order_id, product_id, quantity, total_price) VALUES (?, ?, ?, ?)");
        $detail_stmt->bind_param("iiid", $order_id, $product_id, $quantity, $total_price_item);
        $detail_stmt->execute();
        $detail_stmt->close();
      }
      
      // Link order to reservation (check if order_id column exists)
      $check_column = $conn->query("SHOW COLUMNS FROM reservation LIKE 'order_id'");
      if ($check_column && $check_column->num_rows > 0) {
        $update_res = $conn->prepare("UPDATE reservation SET order_id = ? WHERE reservation_id = ?");
        if ($update_res) {
          $update_res->bind_param("ii", $order_id, $reservation_id);
          $update_res->execute();
          $update_res->close();
        }
      }
    }
    
    // Insert payment record
    $payment_date = date('Y-m-d');
    $payment_time = date('H:i:s');
    // If PayPal and has order ID, mark as Paid, otherwise Pending
    $payment_status = ($payment_method === 'PayPal' && $paypal_order_id) ? 'Paid' : 'Pending';
    
    $payment_stmt = $conn->prepare("INSERT INTO payment (order_id, payment_date, payment_time, payment_method, payment_status, total_amount) VALUES (?, ?, ?, ?, ?, ?)");
    $payment_stmt->bind_param("issssd", $order_id, $payment_date, $payment_time, $payment_method, $payment_status, $grand_total);
    $payment_stmt->execute();
    $payment_id = $conn->insert_id;
    $payment_stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    $message = "Reservation submitted successfully! Payment ID: #" . $payment_id . ". ";
    if ($total_price > 0) {
      $message .= "Room Cost: ₱" . number_format($total_price, 2) . ". ";
    }
    if ($food_total > 0) {
      $message .= "Food Order: ₱" . number_format($food_total, 2) . ". ";
    }
    $message .= "Total: ₱" . number_format($grand_total, 2) . ". Payment Method: " . $payment_method . ". ";
    if ($payment_status === 'Paid') {
      $message .= "Payment confirmed via PayPal! ";
    }
    $message .= "Our staff will confirm your booking shortly.";
    $messageType = "success";
    
  } catch (Exception $e) {
    $conn->rollback();
    $message = "Error: " . $e->getMessage();
    $messageType = "error";
  }
}

// Fetch products by category
$products_query = "SELECT p.product_id, p.product_name, p.price, p.image, c.category_name 
                   FROM product p 
                   LEFT JOIN category c ON p.category_id = c.category_id 
                   WHERE p.stock_quantity > 0
                   ORDER BY c.category_name, p.product_name";
$products_result = $conn->query($products_query);
$products_by_category = [];
while ($product = $products_result->fetch_assoc()) {
  $category = $product['category_name'] ?? 'Uncategorized';
  $products_by_category[$category][] = $product;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Reserve a Table | Kyla's Bistro</title>
  <!-- PayPal SDK -->
  <script src="https://www.paypal.com/sdk/js?client-id=Ad0jyZPG1bHR0wscGdYQqlX7AKF-Xr_F6JCKcJere-ZQRhC0PoDeH5_4InO2DrFV17eMD2byS6tPtvhp&currency=USD"></script>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #f9f6f2;
      min-height: 100vh;
      padding: 20px;
      color: #2c1810;
    }

    .container {
      max-width: 1000px;
      margin: 0 auto;
    }

    .back-link {
      display: inline-block;
      padding: 10px 20px;
      background-color: #8b4513;
      color: white;
      text-decoration: none;
      border-radius: 6px;
      margin-bottom: 20px;
      transition: all 0.3s ease;
      box-shadow: 0 2px 5px rgba(139, 69, 19, 0.2);
      font-weight: 600;
    }

    h1 {
      text-align: center;
      color: #8b4513;
      margin-bottom: 10px;
      font-size: 2.2rem;
      font-weight: 600;
    }

    .subtitle {
      text-align: center;
      color: #555;
      margin-bottom: 30px;
      font-size: 1rem;
    }

    .message {
      padding: 12px;
      border-radius: 8px;
      margin-bottom: 20px;
      text-align: center;
      font-weight: 600;
    }

    .message.success {
      background-color: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }

    .message.error {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }

    .card {
      background: white;
      border-radius: 12px;
      padding: 25px;
      margin-bottom: 25px;
      box-shadow: 0 4px 12px rgba(44, 24, 16, 0.1);
      border: 1px solid #f5e6d3;
    }

    .card h2 {
      color: #8b4513;
      margin-bottom: 20px;
      padding-bottom: 12px;
      border-bottom: 3px solid #d4a574;
      font-size: 1.5rem;
      font-weight: 600;
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-group label {
      display: block;
      font-weight: 600;
      color: #2c1810;
      margin-bottom: 8px;
      font-size: 0.95rem;
    }

    .form-group input[type="date"],
    .form-group input[type="time"],
    .form-group input[type="number"],
    .form-group select {
      width: 100%;
      padding: 10px 12px;
      border: 2px solid #d4a574;
      border-radius: 8px;
      font-size: 14px;
      transition: all 0.3s ease;
      background-color: #f9f9f9;
    }

    .form-group input:focus,
    .form-group select:focus {
      outline: none;
      border-color: #8b4513;
      box-shadow: 0 0 0 3px rgba(139, 69, 19, 0.1);
      background-color: white;
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
    }

    .event-types {
      display: flex;
      gap: 10px;
      margin-bottom: 15px;
    }

    .event-option {
      flex: 1;
    }

    .event-option input[type="radio"] {
      display: none;
    }

    .event-label {
      display: block;
      padding: 15px 12px;
      background: #f9f6f2;
      border: 2px solid #d4a574;
      border-radius: 8px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s ease;
      font-weight: 500;
      color: #2c1810;
    }

    .event-option input[type="radio"]:checked + .event-label {
      background: #8b4513;
      border-color: #8b4513;
      color: white;
      font-weight: 600;
      box-shadow: 0 4px 10px rgba(139, 69, 19, 0.3);
    }

    .event-label:hover {
      border-color: #000000ff;
    }

    .table-list {
      margin: 15px 0;
    }

    .table-category h4 {
      margin: 15px 0 10px 0;
      color: #8b4513;
      font-weight: 600;
    }

    .table-item {
      display: flex;
      align-items: center;
      padding: 12px;
      background: white;
      border: 2px solid #f5e6d3;
      border-radius: 8px;
      margin-bottom: 10px;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .table-item:hover {
      background: #f9f6f2;
      border-color: #d4a574;
      transform: translateX(5px);
    }

    .table-item input[type="radio"]:checked + .table-info {
      font-weight: 600;
    }

    .table-item input[type="radio"] {
      margin-right: 12px;
      width: 18px;
      height: 18px;
      accent-color: #8b4513;
    }

    .table-info {
      flex: 1;
    }

    .table-name {
      font-weight: bold;
      color: #2c1810;
    }

    .table-desc {
      font-size: 12px;
      color: #555;
    }

    .table-price {
      font-weight: bold;
      color: #8b4513;
      margin-left: 10px;
    }

    /* Food Menu Styles */
    .food-menu {
      margin-top: 20px;
      padding: 20px;
      background: #f9f6f2;
      border-radius: 10px;
      border: 2px solid #d4a574;
    }

    .food-menu h3 {
      color: #8b4513;
      margin-bottom: 15px;
      font-size: 1.3rem;
    }

    .menu-category {
      margin-bottom: 20px;
    }

    .menu-category h4 {
      color: #8b4513;
      margin-bottom: 10px;
      font-size: 1.1rem;
      border-bottom: 2px solid #d4a574;
      padding-bottom: 5px;
    }

    .food-items {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 15px;
    }

    .food-item {
      background: white;
      border: 2px solid #f5e6d3;
      border-radius: 8px;
      padding: 12px;
      transition: all 0.3s ease;
    }

    .food-item:hover {
      border-color: #d4a574;
      box-shadow: 0 2px 8px rgba(139, 69, 19, 0.15);
    }

    .food-item img {
      width: 100%;
      height: 120px;
      object-fit: cover;
      border-radius: 6px;
      margin-bottom: 8px;
    }

    .food-name {
      font-weight: 600;
      color: #2c1810;
      margin-bottom: 5px;
      font-size: 0.95rem;
    }

    .food-price {
      color: #8b4513;
      font-weight: 700;
      margin-bottom: 8px;
    }

    .quantity-control {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .quantity-control button {
      width: 30px;
      height: 30px;
      border: 2px solid #d4a574;
      background: white;
      border-radius: 5px;
      cursor: pointer;
      font-weight: 600;
      color: #8b4513;
      transition: all 0.2s;
    }

    .quantity-control button:hover {
      background: #8b4513;
      color: white;
    }

    .quantity-control input {
      width: 50px;
      text-align: center;
      border: 2px solid #d4a574;
      border-radius: 5px;
      padding: 5px;
    }

    /* Payment Section Styles - Updated to match checkout.php */
    .payment-section {
      margin-top: 20px;
      padding: 20px;
      background: white;
      border-radius: 10px;
      border: 2px solid #d4a574;
    }

    .payment-section h3 {
      color: #8b4513;
      margin-bottom: 15px;
      font-size: 1.3rem;
    }

    .payment-options {
      display: grid;
      gap: 15px;
      margin-bottom: 15px;
    }

    .payment-option {
      position: relative;
    }

    .payment-option input[type="radio"] {
      display: none;
    }

    .payment-label {
      display: flex;
      align-items: center;
      gap: 15px;
      padding: 16px 18px;
      background: #f9f6f2;
      border: 2px solid #d4a574;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .payment-option input[type="radio"]:checked + .payment-label {
      background: #f5e6d3;
      border-color: #8b4513;
      box-shadow: 0 4px 12px rgba(139, 69, 19, 0.15);
    }

    .payment-label:hover {
      border-color: #8b4513;
    }

    .payment-icon {
      font-size: 28px;
      min-width: 40px;
      text-align: center;
    }

    .payment-text {
      flex: 1;
    }

    .payment-name {
      font-weight: 700;
      font-size: 16px;
      color: #2c1810;
      margin-bottom: 3px;
    }

    .payment-desc {
      font-size: 13px;
      color: #666;
    }

    /* PayPal Container Styles */
    .paypal-container {
      display: none;
      margin-top: 15px;
      padding: 20px;
      background: #fff;
      border: 2px solid #0070ba;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0, 112, 186, 0.1);
    }

    .paypal-container.active {
      display: block;
    }

    .paypal-header {
      text-align: center;
      margin-bottom: 15px;
      padding-bottom: 15px;
      border-bottom: 2px solid #f0f0f0;
    }

    .paypal-header h3 {
      color: #0070ba;
      font-size: 18px;
      margin: 0 0 5px 0;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }

    .paypal-header p {
      color: #666;
      font-size: 14px;
      margin: 0;
    }


    #paypal-button-container {
      min-height: 50px;
    }

    .payment-note {
      background: #e8f4f8;
      padding: 12px;
      border-radius: 6px;
      margin-top: 15px;
      font-size: 13px;
      color: #555;
      border-left: 4px solid #17a2b8;
    }

    .price-display {
      background: #f5e6d3;
      padding: 15px;
      border-radius: 8px;
      border: 2px solid #d4a574;
      margin: 15px 0;
      box-shadow: 0 2px 8px rgba(139, 69, 19, 0.15);
    }

    .price-breakdown {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .price-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .price-row.total {
      font-size: 1.3rem;
      font-weight: 700;
      color: #8b4513;
      padding-top: 8px;
      border-top: 2px solid #d4a574;
      margin-top: 8px;
    }

    .submit-btn {
      width: 100%;
      padding: 14px;
      background: #8b4513;
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(139, 69, 19, 0.3);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .submit-btn:hover:not(:disabled) {
      background: #6d3410;
      transform: translateY(-2px);
    }

    .submit-btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    .reservations-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }

    .reservations-table th {
      background: #2c1810;
      color: white;
      font-weight: 600;
    }

    .reservations-table th,
    .reservations-table td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid #f5e6d3;
    }

    .reservations-table tbody tr {
      transition: background 0.2s ease;
    }

    .reservations-table tbody tr:hover {
      background: #f9f6f2;
    }

    .status-badge {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 3px;
      font-size: 12px;
      font-weight: bold;
    }

    .status-pending {
      background-color: #fff3cd;
      color: #856404;
    }

    .status-confirmed {
      background-color: #d4edda;
      color: #155724;
    }

    .status-paid {
      background-color: #d1ecf1;
      color: #0c5460;
    }

    .cancel-btn {
      padding: 6px 12px;
      background-color: #dc3545;
      color: white;
      border: none;
      border-radius: 3px;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    .cancel-btn:hover {
      background-color: #c82333;
    }

    .no-reservations {
      text-align: center;
      padding: 30px;
      color: #999;
    }

    #durationField {
      display: none;
    }

    @media (max-width: 768px) {
      .event-types,
      .form-row {
        grid-template-columns: 1fr;
      }

      .food-items {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <a href="index.php" class="back-link">← Back to Home</a>

    <h1>Reserve Your Table</h1>
    <p class="subtitle">Choose your perfect dining experience at Kyla's Bistro</p>

    <?php if ($message): ?>
      <div class="message <?= $messageType ?>">
        <?= $message ?>
      </div>
    <?php endif; ?>

    <div class="card">
      <h2>Make a Reservation</h2>
      <form method="POST" id="reservationForm">
        <input type="hidden" name="paypal_order_id" id="paypal_order_id" value="">
        
        <!-- Event Type -->
        <div class="form-group">
          <label>Select Dining Type</label>
          <div class="event-types">
            <div class="event-option">
              <input type="radio" id="regular" name="event_type" value="Regular Dining" checked onchange="handleEventChange()">
              <label for="regular" class="event-label">🍽️ Regular Dining</label>
            </div>
            <div class="event-option">
              <input type="radio" id="birthday" name="event_type" value="Birthday Party" onchange="handleEventChange()">
              <label for="birthday" class="event-label">🎂 Birthday Party</label>
            </div>
            <div class="event-option">
              <input type="radio" id="meeting" name="event_type" value="Meeting" onchange="handleEventChange()">
              <label for="meeting" class="event-label">💼 Meeting</label>
            </div>
          </div>
        </div>

        <!-- Date & Time -->
        <div class="form-row">
          <div class="form-group">
            <label>Date</label>
            <input type="date" name="reservation_date" required min="<?= date('Y-m-d') ?>" />
          </div>
          <div class="form-group">
            <label>Time</label>
            <input type="time" name="reservation_time" required />
          </div>
        </div>

        <!-- Duration Field (only for Birthday & Meeting) -->
        <div class="form-group" id="durationField">
          <label>Duration (Hours)</label>
          <input type="number" name="total_hours" id="total_hours" min="1" max="12" value="2" onchange="updatePrice()" />
          <small style="color: #666;">Specify how many hours you need the room</small>
        </div>

        <!-- Select Table -->
        <div class="form-group">
          <label>Select Table/Room</label>
          <div class="table-list" id="tablesList">
            <?php
            $tables_query = "SELECT table_id, table_number, capacity, table_type, description, price_per_hour 
                             FROM tables 
                             WHERE status = 'Available' 
                             ORDER BY table_type, table_number";
            
            $tables_result = $conn->query($tables_query);
            $grouped_tables = [];
            while ($table = $tables_result->fetch_assoc()) {
              $type = $table['table_type'];
              $grouped_tables[$type][] = $table;
            }
            
            foreach ($grouped_tables as $type => $tables):
            ?>
              <div class="table-category" data-table-type="<?= htmlspecialchars($type) ?>">
                <h4><?= htmlspecialchars($type) ?></h4>
                <?php foreach ($tables as $table): ?>
                  <label class="table-item">
                    <input type="radio" name="table_id" value="<?= $table['table_id'] ?>" 
                           required data-price="<?= $table['price_per_hour'] ?>" onchange="updatePrice()">
                    <div class="table-info">
                      <div class="table-name">Table <?= htmlspecialchars($table['table_number']) ?></div>
                      <div class="table-desc">
                        <?php if ($table['description']): ?>
                          <?= htmlspecialchars($table['description']) ?> • 
                        <?php endif; ?>
                        Capacity: <?= $table['capacity'] ?> guests
                      </div>
                    </div>
                    <div class="table-price">
                      <?= $table['price_per_hour'] > 0 ? '₱' . number_format($table['price_per_hour'], 2) . '/hr' : 'Free' ?>
                    </div>
                  </label>
                <?php endforeach; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Food Menu -->
        <div class="food-menu">
          <h3>🍴 Order Food (Optional)</h3>
          <p style="color: #666; margin-bottom: 15px;">Add food items to your reservation</p>
          
          <?php foreach ($products_by_category as $category => $products): ?>
            <div class="menu-category">
              <h4><?= htmlspecialchars($category) ?></h4>
              <div class="food-items">
                <?php foreach ($products as $product): ?>
                  <div class="food-item">
                    <img src="uploads/<?= htmlspecialchars($product['image']) ?>" 
                         alt="<?= htmlspecialchars($product['product_name']) ?>"
                         onerror="this.src='uploads/placeholder.jpg'">
                    <div class="food-name"><?= htmlspecialchars($product['product_name']) ?></div>
                    <div class="food-price">₱<?= number_format($product['price'], 2) ?></div>
                    <div class="quantity-control">
                      <button type="button" onclick="decreaseQuantity(<?= $product['product_id'] ?>)">-</button>
                      <input type="number" 
                             name="food_items[<?= $product['product_id'] ?>]" 
                             id="qty_<?= $product['product_id'] ?>" 
                             value="0" 
                             min="0" 
                             data-price="<?= $product['price'] ?>"
                             onchange="updatePrice()" 
                             readonly>
                      <button type="button" onclick="increaseQuantity(<?= $product['product_id'] ?>)">+</button>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Payment Section -->
        <div class="payment-section" id="paymentSection" style="display: none;">
          <h3>💳 Payment Method</h3>
          
          <div class="payment-options">
            <!-- Cash on Pickup Option -->
            <div class="payment-option">
              <input type="radio" id="cop" name="payment_method" value="Cash on Pickup" checked>
              <label for="cop" class="payment-label">
                <span class="payment-icon">💵</span>
                <div class="payment-text">
                  <div class="payment-name">Cash on Pickup</div>
                  <div class="payment-desc">Pay with cash when you arrive at the restaurant</div>
                </div>
              </label>
            </div>

            <!-- PayPal Option -->
            <div class="payment-option">
              <input type="radio" id="paypal_radio" name="payment_method" value="PayPal">
              <label for="paypal_radio" class="payment-label">
                <span class="payment-icon">💳</span>
                <div class="payment-text">
                  <div class="payment-name">PayPal</div>
                  <div class="payment-desc">Pay securely online with PayPal or credit/debit card</div>
                </div>
              </label>
            </div>
          </div>

          <!-- PayPal Payment Container (Hidden by default) -->
          <div class="paypal-container" id="paypalContainer">
            <div class="paypal-header">
              <h3>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="#0070ba">
                  <path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944 3.72a.77.77 0 0 1 .76-.633h8.625c2.23 0 3.716.4 4.547 1.223.814.804 1.188 2.003 1.11 3.56-.078 1.634-.502 2.993-1.265 4.046-.74 1.027-1.79 1.778-3.12 2.23-1.296.44-2.86.66-4.646.66H8.97a.641.641 0 0 0-.633.552l-1.261 6.02zm13.9-16.91c-.83-1.03-2.4-1.55-4.665-1.55H7.686a1.537 1.537 0 0 0-1.518 1.267L2.964 20.916a1.283 1.283 0 0 0 1.267 1.484h4.605a1.283 1.283 0 0 0 1.267-1.054l1.093-5.22h1.838c3.996 0 7.084-1.639 8.425-4.485.635-1.346.935-2.95.74-4.78-.195-1.83-.975-3.254-2.223-4.334z"/>
                </svg>
                Complete Payment with PayPal
              </h3>
              <p>You will be redirected to PayPal to complete your payment securely</p>
            </div>

            <div id="paypal-button-container"></div>
          </div>

          <div class="payment-note">
            ℹ️ <strong>Note:</strong> For Cash on Pickup, you'll pay when you arrive at the restaurant. For PayPal, complete the secure payment now to confirm your order instantly.
          </div>
        </div>

        <!-- Price Display -->
        <div id="priceDisplay" class="price-display">
          <div class="price-breakdown">
            <div class="price-row">
              <span>Room/Table Cost:</span>
              <span id="roomPrice">₱0.00</span>
            </div>
            <div class="price-row">
              <span>Food Order:</span>
              <span id="foodPrice">₱0.00</span>
            </div>
            <div class="price-row total">
              <span>Total Amount to Pay:</span>
              <span id="grandTotal">₱0.00</span>
            </div>
          </div>
        </div>

        <button type="submit" class="submit-btn" id="submitBtn">Confirm Reservation</button>
      </form>
    </div>

    <!-- My Reservations -->
    <div class="card">
      <h2>My Reservations</h2>
      <?php
      $reservations_query = $conn->prepare("SELECT r.reservation_id, r.reservation_date, r.reservation_time, r.event_type, r.status, r.total_hours, r.total_price, t.table_number, t.table_type,
                                             p.payment_id, p.payment_method, p.payment_status
                                             FROM reservation r 
                                             JOIN tables t ON r.table_id = t.table_id 
                                             LEFT JOIN payment p ON r.order_id = p.order_id
                                             WHERE r.customer_id = ? 
                                             ORDER BY r.reservation_date DESC, r.reservation_time DESC");
      $reservations_query->bind_param("i", $customer_id);
      $reservations_query->execute();
      $reservations_result = $reservations_query->get_result();

      if ($reservations_result->num_rows > 0):
      ?>
        <table class="reservations-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Time</th>
              <th>Table/Room</th>
              <th>Event Type</th>
              <th>Duration</th>
              <th>Total Price</th>
              <th>Payment Method</th>
              <th>Payment Status</th>
              <th>Reservation Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($res = $reservations_result->fetch_assoc()): ?>
              <tr>
                <td><?= date('M d, Y', strtotime($res['reservation_date'])) ?></td>
                <td><?= date('h:i A', strtotime($res['reservation_time'])) ?></td>
                <td><?= htmlspecialchars($res['table_number']) ?></td>
                <td><?= htmlspecialchars($res['event_type']) ?></td>
                <td><?= $res['total_hours'] ?> hr<?= $res['total_hours'] > 1 ? 's' : '' ?></td>
                <td>
                  <?= $res['total_price'] > 0 ? '₱' . number_format($res['total_price'], 2) : 'Free' ?>
                </td>
                <td><?= htmlspecialchars($res['payment_method'] ?? 'N/A') ?></td>
                <td>
                  <span class="status-badge status-<?= strtolower($res['payment_status'] ?? 'pending') ?>">
                    <?= htmlspecialchars($res['payment_status'] ?? 'N/A') ?>
                  </span>
                </td>
                <td>
                  <span class="status-badge status-<?= strtolower($res['status']) ?>">
                    <?= htmlspecialchars($res['status']) ?>
                  </span>
                </td>
                <td>
                  <?php if ($res['status'] === 'Pending'): ?>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Cancel this reservation?');">
                      <input type="hidden" name="cancel_reservation_id" value="<?= $res['reservation_id'] ?>">
                      <button type="submit" class="cancel-btn">Cancel</button>
                    </form>
                  <?php else: ?>
                    -
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="no-reservations">
          <p>You don't have any reservations yet.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    let totalAmountUSD = 0;
    let paypalButtonRendered = false;

    function handleEventChange() {
      const selectedEvent = document.querySelector('input[name="event_type"]:checked').value;
      const durationField = document.getElementById('durationField');
      const categories = document.querySelectorAll('.table-category');
      
      // Show/hide duration field based on event type
      if (selectedEvent === 'Birthday Party' || selectedEvent === 'Meeting') {
        durationField.style.display = 'block';
      } else {
        durationField.style.display = 'none';
      }
      
      // Filter tables based on event type
      categories.forEach(category => {
        const tableType = category.getAttribute('data-table-type');
        
        if (selectedEvent === 'Regular Dining') {
          category.style.display = tableType.includes('Regular') ? 'block' : 'none';
        } else if (selectedEvent === 'Birthday Party') {
          category.style.display = tableType === 'Birthday Party Room' ? 'block' : 'none';
        } else if (selectedEvent === 'Meeting') {
          category.style.display = tableType === 'Meeting Room' ? 'block' : 'none';
        }
      });

      // Reset table selection and price
      document.querySelectorAll('input[name="table_id"]').forEach(radio => radio.checked = false);
      updatePrice();
    }

    function increaseQuantity(productId) {
      const input = document.getElementById('qty_' + productId);
      input.value = parseInt(input.value) + 1;
      updatePrice();
    }

    function decreaseQuantity(productId) {
      const input = document.getElementById('qty_' + productId);
      if (parseInt(input.value) > 0) {
        input.value = parseInt(input.value) - 1;
        updatePrice();
      }
    }

    function updatePrice() {
      const selectedTable = document.querySelector('input[name="table_id"]:checked');
      const selectedEvent = document.querySelector('input[name="event_type"]:checked').value;
      const totalHours = parseInt(document.getElementById('total_hours').value) || 2;
      const priceDisplay = document.getElementById('priceDisplay');
      const roomPriceElement = document.getElementById('roomPrice');
      const foodPriceElement = document.getElementById('foodPrice');
      const grandTotalElement = document.getElementById('grandTotal');
      const paymentSection = document.getElementById('paymentSection');
      
      let roomPrice = 0;
      let foodPrice = 0;

      // Calculate room price
      if (selectedTable) {
        const pricePerHour = parseFloat(selectedTable.getAttribute('data-price')) || 0;
        
        if (selectedEvent === 'Birthday Party' || selectedEvent === 'Meeting') {
          roomPrice = pricePerHour * totalHours;
        } else {
          roomPrice = 0; // Regular dining is free
        }
      }

      // Calculate food price
      const foodInputs = document.querySelectorAll('input[name^="food_items"]');
      foodInputs.forEach(input => {
        const quantity = parseInt(input.value) || 0;
        const price = parseFloat(input.getAttribute('data-price')) || 0;
        foodPrice += quantity * price;
      });

      const grandTotal = roomPrice + foodPrice;

      // Show/hide payment section based on whether food is ordered or room has cost
      if (foodPrice > 0 || roomPrice > 0) {
        paymentSection.style.display = 'block';
      } else {
        paymentSection.style.display = 'none';
      }

      // Calculate USD amount (approximate conversion rate)
      totalAmountUSD = grandTotal / 56;

      // Update display
      roomPriceElement.textContent = '₱' + roomPrice.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
      foodPriceElement.textContent = '₱' + foodPrice.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
      grandTotalElement.textContent = '₱' + grandTotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');

      // Update PayPal amount display
      document.getElementById('paypalAmountUSD').textContent = + totalAmountUSD.toFixed(2) + USD;
      document.getElementById('paypalAmountPHP').textContent = '(₱' + grandTotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',') + ')';

      // Reset PayPal button if amount changed
      if (paypalButtonRendered) {
        paypalButtonRendered = false;
        document.getElementById('paypal-button-container').innerHTML = '';
      }
    }

    // Handle payment method change
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
      radio.addEventListener('change', function() {
        const paypalContainer = document.getElementById('paypalContainer');
        const submitBtn = document.getElementById('submitBtn');
        
        if (this.value === 'PayPal') {
          // Show PayPal container
          paypalContainer.classList.add('active');
          submitBtn.style.display = 'none';
          
          // Render PayPal button only once
          if (!paypalButtonRendered && totalAmountUSD > 0) {
            renderPayPalButtons();
            paypalButtonRendered = true;
          }
        } else {
          // Hide PayPal container, show regular submit button
          paypalContainer.classList.remove('active');
          submitBtn.style.display = 'block';
        }
      });
    });

    function renderPayPalButtons() {
      paypal.Buttons({
        style: {
          layout: 'vertical',
          color: 'gold',
          shape: 'rect',
          label: 'paypal',
          height: 45
        },
        
        createOrder: function(data, actions) {
          return actions.order.create({
            purchase_units: [{
              amount: {
                value: totalAmountUSD.toFixed(2),
                currency_code: 'USD'
              },
              description: 'Kyla\'s Bistro - Reservation & Food Order'
            }]
          });
        },
        
        onApprove: function(data, actions) {
          return actions.order.capture().then(function(details) {
            // Store PayPal order ID
            document.getElementById('paypal_order_id').value = data.orderID;
            
            // Show success message
            alert('✅ Payment successful! Processing your reservation...');
            
            // Submit the form
            document.getElementById('reservationForm').submit();
          });
        },
        
        onError: function(err) {
          console.error('PayPal Error:', err);
          alert('❌ Payment failed. Please try again or select Cash on Pickup.');
        },
        
        onCancel: function(data) {
          alert('Payment was cancelled. You can try again or choose Cash on Pickup.');
        }
      }).render('#paypal-button-container');
    }

    // Handle form submission for Cash on Pickup
    document.getElementById('reservationForm').addEventListener('submit', function(e) {
      const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
      
      if (!paymentMethod) {
        e.preventDefault();
        alert('Please select a payment method');
        return false;
      }
      
      // Prevent submission if PayPal is selected but not completed
      if (paymentMethod.value === 'PayPal' && !document.getElementById('paypal_order_id').value) {
        e.preventDefault();
        alert('Please complete the PayPal payment by clicking the PayPal button above.');
        return false;
      }
      
      // For Cash on Pickup
      if (paymentMethod.value === 'Cash on Pickup') {
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.textContent = 'Processing Reservation...';
      }
    });

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
      handleEventChange();
      updatePrice();
    });
  </script>
</body>
</html>
<?php $conn->close(); ?>