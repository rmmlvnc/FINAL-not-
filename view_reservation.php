<?php
session_start();
include 'database.php';

// Check if customer is logged in
if (!isset($_SESSION['customer_id'])) {
  header("Location: login.php");
  exit();
}

$customer_id = $_SESSION['customer_id'];
$reservation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($reservation_id <= 0) {
  header("Location: profile.php");
  exit();
}

$message = "";
$messageType = "";

// Handle reservation cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_reservation'])) {
  $cancel_stmt = $conn->prepare("UPDATE reservation SET status = 'Cancelled' WHERE reservation_id = ? AND customer_id = ? AND status = 'Pending'");
  $cancel_stmt->bind_param("ii", $reservation_id, $customer_id);
  
  if ($cancel_stmt->execute() && $cancel_stmt->affected_rows > 0) {
    $message = "Reservation cancelled successfully!";
    $messageType = "success";
  } else {
    $message = "Unable to cancel reservation. Only pending reservations can be cancelled.";
    $messageType = "error";
  }
  $cancel_stmt->close();
}

// Fetch reservation details
$reservation_stmt = $conn->prepare("
  SELECT 
    r.*,
    t.table_number,
    t.table_type,
    t.capacity,
    t.description as table_description,
    t.price_per_hour,
    c.first_name,
    c.middle_name,
    c.last_name,
    c.email,
    c.phone_number
  FROM reservation r
  JOIN tables t ON r.table_id = t.table_id
  JOIN customer c ON r.customer_id = c.customer_id
  WHERE r.reservation_id = ? AND r.customer_id = ?
");
$reservation_stmt->bind_param("ii", $reservation_id, $customer_id);
$reservation_stmt->execute();
$result = $reservation_stmt->get_result();

if ($result->num_rows === 0) {
  header("Location: profile.php");
  exit();
}

$reservation = $result->fetch_assoc();
$reservation_stmt->close();

// Calculate if reservation is cancellable
$reservation_date = strtotime($reservation['reservation_date']);
$today = strtotime(date('Y-m-d'));
$is_cancellable = ($reservation['status'] === 'Pending' && $reservation_date >= $today);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reservation Details - Kyla's Bistro</title>
  <link rel="stylesheet" href="style.css" />
  <style>
    body {
      background-color: #f9f6f2;
      font-family: 'Segoe UI', Arial, sans-serif;
    }
    
    .reservation-container {
      max-width: 800px;
      margin: 30px auto;
      padding: 20px;
    }
    
    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
    }
    
    .page-title {
      font-size: 26px;
      color: #333;
      margin: 0;
      font-weight: 600;
    }
    
    .back-btn {
      background: #6c757d;
      color: white;
      padding: 10px 20px;
      border-radius: 4px;
      text-decoration: none;
      font-weight: 500;
    }
    
    .back-btn:hover {
      background: #5a6268;
    }
    
    .alert {
      padding: 15px;
      border-radius: 4px;
      margin-bottom: 20px;
      font-weight: 500;
    }
    
    .alert-success {
      background: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }
    
    .alert-error {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }
    
    .details-card {
      background: white;
      padding: 30px;
      border-radius: 6px;
      border: 1px solid #ddd;
      margin-bottom: 20px;
    }
    
    .status-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding-bottom: 20px;
      border-bottom: 2px solid #eee;
      margin-bottom: 30px;
    }
    
    .reservation-id {
      font-size: 20px;
      font-weight: 600;
      color: #333;
    }
    
    .status-badge {
      padding: 8px 16px;
      border-radius: 4px;
      font-size: 14px;
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
    
    .status-completed {
      background-color: #d1ecf1;
      color: #0c5460;
    }
    
    .section-title {
      font-size: 17px;
      font-weight: 600;
      color: #333;
      margin-bottom: 15px;
      padding-bottom: 10px;
      border-bottom: 2px solid #8b4513;
    }
    
    .info-row {
      display: grid;
      grid-template-columns: 170px 1fr;
      padding: 14px 0;
      border-bottom: 1px solid #f0f0f0;
    }
    
    .info-row:last-child {
      border-bottom: none;
    }
    
    .info-label {
      font-weight: 600;
      color: #555;
      font-size: 15px;
    }
    
    .info-value {
      color: #333;
      font-size: 15px;
    }
    
    .price-highlight {
      font-size: 22px;
      color: #8b4513;
      font-weight: 700;
    }
    
    .section-spacing {
      margin-bottom: 30px;
    }
    
    .action-buttons {
      padding-top: 25px;
      border-top: 2px solid #eee;
      margin-top: 30px;
      text-align: right;
    }
    
    .cancel-btn {
      background: #dc3545;
      color: white;
      padding: 12px 24px;
      border: none;
      border-radius: 4px;
      font-size: 15px;
      font-weight: 600;
      cursor: pointer;
    }
    
    .cancel-btn:hover {
      background: #c82333;
    }
    
    .info-note {
      background: #f8f9fa;
      border-left: 4px solid #007bff;
      padding: 15px;
      margin-top: 20px;
      border-radius: 4px;
      font-size: 14px;
      color: #495057;
      line-height: 1.6;
    }
    
    .info-note strong {
      color: #333;
      display: block;
      margin-bottom: 5px;
    }
    
    @media (max-width: 768px) {
      .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
      }
      
      .info-row {
        grid-template-columns: 1fr;
        gap: 5px;
      }
    }
  </style>
</head>
<body class="index">
  <header>
    <div class="nav-bar">
      <img src="pictures/logo.jpg" alt="Kyla Logo" class="logo" />
      <div class="nav-actions">
        <?php $cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>
        <span class="welcome-text">👋 Welcome, <?= htmlspecialchars($_SESSION['username']) ?></span>
        <a href="profile.php" class="btn profile-btn" title="View Profile">👤 Profile</a>
        <a href="cart.php" class="cart-icon" title="View Cart">🛒<?= $cart_count > 0 ? " ($cart_count)" : "" ?></a>
        <a href="customer_logout.php" class="btn logout-btn">LOG OUT</a>
      </div>
    </div>
  </header>

  <nav>
    <ul class="links">
      <li><a href="index.php">HOME</a></li>
      <li><a href="menu.php">MENU</a></li>
      <li><a href="aboutus.php">ABOUT US</a></li>
    </ul>
  </nav>

  <div class="reservation-container">
    <div class="page-header">
      <h1 class="page-title">Reservation Details</h1>
      <a href="profile.php" class="back-btn">← Back to Profile</a>
    </div>
    
    <?php if ($message): ?>
      <div class="alert alert-<?= $messageType ?>">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>

    <div class="details-card">
      <!-- Status Header -->
      <div class="status-header">
        <div class="reservation-id">Reservation #<?= htmlspecialchars($reservation['reservation_id']) ?></div>
        <span class="status-badge status-<?= strtolower($reservation['status']) ?>">
          <?= htmlspecialchars($reservation['status']) ?>
        </span>
      </div>

      <!-- Status Note -->
      <?php if ($reservation['status'] === 'Pending'): ?>
        <div class="info-note">
          <strong>Pending Confirmation</strong>
          Your reservation is awaiting confirmation. Our staff will contact you shortly.
        </div>
      <?php elseif ($reservation['status'] === 'Confirmed'): ?>
        <div class="info-note">
          <strong>Reservation Confirmed</strong>
          Your reservation has been confirmed. Please arrive on time.
        </div>
      <?php elseif ($reservation['status'] === 'Cancelled'): ?>
        <div class="info-note" style="background: #fff3cd; border-left-color: #ffc107;">
          <strong style="color: #856404;">Reservation Cancelled</strong>
          This reservation has been cancelled.
        </div>
      <?php endif; ?>

      <!-- Table/Room Information -->
      <div class="section-spacing">
        <h3 class="section-title">Table/Room Information</h3>
        <div class="info-row">
          <div class="info-label">Table Type:</div>
          <div class="info-value"><?= htmlspecialchars($reservation['table_type']) ?></div>
        </div>
        <div class="info-row">
          <div class="info-label">Table Number:</div>
          <div class="info-value"><?= htmlspecialchars($reservation['table_number']) ?></div>
        </div>
        <div class="info-row">
          <div class="info-label">Capacity:</div>
          <div class="info-value"><?= htmlspecialchars($reservation['capacity']) ?> guests</div>
        </div>
        <?php if ($reservation['table_description']): ?>
          <div class="info-row">
            <div class="info-label">Description:</div>
            <div class="info-value"><?= htmlspecialchars($reservation['table_description']) ?></div>
          </div>
        <?php endif; ?>
      </div>

      <!-- Reservation Details -->
      <div class="section-spacing">
        <h3 class="section-title">Reservation Information</h3>
        <div class="info-row">
          <div class="info-label">Event Type:</div>
          <div class="info-value"><?= htmlspecialchars($reservation['event_type']) ?></div>
        </div>
        <div class="info-row">
          <div class="info-label">Date:</div>
          <div class="info-value"><?= date('F d, Y', strtotime($reservation['reservation_date'])) ?></div>
        </div>
        <div class="info-row">
          <div class="info-label">Time:</div>
          <div class="info-value"><?= date('h:i A', strtotime($reservation['reservation_time'])) ?></div>
        </div>
        <div class="info-row">
          <div class="info-label">Duration:</div>
          <div class="info-value"><?= htmlspecialchars($reservation['total_hours']) ?> hour<?= $reservation['total_hours'] > 1 ? 's' : '' ?></div>
        </div>
      </div>

      <!-- Pricing Information -->
      <div class="section-spacing">
        <h3 class="section-title">Pricing Details</h3>
        <div class="info-row">
          <div class="info-label">Price per Hour:</div>
          <div class="info-value">
            <?= $reservation['price_per_hour'] > 0 ? '₱' . number_format($reservation['price_per_hour'], 2) : 'Free' ?>
          </div>
        </div>
        <div class="info-row">
          <div class="info-label">Total Hours:</div>
          <div class="info-value"><?= htmlspecialchars($reservation['total_hours']) ?> hour<?= $reservation['total_hours'] > 1 ? 's' : '' ?></div>
        </div>
        <div class="info-row">
          <div class="info-label">Total Price:</div>
          <div class="info-value price-highlight">
            <?= $reservation['total_price'] > 0 ? '₱' . number_format($reservation['total_price'], 2) : 'Free' ?>
          </div>
        </div>
      </div>

      <!-- Customer Information -->
      <div class="section-spacing">
        <h3 class="section-title">Contact Information</h3>
        <div class="info-row">
          <div class="info-label">Full Name:</div>
          <div class="info-value"><?= htmlspecialchars(trim($reservation['first_name'] . ' ' . $reservation['middle_name'] . ' ' . $reservation['last_name'])) ?></div>
        </div>
        <div class="info-row">
          <div class="info-label">Email:</div>
          <div class="info-value"><?= htmlspecialchars($reservation['email']) ?></div>
        </div>
        <div class="info-row">
          <div class="info-label">Phone:</div>
          <div class="info-value"><?= htmlspecialchars($reservation['phone_number']) ?></div>
        </div>
      </div>

      <!-- Action Buttons -->
      <?php if ($is_cancellable): ?>
        <div class="action-buttons">
          <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this reservation?');">
            <input type="hidden" name="cancel_reservation" value="1">
            <button type="submit" class="cancel-btn">Cancel Reservation</button>
          </form>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <section class="banner">
    <img src="pictures/bg.jpg" alt="bg Kyla's Bistro" />
  </section>
</body>
</html>
<?php $conn->close(); ?>