<?php
session_start();
include 'database.php';

// Check if customer is logged in
if (!isset($_SESSION['customer_id'])) {
  header("Location: login.php");
  exit();
}

$customer_id = $_SESSION['customer_id'];
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id <= 0) {
  header("Location: profile.php");
  exit();
}

// Fetch order details
$order_stmt = $conn->prepare("
  SELECT 
    o.order_id,
    o.order_date,
    o.order_time,
    o.total_amount,
    c.first_name,
    c.last_name,
    c.email,
    c.phone_number,
    c.address
  FROM `orders` o
  JOIN customer c ON o.customer_id = c.customer_id
  WHERE o.order_id = ? AND o.customer_id = ?
");
$order_stmt->bind_param("ii", $order_id, $customer_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();
$order = $order_result->fetch_assoc();
$order_stmt->close();

if (!$order) {
  header("Location: profile.php");
  exit();
}

// Fetch order items with product details
$items_stmt = $conn->prepare("
  SELECT 
    oi.order_item_id,
    oi.quantity,
    oi.total_price,
    p.product_name,
    p.description,
    p.price as unit_price,
    p.image,
    c.category_name
  FROM order_item oi
  JOIN product p ON oi.product_id = p.product_id
  JOIN category c ON p.category_id = c.category_id
  WHERE oi.order_id = ?
  ORDER BY c.category_name, p.product_name
");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
$items_stmt->close();

// Fetch payment information
$payment_stmt = $conn->prepare("
  SELECT 
    payment_id,
    payment_date,
    payment_time,
    payment_method,
    payment_status,
    total_amount
  FROM payment
  WHERE order_id = ?
");
$payment_stmt->bind_param("i", $order_id);
$payment_stmt->execute();
$payment_result = $payment_stmt->get_result();
$payment = $payment_result->fetch_assoc();
$payment_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order Details #<?= $order_id ?> - Kyla's Bistro</title>
  <link rel="stylesheet" href="style.css" />
  <style>
    .order-container {
      max-width: 1000px;
      margin: 40px auto;
      padding: 20px;
    }
    
    .page-title {
      font-size: 28px;
      margin-bottom: 10px;
      color: #333;
    }
    
    .order-subtitle {
      font-size: 14px;
      color: #666;
      margin-bottom: 30px;
    }
    
    .back-btn {
      background: #6c757d;
      color: white;
      padding: 8px 16px;
      border-radius: 4px;
      text-decoration: none;
      display: inline-block;
      margin-bottom: 20px;
      font-size: 14px;
    }
    
    .back-btn:hover {
      background: #5a6268;
    }
    
    .order-grid {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 25px;
      margin-bottom: 25px;
    }
    
    .order-section {
      background: white;
      border: 1px solid #ddd;
      border-radius: 6px;
      padding: 25px;
    }
    
    .section-title {
      font-size: 18px;
      font-weight: 600;
      color: #333;
      margin-bottom: 20px;
      padding-bottom: 12px;
      border-bottom: 2px solid #8b4513;
    }
    
    .info-row {
      display: flex;
      justify-content: space-between;
      padding: 10px 0;
      border-bottom: 1px solid #f5f5f5;
    }
    
    .info-row:last-child {
      border-bottom: none;
    }
    
    .info-label {
      font-weight: 600;
      color: #666;
    }
    
    .info-value {
      color: #333;
      text-align: right;
    }
    
    .order-items {
      background: white;
      border: 1px solid #ddd;
      border-radius: 6px;
      padding: 25px;
    }
    
    .item-card {
      display: grid;
      grid-template-columns: 80px 1fr auto;
      gap: 15px;
      padding: 15px;
      border: 1px solid #eee;
      border-radius: 6px;
      margin-bottom: 15px;
      transition: all 0.3s ease;
    }
    
    .item-card:hover {
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      transform: translateY(-2px);
    }
    
    .item-image {
      width: 80px;
      height: 80px;
      object-fit: cover;
      border-radius: 6px;
      border: 1px solid #ddd;
    }
    
    .item-details {
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    
    .item-name {
      font-size: 16px;
      font-weight: 600;
      color: #333;
      margin-bottom: 5px;
    }
    
    .item-category {
      font-size: 12px;
      color: #999;
      margin-bottom: 8px;
      text-transform: uppercase;
    }
    
    .item-description {
      font-size: 13px;
      color: #666;
      line-height: 1.4;
    }
    
    .item-pricing {
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      justify-content: center;
      min-width: 120px;
    }
    
    .item-quantity {
      font-size: 13px;
      color: #666;
      margin-bottom: 5px;
    }
    
    .item-unit-price {
      font-size: 12px;
      color: #999;
      margin-bottom: 8px;
    }
    
    .item-total {
      font-size: 18px;
      font-weight: 700;
      color: #8b4513;
    }
    
    .order-summary {
      background: #f8f9fa;
      padding: 20px;
      border-radius: 6px;
      margin-top: 20px;
    }
    
    .summary-row {
      display: flex;
      justify-content: space-between;
      padding: 10px 0;
      font-size: 15px;
    }
    
    .summary-row.total {
      border-top: 2px solid #dee2e6;
      margin-top: 10px;
      padding-top: 15px;
      font-size: 20px;
      font-weight: 700;
      color: #8b4513;
    }
    
    .status-badge {
      display: inline-block;
      padding: 6px 12px;
      border-radius: 4px;
      font-size: 13px;
      font-weight: 600;
    }
    
    .status-pending {
      background-color: #fff3cd;
      color: #856404;
    }
    
    .status-paid {
      background-color: #d4edda;
      color: #155724;
    }
    
    .payment-method {
      background: #e7f3ff;
      color: #004085;
      padding: 6px 12px;
      border-radius: 4px;
      font-size: 13px;
      font-weight: 600;
      display: inline-block;
    }
    
    .empty-image {
      width: 80px;
      height: 80px;
      background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
      border-radius: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
      color: #999;
      text-align: center;
      padding: 5px;
    }
    
    
    @media (max-width: 768px) {
      .order-grid {
        grid-template-columns: 1fr;
      }
      
      .item-card {
        grid-template-columns: 60px 1fr;
        gap: 10px;
      }
      
      .item-image, .empty-image {
        width: 60px;
        height: 60px;
      }
      
      .item-pricing {
        grid-column: 2;
        align-items: flex-start;
        margin-top: 10px;
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

  <div class="order-container">
    <a href="profile.php" class="back-btn">← Back to Profile</a>
    
    <h1 class="page-title">Order #<?= htmlspecialchars($order['order_id']) ?></h1>
    <p class="order-subtitle">
      Placed on <?= date('F d, Y', strtotime($order['order_date'])) ?> at <?= date('h:i A', strtotime($order['order_time'])) ?>
    </p>

    <div class="order-grid">
      <!-- Customer Information -->
      <div class="order-section">
        <h2 class="section-title">Customer Information</h2>
        <div class="info-row">
          <span class="info-label">Name:</span>
          <span class="info-value"><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">Email:</span>
          <span class="info-value"><?= htmlspecialchars($order['email']) ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">Phone:</span>
          <span class="info-value"><?= htmlspecialchars($order['phone_number']) ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">Address:</span>
          <span class="info-value"><?= htmlspecialchars($order['address']) ?></span>
        </div>
      </div>

      <!-- Payment Information -->
      <div class="order-section">
        <h2 class="section-title">Payment Information</h2>
        <?php if ($payment): ?>
          <div class="info-row">
            <span class="info-label">Payment ID:</span>
            <span class="info-value">#<?= htmlspecialchars($payment['payment_id']) ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">Method:</span>
            <span class="info-value">
              <span class="payment-method"><?= htmlspecialchars($payment['payment_method']) ?></span>
            </span>
          </div>
          <div class="info-row">
            <span class="info-label">Status:</span>
            <span class="info-value">
              <span class="status-badge status-<?= strtolower($payment['payment_status']) ?>">
                <?= htmlspecialchars($payment['payment_status']) ?>
              </span>
            </span>
          </div>
          <div class="info-row">
            <span class="info-label">Payment Date:</span>
            <span class="info-value">
              <?= date('M d, Y', strtotime($payment['payment_date'])) ?><br>
              <?= date('h:i A', strtotime($payment['payment_time'])) ?>
            </span>
          </div>
        <?php else: ?>
          <p style="color: #999; text-align: center; padding: 20px 0;">Payment information not available</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Order Items -->
    <div class="order-items">
      <h2 class="section-title">Order Items</h2>
      
      <?php if ($items_result && $items_result->num_rows > 0): ?>
        <?php while ($item = $items_result->fetch_assoc()): ?>
          <div class="item-card">
            <div>
              <?php if (!empty($item['image']) && file_exists('uploads/' . $item['image'])): ?>
                <img src="uploads/<?= htmlspecialchars($item['image']) ?>" 
                     alt="<?= htmlspecialchars($item['product_name']) ?>" 
                     class="item-image">
              <?php else: ?>
                <div class="empty-image">No Image</div>
              <?php endif; ?>
            </div>
            
            <div class="item-details">
              <div class="item-category"><?= htmlspecialchars($item['category_name']) ?></div>
              <div class="item-name"><?= htmlspecialchars($item['product_name']) ?></div>
              <div class="item-description">
                <?= htmlspecialchars(substr($item['description'], 0, 100)) ?>
                <?= strlen($item['description']) > 100 ? '...' : '' ?>
              </div>
            </div>
            
            <div class="item-pricing">
              <div class="item-quantity">Qty: <?= htmlspecialchars($item['quantity']) ?></div>
              <div class="item-unit-price">₱<?= number_format($item['unit_price'], 2) ?> each</div>
              <div class="item-total">₱<?= number_format($item['total_price'], 2) ?></div>
            </div>
          </div>
        <?php endwhile; ?>
        
        <div class="order-summary">
          <div class="summary-row total">
            <span>Total Amount:</span>
            <span>₱<?= number_format($order['total_amount'], 2) ?></span>
          </div>
        </div>
        
      <?php else: ?>
        <p style="text-align: center; color: #999; padding: 40px 0;">No items found in this order</p>
      <?php endif; ?>
    </div>
  </div>

  <section class="banner">
    <img src="pictures/bg.jpg" alt="bg Kyla's Bistro" />
  </section>
</body>
</html>