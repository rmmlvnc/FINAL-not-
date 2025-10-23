<?php
session_start();
include("database.php");

// Check if order_id is provided
if (!isset($_GET['order_id']) || !isset($_SESSION['last_order'])) {
  header("Location: index.php");
  exit();
}

$order_id = $_GET['order_id'];
$order = $_SESSION['last_order'];

// Verify order_id matches
if ($order['order_id'] != $order_id) {
  header("Location: index.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Order Receipt | Kyla's Bistro</title>
  <link rel="stylesheet" href="style.css" />
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #f9f6f2;
      color: #2c1810;
      margin: 0;
      padding: 20px;
      min-height: 100vh;
    }

    .container {
      max-width: 800px;
      margin: 0 auto;
    }

    .success-header {
      text-align: center;
      margin-bottom: 20px;
      background: white;
      padding: 25px;
      border: 1px solid #ddd;
    }

    .success-icon {
      width: 60px;
      height: 60px;
      background: #28a745;
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      color: white;
      margin-bottom: 10px;
    }

    .success-title {
      font-size: 24px;
      color: #2c1810;
      margin-bottom: 5px;
      font-weight: 600;
    }

    .success-subtitle {
      color: #666;
      font-size: 14px;
    }

    .receipt-card {
      background: white;
      border: 1px solid #ddd;
      overflow: hidden;
    }

    .receipt-header {
      background: #2c1810;
      color: white;
      padding: 25px;
      text-align: center;
    }

    .receipt-header h1 {
      font-size: 28px;
      margin-bottom: 5px;
    }

    .receipt-header p {
      margin-bottom: 10px;
      font-size: 14px;
    }

    .receipt-number {
      font-size: 16px;
      font-weight: 600;
      background: #d4a574;
      color: #2c1810;
      display: inline-block;
      padding: 8px 20px;
      margin-top: 8px;
    }

    .order-id-badge {
      display: inline-block;
      background: rgba(212, 165, 116, 0.3);
      padding: 6px 15px;
      margin-top: 5px;
      font-size: 14px;
    }

    .receipt-body {
      padding: 25px;
    }

    .info-section {
      margin-bottom: 25px;
    }

    .section-title {
      font-size: 18px;
      font-weight: 600;
      color: #2c1810;
      margin-bottom: 12px;
      padding-bottom: 8px;
      border-bottom: 2px solid #ddd;
    }

    .info-grid {
      display: grid;
      gap: 8px;
    }

    .info-row {
      display: flex;
      justify-content: space-between;
      padding: 10px;
      background: #f9f9f9;
      font-size: 14px;
    }

    .info-label {
      font-weight: 600;
      color: #2c1810;
    }

    .info-value {
      color: #2c1810;
      text-align: right;
    }

    .status-badge {
      display: inline-block;
      padding: 4px 12px;
      font-size: 12px;
      font-weight: 600;
      background: #ffc107;
      color: #856404;
    }

    .address-box {
      background: #f9f9f9;
      padding: 12px;
      color: #2c1810;
      line-height: 1.6;
      border-left: 3px solid #2c1810;
    }

    .order-items-list {
      margin-bottom: 15px;
    }

    .item-card {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px;
      background: #f9f9f9;
      margin-bottom: 8px;
      border-left: 3px solid #2c1810;
    }

    .item-details {
      flex: 1;
    }

    .item-name {
      font-weight: 600;
      color: #2c1810;
      margin-bottom: 4px;
      font-size: 15px;
    }

    .item-qty {
      font-size: 13px;
      color: #666;
    }

    .item-price {
      font-weight: 600;
      color: #2c1810;
      font-size: 16px;
    }

    .total-section {
      background: #f9f9f9;
      padding: 15px;
      margin-top: 15px;
      border: 2px solid #ddd;
    }

    .total-row {
      display: flex;
      justify-content: space-between;
      padding: 8px 0;
      font-size: 15px;
    }

    .total-row.grand {
      font-size: 20px;
      font-weight: 600;
      color: #2c1810;
      padding-top: 12px;
      margin-top: 8px;
      border-top: 2px solid #2c1810;
    }

    .alert-box {
      background: #fff3cd;
      border: 2px solid #ffc107;
      padding: 12px;
      margin-top: 15px;
    }

    .alert-box strong {
      display: block;
      margin-bottom: 6px;
      font-size: 15px;
      color: #856404;
    }

    .alert-box p {
      font-size: 13px;
      line-height: 1.5;
      color: #856404;
    }

    .btn-home {
      display: block;
      width: 100%;
      padding: 12px 24px;
      border: none;
      background: #2c1810;
      color: white;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: background-color 0.3s ease;
      text-align: center;
      text-decoration: none;
      margin-top: 20px;
    }

    .btn-home:hover {
      background: #3d2417;
    }

    @media (max-width: 576px) {

      .success-title {
        font-size: 24px;
      }

      .receipt-header h1 {
        font-size: 26px;
      }

      .receipt-number {
        font-size: 16px;
      }

      .receipt-body {
        padding: 20px;
      }

      .item-card {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="success-header">
      <div class="success-icon">✓</div>
      <h1 class="success-title">Order Placed Successfully!</h1>
      <p class="success-subtitle">Thank you for your order</p>
    </div>

    <div class="receipt-card">
      <div class="receipt-header">
        <h1>Kyla's Bistro</h1>
        <p>Official Order Receipt</p>
        <div class="order-id-badge">Order ID: #<?= htmlspecialchars($order['order_id']) ?></div>
        <div class="receipt-number"><?= htmlspecialchars($order['receipt_number']) ?></div>
      </div>

      <div class="receipt-body">
        <!-- Customer Information -->
        <div class="info-section">
          <h2 class="section-title">Customer Information</h2>
          <div class="info-grid">
            <div class="info-row">
              <span class="info-label">Name:</span>
              <span class="info-value"><?= htmlspecialchars($order['customer_name']) ?></span>
            </div>
            <div class="info-row">
              <span class="info-label">Email:</span>
              <span class="info-value"><?= htmlspecialchars($order['customer_email']) ?></span>
            </div>
            <div class="info-row">
              <span class="info-label">Phone:</span>
              <span class="info-value"><?= htmlspecialchars($order['customer_phone']) ?></span>
            </div>
          </div>
        </div>

        <!-- Order Details -->
        <div class="info-section">
          <h2 class="section-title">Order Details</h2>
          <div class="info-grid">
            <div class="info-row">
              <span class="info-label">Order Date:</span>
              <span class="info-value"><?= date('F j, Y') ?></span>
            </div>
            <div class="info-row">
              <span class="info-label">Order Time:</span>
              <span class="info-value"><?= date('g:i A') ?></span>
            </div>
            <div class="info-row">
              <span class="info-label">Payment Method:</span>
              <span class="info-value"><?= htmlspecialchars($order['payment_method']) ?></span>
            </div>
            <div class="info-row">
              <span class="info-label">Status:</span>
              <span class="info-value"><span class="status-badge">Pending</span></span>
            </div>
          </div>
        </div>

        <!-- Delivery Address -->
        <div class="info-section">
          <h2 class="section-title">Delivery Address</h2>
          <div class="address-box">
            <?= nl2br(htmlspecialchars($order['delivery_address'])) ?>
          </div>
        </div>

        <!-- Order Items -->
        <div class="info-section">
          <h2 class="section-title">Order Items</h2>
          <div class="order-items-list">
            <?php foreach ($order['items'] as $item): ?>
              <div class="item-card">
                <div class="item-details">
                  <div class="item-name"><?= htmlspecialchars($item['product_name']) ?></div>
                  <div class="item-qty">
                    Quantity: <?= $item['quantity'] ?> × ₱<?= number_format($item['price'], 2) ?>
                  </div>
                </div>
                <div class="item-price">
                  ₱<?= number_format($item['price'] * $item['quantity'], 2) ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Total Section -->
        <div class="total-section">
          <div class="total-row">
            <span>Subtotal:</span>
            <strong>₱<?= number_format($order['total'], 2) ?></strong>
          </div>
          <div class="total-row">
            <span>Delivery Fee:</span>
            <strong>₱0.00</strong>
          </div>
          <div class="total-row grand">
            <span>Grand Total:</span>
            <strong>₱<?= number_format($order['total'], 2) ?></strong>
          </div>
        </div>

        <div class="alert-box">
          <strong>Important Notice</strong>
          <p>
            Your order is currently pending. We will contact you shortly to confirm your order.
            <?php if ($order['payment_method'] === 'Cash on Delivery'): ?>
              Please prepare the exact amount (₱<?= number_format($order['total'], 2) ?>) for faster processing.
            <?php endif; ?>
            Keep this receipt number for reference: <strong><?= htmlspecialchars($order['receipt_number']) ?></strong>
          </p>
        </div>

        <!-- Action Button -->
        <a href="index.php" class="btn-home">
          Back to Home
        </a>
      </div>
    </div>
  </div>
</body>
</html>