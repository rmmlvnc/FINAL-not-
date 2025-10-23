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
if (!$cust_stmt) {
  die("Prepare failed: " . $conn->error);
}
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
  if (!$cancel_stmt) {
    die("Prepare failed: " . $conn->error);
  }
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
  $status = 'Pending';

  // Check if reservation table has the new columns
  $check_columns = $conn->query("SHOW COLUMNS FROM reservation LIKE 'event_type'");
  if ($check_columns->num_rows == 0) {
    // Add new columns if they don't exist
    $conn->query("ALTER TABLE reservation ADD COLUMN event_type VARCHAR(100) DEFAULT 'Regular Dining'");
    $conn->query("ALTER TABLE reservation ADD COLUMN status VARCHAR(50) DEFAULT 'Pending'");
  }

  $stmt = $conn->prepare("INSERT INTO reservation (customer_id, table_id, reservation_date, reservation_time, event_type, status) VALUES (?, ?, ?, ?, ?, ?)");
  if (!$stmt) {
    die("Prepare failed: " . $conn->error);
  }
  $stmt->bind_param("iisssss", $customer_id, $table_id, $date, $time, $event_type, $status);
  
  if ($stmt->execute()) {
    $message = "Reservation submitted successfully! Our staff will confirm your booking shortly.";
    $messageType = "success";
  } else {
    $message = "Error: " . $stmt->error;
    $messageType = "error";
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Reserve a Table | Kyla's Bistro</title>
  <link rel="stylesheet" href="style.css" />
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(135deg, #fff7f0 0%, #ffe0e0 100%);
      margin: 0;
      padding: 0;
      min-height: 100vh;
    }

    .reservation-wrapper {
      max-width: 1400px;
      margin: 0 auto;
      padding: 40px 20px;
    }

    .back-link {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 20px;
      background-color: #c00;
      color: white;
      text-decoration: none;
      border-radius: 25px;
      font-weight: 600;
      margin-bottom: 30px;
      transition: all 0.3s ease;
      box-shadow: 0 4px 10px rgba(204, 0, 0, 0.2);
    }

    .back-link:hover {
      background-color: #a00;
      transform: translateY(-2px);
      box-shadow: 0 6px 14px rgba(204, 0, 0, 0.3);
    }

    .page-header {
      text-align: center;
      margin-bottom: 50px;
    }

    .page-header h1 {
      font-size: 3rem;
      color: #253745;
      margin-bottom: 10px;
    }

    .page-header p {
      font-size: 1.2rem;
      color: #666;
    }

    .reservation-grid {
      display: grid;
      grid-template-columns: 1.2fr 1fr;
      gap: 30px;
      margin-bottom: 50px;
    }

    .reservation-form-card,
    .reservation-info-card {
      background: white;
      border-radius: 16px;
      padding: 40px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    }

    .card-title {
      font-size: 1.8rem;
      color: #253745;
      margin-bottom: 25px;
      padding-bottom: 15px;
      border-bottom: 3px solid #c00;
    }

    .message {
      padding: 15px 20px;
      border-radius: 8px;
      margin-bottom: 25px;
      font-weight: 600;
      text-align: center;
      animation: slideDown 0.5s ease;
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

    .form-step {
      margin-bottom: 30px;
    }

    .step-label {
      font-size: 0.9rem;
      color: #c00;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 15px;
      display: block;
    }

    .event-types {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 15px;
      margin-bottom: 25px;
    }

    .event-type-option {
      position: relative;
    }

    .event-type-option input[type="radio"] {
      display: none;
    }

    .event-type-label {
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 20px;
      background: #f8f9fa;
      border: 2px solid #e0e0e0;
      border-radius: 12px;
      cursor: pointer;
      transition: all 0.3s ease;
      text-align: center;
    }

    .event-type-option input[type="radio"]:checked + .event-type-label {
      background: #fff7f0;
      border-color: #c00;
      box-shadow: 0 4px 12px rgba(204, 0, 0, 0.15);
    }

    .event-type-label:hover {
      border-color: #c00;
      transform: translateY(-3px);
    }

    .event-icon {
      font-size: 2rem;
      margin-bottom: 10px;
    }

    .event-name {
      font-weight: 600;
      color: #253745;
      font-size: 1rem;
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-bottom: 25px;
    }

    .form-group {
      margin-bottom: 25px;
    }

    .form-group label {
      display: block;
      font-weight: 600;
      color: #253745;
      margin-bottom: 8px;
      font-size: 0.95rem;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 12px 15px;
      border: 2px solid #e0e0e0;
      border-radius: 8px;
      font-size: 1rem;
      transition: all 0.3s ease;
      font-family: 'Segoe UI', sans-serif;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: #c00;
      box-shadow: 0 0 0 3px rgba(204, 0, 0, 0.1);
    }

    .form-group textarea {
      resize: vertical;
      min-height: 100px;
    }

    .submit-btn {
      width: 100%;
      padding: 16px;
      background-color: #c00;
      color: white;
      border: none;
      border-radius: 25px;
      font-size: 1.1rem;
      font-weight: bold;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 6px 16px rgba(204, 0, 0, 0.3);
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    .submit-btn:hover {
      background-color: #a00;
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(204, 0, 0, 0.4);
    }

    .info-section {
      margin-bottom: 30px;
    }

    .info-section h3 {
      color: #253745;
      font-size: 1.2rem;
      margin-bottom: 15px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .info-list {
      list-style: none;
      padding: 0;
    }

    .info-list li {
      padding: 12px 0;
      border-bottom: 1px solid #f0f0f0;
      display: flex;
      align-items: center;
      gap: 10px;
      color: #555;
    }

    .info-list li:last-child {
      border-bottom: none;
    }

    .info-list li::before {
      content: '✓';
      display: flex;
      align-items: center;
      justify-content: center;
      width: 20px;
      height: 20px;
      background-color: #c00;
      color: white;
      border-radius: 50%;
      font-size: 0.8rem;
      font-weight: bold;
      flex-shrink: 0;
    }

    .contact-info {
      background: linear-gradient(135deg, #253745 0%, #2f4f4f 100%);
      padding: 25px;
      border-radius: 12px;
      color: white;
    }

    .contact-info h3 {
      color: white;
      margin-bottom: 15px;
    }

    .contact-item {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 12px;
      color: rgba(255, 255, 255, 0.9);
    }

    .reservations-list {
      background: white;
      border-radius: 16px;
      padding: 40px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.12);
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

    .reservations-table th {
      padding: 15px;
      text-align: left;
      font-weight: 600;
      font-size: 0.9rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .reservations-table td {
      padding: 15px;
      border-bottom: 1px solid #f0f0f0;
    }

    .reservations-table tr:hover {
      background-color: #f8f9fa;
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

    .status-cancelled {
      background-color: #f8d7da;
      color: #721c24;
    }

    .cancel-btn {
      padding: 8px 16px;
      background-color: #ff4757;
      color: white;
      border: none;
      border-radius: 6px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 0.85rem;
    }

    .cancel-btn:hover {
      background-color: #ee2f3d;
      transform: scale(1.05);
    }

    .no-reservations {
      text-align: center;
      padding: 40px;
      color: #999;
    }

    .no-reservations-icon {
      font-size: 4rem;
      margin-bottom: 15px;
      opacity: 0.3;
    }

    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @media (max-width: 1024px) {
      .reservation-grid {
        grid-template-columns: 1fr;
      }

      .event-types {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 768px) {
      .page-header h1 {
        font-size: 2rem;
      }

      .form-row {
        grid-template-columns: 1fr;
      }

      .reservation-form-card,
      .reservation-info-card,
      .reservations-list {
        padding: 25px;
      }

      .reservations-table {
        font-size: 0.85rem;
      }

      .reservations-table th,
      .reservations-table td {
        padding: 10px;
      }
    }
  </style>
</head>
<body>
  <div class="reservation-wrapper">
    <a href="index.php" class="back-link">
      ← Back to Home
    </a>

    <div class="page-header">
      <h1>🍽️ Reserve Your Table</h1>
      <p>Book your perfect dining experience at Kyla's Bistro</p>
    </div>

    <?php if ($message): ?>
      <div class="message <?= $messageType ?>">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>

    <div class="reservation-grid">
      <!-- Reservation Form -->
      <div class="reservation-form-card">
        <h2 class="card-title">Make a Reservation</h2>
        <form method="POST">
          <!-- Step 1: Event Type -->
          <div class="form-step">
            <span class="step-label">Step 1: Select Event Type</span>
            <div class="event-types">
              <div class="event-type-option">
                <input type="radio" id="regular" name="event_type" value="Regular Dining" checked>
                <label for="regular" class="event-type-label">
                  <span class="event-icon">🍽️</span>
                  <span class="event-name">Regular Dining</span>
                </label>
              </div>
              <div class="event-type-option">
                <input type="radio" id="birthday" name="event_type" value="Intimate Birthday Party">
                <label for="birthday" class="event-type-label">
                  <span class="event-icon">🎂</span>
                  <span class="event-name">Birthday Party</span>
                </label>
              </div>
              <div class="event-type-option">
                <input type="radio" id="wedding" name="event_type" value="Romantic Wedding">
                <label for="wedding" class="event-type-label">
                  <span class="event-icon">💒</span>
                  <span class="event-name">Wedding</span>
                </label>
              </div>
              <div class="event-type-option">
                <input type="radio" id="corporate" name="event_type" value="Corporate Event">
                <label for="corporate" class="event-type-label">
                  <span class="event-icon">💼</span>
                  <span class="event-name">Corporate Event</span>
                </label>
              </div>
              <div class="event-type-option">
                <input type="radio" id="meeting" name="event_type" value="Private Meeting">
                <label for="meeting" class="event-type-label">
                  <span class="event-icon">📋</span>
                  <span class="event-name">Private Meeting</span>
                </label>
              </div>
              <div class="event-type-option">
                <input type="radio" id="anniversary" name="event_type" value="Anniversary Celebration">
                <label for="anniversary" class="event-type-label">
                  <span class="event-icon">💕</span>
                  <span class="event-name">Anniversary</span>
                </label>
              </div>
            </div>
          </div>

          <!-- Step 2: Date & Time -->
          <div class="form-step">
            <span class="step-label">Step 2: Choose Date & Time</span>
            <div class="form-row">
              <div class="form-group">
                <label for="reservation_date">Date</label>
                <input type="date" name="reservation_date" id="reservation_date" required min="<?= date('Y-m-d') ?>" />
              </div>
              <div class="form-group">
                <label for="reservation_time">Time</label>
                <input type="time" name="reservation_time" id="reservation_time" required />
              </div>
            </div>
          </div>

          <!-- Step 3: Select Table -->
          <div class="form-step">
            <span class="step-label">Step 3: Select Table</span>
            <div class="form-group">
              <label for="table_id">Select Table</label>
              <select name="table_id" id="table_id" required>
                <option value="">-- Choose a Table --</option>
                <?php
                $tables = $conn->query("SELECT table_id, table_number, capacity FROM tables ORDER BY table_number");
                while ($row = $tables->fetch_assoc()):
                ?>
                  <option value="<?= $row['table_id'] ?>">
                    Table <?= $row['table_number'] ?> (<?= $row['capacity'] ?> seats)
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
          </div>

          <button type="submit" class="submit-btn">Confirm Reservation</button>
        </form>
      </div>

      <!-- Info Sidebar -->
      <div>
        <div class="reservation-info-card">
          <h2 class="card-title">What to Expect</h2>
          
          <div class="info-section">
            <h3>📋 Reservation Process</h3>
            <ul class="info-list">
              <li>Select your event type and preferences</li>
              <li>Choose your preferred date and time</li>
              <li>Your reservation will be pending confirmation</li>
              <li>Our staff will contact you within 24 hours</li>
            </ul>
          </div>

          <div class="info-section">
            <h3>⭐ Special Events</h3>
            <ul class="info-list">
              <li>Custom menu planning available</li>
              <li>Event decoration options</li>
              <li>Private dining areas</li>
              <li>Audio/Visual equipment support</li>
            </ul>
          </div>
        </div>

        <div class="contact-info">
          <h3>📞 Need Help?</h3>
          <div class="contact-item">
            <span>📧</span>
            <span>kylasbistro.ph@gmail.com</span>
          </div>
          <div class="contact-item">
            <span>📱</span>
            <span>+63 917 888 8309</span>
          </div>
          <div class="contact-item">
            <span>🕐</span>
            <span>10:00 AM - 10:00 PM Daily</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Reservations List -->
    <div class="reservations-list">
      <h2 class="card-title">🗓️ Your Reservations</h2>
      <?php
      // Check which columns exist in reservation table
      $columns_query = $conn->query("SHOW COLUMNS FROM reservation");
      $columns = [];
      while ($col = $columns_query->fetch_assoc()) {
        $columns[] = $col['Field'];
      }
      
      $has_event_type = in_array('event_type', $columns);
      $has_status = in_array('status', $columns);
      
      // Build query based on available columns
      if ($has_event_type && $has_status) {
        $query = "SELECT r.reservation_id, r.reservation_date, r.reservation_time, r.event_type, r.status, t.table_number, t.capacity 
                  FROM reservation r 
                  JOIN tables t ON r.table_id = t.table_id 
                  WHERE r.customer_id = ? 
                  ORDER BY r.reservation_date DESC, r.reservation_time DESC";
      } else {
        $query = "SELECT r.reservation_id, r.reservation_date, r.reservation_time, r.table_id, t.table_number, t.capacity 
                  FROM reservation r 
                  JOIN tables t ON r.table_id = t.table_id 
                  WHERE r.customer_id = ? 
                  ORDER BY r.reservation_date DESC, r.reservation_time DESC";
      }
      
      $res_stmt = $conn->prepare($query);
      if (!$res_stmt) {
        die("Prepare failed: " . $conn->error);
      }
      $res_stmt->bind_param("i", $customer_id);
      $res_stmt->execute();
      $res_result = $res_stmt->get_result();

      if ($res_result->num_rows === 0): ?>
        <div class="no-reservations">
          <div class="no-reservations-icon">📅</div>
          <p>You have no reservations yet.</p>
          <p>Book your first table above!</p>
        </div>
      <?php else: ?>
        <table class="reservations-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Time</th>
              <?php if ($has_event_type): ?>
              <th>Event Type</th>
              <?php endif; ?>
              <th>Table</th>
              <?php if ($has_status): ?>
              <th>Status</th>
              <?php endif; ?>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($res = $res_result->fetch_assoc()): 
              $status = $has_status ? ($res['status'] ?? 'Pending') : 'Pending';
              $status_class = 'status-' . strtolower($status);
            ?>
              <tr>
                <td><?= date('M d, Y', strtotime($res['reservation_date'])) ?></td>
                <td><?= date('h:i A', strtotime($res['reservation_time'])) ?></td>
                <?php if ($has_event_type): ?>
                <td><?= htmlspecialchars($res['event_type'] ?? 'Regular Dining') ?></td>
                <?php endif; ?>
                <td>Table <?= htmlspecialchars($res['table_number']) ?> (<?= $res['capacity'] ?> seats)</td>
                <?php if ($has_status): ?>
                <td>
                  <span class="status-badge <?= $status_class ?>">
                    <?= htmlspecialchars($status) ?>
                  </span>
                </td>
                <?php endif; ?>
                <td>
                  <form method="POST" style="margin: 0;">
                    <input type="hidden" name="cancel_reservation_id" value="<?= $res['reservation_id'] ?>" />
                    <button type="submit" class="cancel-btn" onclick="return confirm('Are you sure you want to cancel this reservation?')">Cancel</button>
                  </form>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>