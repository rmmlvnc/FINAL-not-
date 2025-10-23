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

// Make reservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['table_id'])) {
  $table_id = $_POST['table_id'];
  $date = $_POST['reservation_date'];
  $time = $_POST['reservation_time'];
  $event_type = $_POST['event_type'];
  $total_hours = $_POST['total_hours'] ?? 2;
  $status = 'Pending';
  
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

  $stmt = $conn->prepare("INSERT INTO reservation (customer_id, table_id, reservation_date, reservation_time, event_type, status, guest_count, total_hours, total_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
  $stmt->bind_param("iissssiid", $customer_id, $table_id, $date, $time, $event_type, $status, $guest_count, $total_hours, $total_price);
  
  if ($stmt->execute()) {
    if ($total_price > 0) {
      $message = "Reservation submitted successfully! Total Cost: ₱" . number_format($total_price, 2) . ". Our staff will confirm your booking shortly.";
    } else {
      $message = "Reservation submitted successfully! Our staff will confirm your booking shortly.";
    }
    $messageType = "success";
  } else {
    $message = "Error: " . $stmt->error;
    $messageType = "error";
  }
  $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Reserve a Table | Kyla's Bistro</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(135deg, #fff7f0 0%, #ffe0e0 100%);
      min-height: 100vh;
      padding: 20px;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
    }

    .back-link {
      display: inline-block;
      padding: 10px 20px;
      background-color: #c00;
      color: white;
      text-decoration: none;
      border-radius: 25px;
      font-weight: 600;
      margin-bottom: 30px;
      transition: all 0.3s;
    }

    .back-link:hover {
      background-color: #a00;
    }

    .header {
      text-align: center;
      margin-bottom: 40px;
    }

    .header h1 {
      font-size: 2.5rem;
      color: #253745;
      margin-bottom: 10px;
    }

    .header p {
      font-size: 1.1rem;
      color: #666;
    }

    .message {
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-weight: 600;
      text-align: center;
    }

    .message.success {
      background-color: #d4edda;
      color: #155724;
    }

    .message.error {
      background-color: #f8d7da;
      color: #721c24;
    }

    .card {
      background: white;
      border-radius: 12px;
      padding: 30px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      margin-bottom: 30px;
    }

    .card-title {
      font-size: 1.5rem;
      color: #253745;
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 2px solid #c00;
    }

    .form-section {
      margin-bottom: 25px;
    }

    .form-label {
      display: block;
      font-weight: 600;
      color: #253745;
      margin-bottom: 10px;
      font-size: 0.9rem;
    }

    .event-types {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 15px;
    }

    .event-option input[type="radio"] {
      display: none;
    }

    .event-label {
      display: block;
      padding: 20px;
      background: #f8f9fa;
      border: 2px solid #e0e0e0;
      border-radius: 10px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s;
    }

    .event-option input[type="radio"]:checked + .event-label {
      background: #fff7f0;
      border-color: #c00;
    }

    .event-label:hover {
      border-color: #c00;
    }

    .event-icon {
      font-size: 2rem;
      margin-bottom: 5px;
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-group label {
      display: block;
      font-weight: 600;
      color: #253745;
      margin-bottom: 8px;
    }

    .form-group input {
      width: 100%;
      padding: 12px;
      border: 2px solid #e0e0e0;
      border-radius: 8px;
      font-size: 1rem;
    }

    .form-group input:focus {
      outline: none;
      border-color: #c00;
    }

    .table-category {
      margin-bottom: 20px;
    }

    .category-header {
      background: #253745;
      color: white;
      padding: 12px 20px;
      border-radius: 8px 8px 0 0;
      font-weight: 700;
    }

    .table-options {
      border: 2px solid #e0e0e0;
      border-top: none;
      border-radius: 0 0 8px 8px;
      padding: 15px;
      background: #f8f9fa;
    }

    .table-item {
      display: flex;
      align-items: center;
      gap: 15px;
      padding: 15px;
      background: white;
      border-radius: 6px;
      margin-bottom: 10px;
      cursor: pointer;
      transition: all 0.3s;
    }

    .table-item:hover {
      background: #fff7f0;
    }

    .table-item input[type="radio"] {
      width: 20px;
      height: 20px;
      cursor: pointer;
      accent-color: #c00;
    }

    .table-info {
      flex: 1;
    }

    .table-name {
      font-weight: 700;
      color: #253745;
      margin-bottom: 5px;
    }

    .table-desc {
      font-size: 0.85rem;
      color: #666;
      margin-bottom: 5px;
    }

    .table-capacity {
      font-size: 0.9rem;
      color: #c00;
      font-weight: 600;
    }

    .table-price {
      text-align: right;
      font-weight: 700;
      color: #c00;
      font-size: 1.1rem;
    }

    .price-display {
      background: #fff7f0;
      padding: 15px;
      border-radius: 8px;
      border: 2px solid #c00;
      text-align: center;
      margin-top: 20px;
      display: none;
    }

    .total-price {
      color: #c00;
      font-size: 1.3rem;
      font-weight: 700;
    }

    .submit-btn {
      width: 100%;
      padding: 15px;
      background-color: #c00;
      color: white;
      border: none;
      border-radius: 25px;
      font-size: 1.1rem;
      font-weight: bold;
      cursor: pointer;
      transition: all 0.3s;
      text-transform: uppercase;
    }

    .submit-btn:hover {
      background-color: #a00;
    }

    .reservations-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }

    .reservations-table thead {
      background: #253745;
      color: white;
    }

    .reservations-table th,
    .reservations-table td {
      padding: 12px;
      text-align: left;
    }

    .reservations-table td {
      border-bottom: 1px solid #f0f0f0;
    }

    .status-badge {
      display: inline-block;
      padding: 5px 12px;
      border-radius: 12px;
      font-size: 0.85rem;
      font-weight: 600;
    }

    .status-pending {
      background-color: #fff3cd;
      color: #856404;
    }

    .status-confirmed {
      background-color: #d4edda;
      color: #155724;
    }

    .cancel-btn {
      padding: 8px 16px;
      background-color: #ff4757;
      color: white;
      border: none;
      border-radius: 6px;
      font-weight: 600;
      cursor: pointer;
    }

    .cancel-btn:hover {
      background-color: #ee2f3d;
    }

    .no-reservations {
      text-align: center;
      padding: 40px;
      color: #999;
    }

    @media (max-width: 768px) {
      .event-types,
      .form-row {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <a href="index.php" class="back-link">← Back to Home</a>

    <div class="header">
      <h1>Reserve Your Table</h1>
      <p>Reserve now! perfect dining experience at Kyla's Bistro</p>
    </div>

    <?php if ($message): ?>
      <div class="message <?= $messageType ?>">
        <?= $message ?>
      </div>
    <?php endif; ?>

    <div class="card">
      <h2 class="card-title">Make a Reservation</h2>
      <form method="POST" id="reservationForm">
        
        <!-- Event Type -->
        <div class="form-section">
          <label class="form-label">Select Dining Type</label>
          <div class="event-types">
            <div class="event-option">
              <input type="radio" id="regular" name="event_type" value="Regular Dining" checked onchange="filterTables()">
              <label for="regular" class="event-label">
                <div class="event-icon">🍽️</div>
                <div>Regular Dining</div>
              </label>
            </div>
            <div class="event-option">
              <input type="radio" id="birthday" name="event_type" value="Birthday Party" onchange="filterTables()">
              <label for="birthday" class="event-label">
                <div class="event-icon">🎂</div>
                <div>Birthday Party</div>
              </label>
            </div>
            <div class="event-option">
              <input type="radio" id="meeting" name="event_type" value="Meeting" onchange="filterTables()">
              <label for="meeting" class="event-label">
                <div class="event-icon">💼</div>
                <div>Meeting</div>
              </label>
            </div>
          </div>
        </div>

        <!-- Date & Time -->
        <div class="form-section">
          <label class="form-label">Choose Date & Time</label>
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
          <div class="form-group" id="durationField" style="display: none;">
            <label>Duration (Hours)</label>
            <input type="number" name="total_hours" id="total_hours" min="1" max="12" value="2" onchange="updatePrice()" />
          </div>
        </div>

        <!-- Select Table -->
        <div class="form-section">
          <label class="form-label">Select Table/Room</label>
          <div id="tablesList">
            <?php
            $tables_query = "SELECT table_id, table_number, capacity, table_type, description, price_per_hour 
                             FROM tables 
                             WHERE status = 'Available' 
                             ORDER BY 
                               CASE table_type 
                                 WHEN 'Regular (Outside)' THEN 1
                                 WHEN 'Regular (Inside)' THEN 2
                                 WHEN 'Birthday Party Room' THEN 3
                                 WHEN 'Meeting Room' THEN 4
                               END, table_number";
            
            $tables_result = $conn->query($tables_query);
            $grouped_tables = [];
            while ($table = $tables_result->fetch_assoc()) {
              $type = $table['table_type'];
              $grouped_tables[$type][] = $table;
            }
            
            $icons = [
              'Regular (Outside)' => '🌳',
              'Regular (Inside)' => '🏠',
              'Birthday Party Room' => '🎉',
              'Meeting Room' => '💼'
            ];
            
            foreach ($grouped_tables as $type => $tables):
            ?>
              <div class="table-category" data-table-type="<?= htmlspecialchars($type) ?>">
                <div class="category-header">
                  <?= $icons[$type] ?? '📍' ?> <?= htmlspecialchars($type) ?>
                </div>
                <div class="table-options">
                  <?php foreach ($tables as $table): ?>
                    <label class="table-item">
                      <input type="radio" name="table_id" value="<?= $table['table_id'] ?>" 
                             required data-price="<?= $table['price_per_hour'] ?>" onchange="updatePrice()">
                      <div class="table-info">
                        <div class="table-name">Table <?= htmlspecialchars($table['table_number']) ?></div>
                        <?php if ($table['description']): ?>
                          <div class="table-desc"><?= htmlspecialchars($table['description']) ?></div>
                        <?php endif; ?>
                        <div class="table-capacity">Capacity: <?= $table['capacity'] ?> guests</div>
                      </div>
                      <div class="table-price">
                        <?= $table['price_per_hour'] > 0 ? '₱' . number_format($table['price_per_hour'], 2) . '/hr' : 'Free' ?>
                      </div>
                    </label>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <div id="priceDisplay" class="price-display">
            <strong>Estimated Total:</strong> 
            <span id="totalPrice" class="total-price">₱0.00</span>
          </div>
        </div>

        <button type="submit" class="submit-btn">Complete Reservation</button>
      </form>
    </div>

    <!-- My Reservations -->
    <div class="card">
      <h2 class="card-title">My Reservations</h2>
      <?php
      $reservations_query = $conn->prepare("SELECT r.reservation_id, r.reservation_date, r.reservation_time, r.event_type, r.status, r.total_hours, r.total_price, t.table_number, t.table_type, t.capacity 
                                             FROM reservation r 
                                             JOIN tables t ON r.table_id = t.table_id 
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
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($res = $reservations_result->fetch_assoc()): ?>
              <tr>
                <td><?= date('M d, Y', strtotime($res['reservation_date'])) ?></td>
                <td><?= date('h:i A', strtotime($res['reservation_time'])) ?></td>
                <td>
                  <strong><?= htmlspecialchars($res['table_number']) ?></strong><br>
                  <small><?= htmlspecialchars($res['table_type']) ?></small>
                </td>
                <td><?= htmlspecialchars($res['event_type']) ?></td>
                <td><?= $res['total_hours'] ?> hr<?= $res['total_hours'] > 1 ? 's' : '' ?></td>
                <td>
                  <?= $res['total_price'] > 0 ? '₱' . number_format($res['total_price'], 2) : 'Free' ?>
                </td>
                <td>
                  <span class="status-badge status-<?= strtolower($res['status']) ?>">
                    <?= htmlspecialchars($res['status']) ?>
                  </span>
                </td>
                <td>
                  <?php if ($res['status'] === 'Pending'): ?>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure?');">
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
          <p>📅 You don't have any reservations yet.</p>
        </div>
      <?php endif; ?>
    </div>

    <div class="card" style="background: linear-gradient(135deg, #253745 0%, #2f4f4f 100%); color: white;">
      <h2 class="card-title" style="color: white; border-bottom-color: white;">📞 Need Help?</h2>
      <div style="display: grid; gap: 15px;">
        <div>
          <strong>Phone:</strong> <?= htmlspecialchars($phone ?? '(02) 8123-4567') ?>
        </div>
        <div>
          <strong>Email:</strong> <?= htmlspecialchars($email ?? 'reservations@kylasbistro.com') ?>
        </div>
        <div>
          <strong>Hours:</strong> Daily 10:00 AM - 10:00 PM
        </div>
      </div>
    </div>
  </div>

  <script>
    function filterTables() {
      const selectedEvent = document.querySelector('input[name="event_type"]:checked').value;
      const categories = document.querySelectorAll('.table-category');
      
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

      document.querySelectorAll('input[name="table_id"]').forEach(radio => radio.checked = false);
      updatePrice();
    }

    function updatePrice() {
      const selectedTable = document.querySelector('input[name="table_id"]:checked');
      const totalHours = parseInt(document.getElementById('total_hours').value) || 2;
      const priceDisplay = document.getElementById('priceDisplay');
      const totalPriceElement = document.getElementById('totalPrice');
      
      if (selectedTable) {
        const pricePerHour = parseFloat(selectedTable.getAttribute('data-price')) || 0;
        const totalPrice = pricePerHour * totalHours;
        
        if (totalPrice > 0) {
          totalPriceElement.textContent = '₱' + totalPrice.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
          priceDisplay.style.display = 'block';
        } else {
          priceDisplay.style.display = 'none';
        }
      } else {
        priceDisplay.style.display = 'none';
      }
    }

    document.addEventListener('DOMContentLoaded', function() {
      filterTables();
    });
  </script>
</body>
</html>
<?php $conn->close(); ?>