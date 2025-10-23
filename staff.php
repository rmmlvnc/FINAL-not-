<?php
session_start();
include 'database.php';

if (!isset($_SESSION['staff_id'])) {
  header("Location: staff_login.php");
  exit();
}

$staff_id = $_SESSION['staff_id'];
$message = "";
$messageType = "";

// Update reservation status
if (isset($_POST['update_reservation_status'])) {
  $reservation_id = $_POST['reservation_id'];
  $new_status = $_POST['reservation_status'];
  
  $update_stmt = $conn->prepare("UPDATE reservation SET status = ? WHERE reservation_id = ?");
  $update_stmt->bind_param("si", $new_status, $reservation_id);
  
  if ($update_stmt->execute()) {
    $message = "Reservation status updated to " . $new_status . " successfully!";
    $messageType = "success";
  } else {
    $message = "Error updating reservation: " . $conn->error;
    $messageType = "error";
  }
  $update_stmt->close();
}

// Fetch staff info
$staff_stmt = $conn->prepare("SELECT * FROM staff WHERE staff_id = ?");
$staff_stmt->bind_param("i", $staff_id);
$staff_stmt->execute();
$staff_result = $staff_stmt->get_result();
$staff = $staff_result->fetch_assoc();
$staff_stmt->close();

// Fetch payment records
$payment_result = $conn->query("
  SELECT 
    p.payment_id, 
    p.order_id, 
    p.payment_method, 
    p.payment_status, 
    p.payment_date,
    o.total_amount,
    CONCAT(c.first_name, ' ', c.last_name) as customer_name
  FROM payment p
  JOIN orders o ON p.order_id = o.order_id
  JOIN customer c ON o.customer_id = c.customer_id
  ORDER BY p.payment_date DESC
");

// Fetch reservations - JOIN with customer table
$res_stmt = $conn->prepare("
  SELECT 
    r.*,
    t.table_number,
    t.capacity,
    t.table_type,
    CONCAT(c.first_name, ' ', c.last_name) as customer_name,
    c.phone_number
  FROM reservation r
  LEFT JOIN tables t ON r.table_id = t.table_id
  LEFT JOIN customer c ON r.customer_id = c.customer_id
  ORDER BY r.reservation_date DESC, r.reservation_time DESC
");
$res_stmt->execute();
$res_result = $res_stmt->get_result();
$res_stmt->close();

// Fetch customer orders
$orders_stmt = $conn->prepare("
  SELECT 
    o.order_id,
    o.customer_id,
    o.order_date,
    o.order_time,
    o.total_amount,
    c.first_name,
    c.last_name,
    c.phone_number
  FROM `orders` o
  LEFT JOIN customer c ON o.customer_id = c.customer_id
  ORDER BY o.order_date DESC, o.order_time DESC
");

$orders_result = null;
$prepare_error = null;

if ($orders_stmt) {
  $orders_stmt->execute();
  $orders_result = $orders_stmt->get_result();
  $orders_stmt->close();
} else {
  $prepare_error = $conn->error;
}

// Fetch products with category names
$product_stmt = $conn->prepare("
  SELECT 
    p.*,
    c.category_name
  FROM product p
  LEFT JOIN category c ON p.category_id = c.category_id
  ORDER BY p.product_name
");
$product_stmt->execute();
$product_result = $product_stmt->get_result();
$product_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Staff Dashboard - Kyla's Bistro</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    
    body { 
      font-family: 'Inter', 'Segoe UI', sans-serif; 
      background: lightslategrey;
      min-height: 100vh;
      padding: 20px;
    }
    
    .container {
      max-width: 1400px;
      margin: 0 auto;
    }
    
    .header { 
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      padding: 25px 35px;
      border-radius: 20px;
      display: flex; 
      justify-content: space-between; 
      align-items: center; 
      margin-bottom: 30px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    }
    
    .header h1 { 
      font-size: 28px;
      background: black;
      background-clip: text;
    }
    
    .btn { 
      padding: 10px 20px;
      background: #dc3545;
      color: white;
      border: none;
      border-radius: 10px;
      text-decoration: none;
      cursor: pointer;
      font-weight: 500;
      transition: all 0.3s ease;
      display: inline-block;
    }

    .btn:hover {
      background: #c82333;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
    }

    .btn-edit { 
      background: #28a745;
      padding: 8px 16px;
      font-size: 14px;
    }

    .btn-edit:hover {
      background: #218838;
    }
    
    .btn-view { 
      background: #007bff;
      padding: 8px 16px;
      font-size: 14px;
    }

    .btn-view:hover {
      background: #0056b3;
    }

    .btn-update {
      background: #17a2b8;
      padding: 6px 12px;
      font-size: 13px;
    }

    .btn-update:hover {
      background: #138496;
    }

    .message {
      padding: 15px 20px;
      border-radius: 12px;
      margin-bottom: 20px;
      font-weight: 500;
      animation: slideDown 0.3s ease;
    }

    .message.success {
      background: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }

    .message.error {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }

    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    .info-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      padding: 25px 35px;
      border-radius: 20px;
      margin-bottom: 30px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    }
    
    .info-card h2 {
      font-size: 20px;
      margin-bottom: 20px;
      color: black;
      padding-left: 15px;
    }
    
    .info-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 15px;
    }
    
    .info-item {
      background: #f8f9ff;
      padding: 15px;
      border-radius: 12px;
    }
    
    .info-item strong {
      display: block;
      color: black;
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 5px;
    }
    
    .info-item span {
      color: #333;
      font-size: 16px;
    }
    
    .section { 
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      padding: 30px;
      border-radius: 20px;
      margin-bottom: 30px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    }
    
    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
      padding-bottom: 15px;
      border-bottom: 2px solid #f0f0f0;
    }
    
    .section-header h2 {
      font-size: 22px;
      color: #333;
    }
    
    table { 
      width: 100%;
      border-collapse: separate;
      border-spacing: 0 10px;
    }
    
    th { 
      padding: 12px 15px;
      text-align: left;
      font-weight: 600;
      font-size: 13px;
      color: #667eea;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      background: transparent;
    }
    
    td { 
      padding: 15px;
      background: #f8f9ff;
      border-top: 1px solid #e8ebf7;
      border-bottom: 1px solid #e8ebf7;
    }
    
    td:first-child {
      border-left: 1px solid #e8ebf7;
      border-radius: 10px 0 0 10px;
    }
    
    td:last-child {
      border-right: 1px solid #e8ebf7;
      border-radius: 0 10px 10px 0;
    }
    
    .action-buttons { 
      display: flex;
      gap: 8px;
    }
    
    .product-img { 
      width: 70px;
      height: 70px;
      object-fit: cover;
      border-radius: 12px;
      border: 2px solid #e8ebf7;
    }
    
    .badge {
      display: inline-block;
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
    }
    
    .badge-pending { background: #fff3cd; color: #856404; }
    .badge-confirmed { background: #d4edda; color: #155724; }
    .badge-cancelled { background: #f8d7da; color: #721c24; }
    .badge-paid { background: #d4edda; color: #155724; }
    
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: #999;
    }
    
    .empty-state svg {
      width: 80px;
      height: 80px;
      margin-bottom: 20px;
      opacity: 0.3;
    }
    
    .error-message {
      background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
      color: white;
      padding: 15px 20px;
      border-radius: 12px;
      margin-bottom: 20px;
    }

    select {
      padding: 6px 10px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 13px;
      margin-right: 8px;
    }

    .payment-form,
    .reservation-form {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .reservation-details {
      font-size: 12px;
      color: #666;
      margin-top: 5px;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>Welcome, <?= htmlspecialchars($staff['first_name']) ?> 👋</h1>
      <a href="staff_logout.php" class="btn">Logout</a>
    </div>

    <?php if ($message): ?>
      <div class="message <?= $messageType ?>">
        <?= $message ?>
      </div>
    <?php endif; ?>

    <div class="info-card">
      <h2>Your Profile</h2>
      <div class="info-grid">
        <div class="info-item">
          <strong>Username</strong>
          <span><?= htmlspecialchars($staff['username']) ?></span>
        </div>
        <div class="info-item">
          <strong>Email Address</strong>
          <span><?= htmlspecialchars($staff['email']) ?></span>
        </div>
        <div class="info-item">
          <strong>Contact Number</strong>
          <span><?= htmlspecialchars($staff['contact_number']) ?></span>
        </div>
      </div>
    </div>

    <!-- Reservations Section -->
    <div class="section">
      <div class="section-header">
        <h2>Customer Reservations</h2>
      </div>
      
      <?php if ($res_result && $res_result->num_rows > 0): ?>
        <table>
          <thead>
            <tr>
              <th>Customer</th>
              <th>Contact</th>
              <th>Date & Time</th>
              <th>Table/Room</th>
              <th>Event Type</th>
              <th>Duration</th>
              <th>Total Price</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = $res_result->fetch_assoc()): ?>
              <tr>
                <td>
                  <strong><?= $row['customer_name'] ? htmlspecialchars($row['customer_name']) : '<em style="color: #999;">N/A</em>' ?></strong>
                </td>
                <td><?= $row['phone_number'] ? htmlspecialchars($row['phone_number']) : '<em style="color: #999;">N/A</em>' ?></td>
                <td>
                  <strong><?= date('M d, Y', strtotime($row['reservation_date'])) ?></strong>
                  <div class="reservation-details"><?= date('h:i A', strtotime($row['reservation_time'])) ?></div>
                </td>
                <td>
                  <strong><?= $row['table_number'] ? 'Table ' . htmlspecialchars($row['table_number']) : '<em style="color: #999;">Not assigned</em>' ?></strong>
                  <div class="reservation-details"><?= htmlspecialchars($row['table_type'] ?? 'N/A') ?></div>
                </td>
                <td><?= $row['event_type'] ? htmlspecialchars($row['event_type']) : '<em style="color: #999;">N/A</em>' ?></td>
                <td><?= $row['total_hours'] ? htmlspecialchars($row['total_hours']) . ' hrs' : '<em style="color: #999;">N/A</em>' ?></td>
                <td>
                  <strong><?= $row['total_price'] > 0 ? '₱' . number_format($row['total_price'], 2) : 'Free' ?></strong>
                </td>
                <td>
                  <span class="badge badge-<?= strtolower($row['status']) ?>">
                    <?= htmlspecialchars($row['status']) ?>
                  </span>
                </td>
                <td>
                  <form method="POST" class="reservation-form">
                    <input type="hidden" name="reservation_id" value="<?= $row['reservation_id'] ?>">
                    <input type="hidden" name="update_reservation_status" value="1">
                    <select name="reservation_status">
                      <option value="Pending" <?= $row['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                      <option value="Confirmed" <?= $row['status'] === 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                      <option value="Cancelled" <?= $row['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                    <button type="submit" class="btn btn-update">Update</button>
                  </form>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="empty-state">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <p>No reservations found</p>
        </div>
      <?php endif; ?>
    </div>

    <!-- Customer Payments Section -->
    <div class="section">
      <div class="section-header">
        <h2>Customer Payments</h2>
      </div>
      
      <?php if ($payment_result && $payment_result->num_rows > 0): ?>
        <table>
          <thead>
            <tr>
              <th>Payment ID</th>
              <th>Order ID</th>
              <th>Customer</th>
              <th>Amount</th>
              <th>Method</th>
              <th>Status</th>
              <th>Date</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($payment = $payment_result->fetch_assoc()): ?>
              <tr>
                <td><strong>#<?= htmlspecialchars($payment['payment_id']) ?></strong></td>
                <td>#<?= htmlspecialchars($payment['order_id']) ?></td>
                <td><?= htmlspecialchars($payment['customer_name']) ?></td>
                <td><strong>₱<?= number_format($payment['total_amount'], 2) ?></strong></td>
                <td><?= htmlspecialchars($payment['payment_method']) ?></td>
                <td>
                  <span class="badge badge-<?= strtolower($payment['payment_status']) ?>">
                    <?= htmlspecialchars($payment['payment_status']) ?>
                  </span>
                </td>
                <td><?= date('M d, Y', strtotime($payment['payment_date'])) ?></td>
                <td>
                  <form method="POST" action="update_payment_status.php" class="payment-form">
                    <input type="hidden" name="payment_id" value="<?= $payment['payment_id'] ?>">
                    <select name="payment_status">
                      <option value="Pending" <?= $payment['payment_status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                      <option value="Paid" <?= $payment['payment_status'] === 'Paid' ? 'selected' : '' ?>>Paid</option>
                    </select>
                    <button type="submit" class="btn btn-update">Update</button>
                  </form>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="empty-state">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
          <p>No payments found</p>
        </div>
      <?php endif; ?>
    </div>

    <div class="section">
      <div class="section-header">
        <h2>Customer Orders</h2>
      </div>
      
      <?php if ($prepare_error): ?>
        <div class="error-message">
          ⚠️ Error loading orders: <?= htmlspecialchars($prepare_error) ?>
        </div>
      <?php elseif ($orders_result && $orders_result->num_rows > 0): ?>
        <table>
          <thead>
            <tr>
              <th>Order ID</th>
              <th>Customer</th>
              <th>Contact</th>
              <th>Date</th>
              <th>Time</th>
              <th>Total</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($order = $orders_result->fetch_assoc()): ?>
              <tr>
                <td><strong>#<?= htmlspecialchars($order['order_id']) ?></strong></td>
                <td><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></td>
                <td><?= htmlspecialchars($order['phone_number']) ?></td>
                <td><?= date('M d, Y', strtotime($order['order_date'])) ?></td>
                <td><?= date('h:i A', strtotime($order['order_time'])) ?></td>
                <td><strong>₱<?= number_format($order['total_amount'], 2) ?></strong></td>
                <td>
                  <div class="action-buttons">
                    <a href="staff_view_order.php?id=<?= $order['order_id'] ?>" class="btn btn-view">View</a>
                    <a href="staff_edit_order.php?id=<?= $order['order_id'] ?>" class="btn btn-edit">Edit</a>
                  </div>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="empty-state">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <p>No orders found</p>
        </div>
      <?php endif; ?>
    </div>

    <div class="section">
      <div class="section-header">
        <h2>Products</h2>
        <a href="staff_add_product.php" class="btn">➕ Add Product</a>
      </div>
      
      <?php if ($product_result && $product_result->num_rows > 0): ?>
        <table>
          <thead>
            <tr>
              <th>Image</th>
              <th>Name</th>
              <th>Description</th>
              <th>Price</th>
              <th>Category</th>
              <th>Stock</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($prod = $product_result->fetch_assoc()): ?>
              <tr>
                <td>
                  <img src="uploads/<?= htmlspecialchars($prod['image']) ?>" 
                       alt="<?= htmlspecialchars($prod['product_name']) ?>" 
                       class="product-img"
                       onerror="this.src='uploads/placeholder.jpg'" />
                </td>
                <td><strong><?= htmlspecialchars($prod['product_name']) ?></strong></td>
                <td><?= htmlspecialchars(substr($prod['description'], 0, 50)) ?>...</td>
                <td><strong>₱<?= number_format($prod['price'], 2) ?></strong></td>
                <td><?= $prod['category_name'] ? htmlspecialchars($prod['category_name']) : '<em style="color: #999;">Uncategorized</em>' ?></td>
                <td><?= htmlspecialchars($prod['stock_quantity']) ?></td>
                <td>
                  <a href="staff_edit_product.php?id=<?= $prod['product_id'] ?>" class="btn btn-edit">Edit</a>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="empty-state">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
          </svg>
          <p>No products found</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>