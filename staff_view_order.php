<?php
session_start();
include 'database.php';

if (!isset($_SESSION['staff_id'])) {
  header("Location: staff_login.php");
  exit();
}

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch order details with customer info
$order_stmt = $conn->prepare("
  SELECT o.*, c.first_name, c.last_name, c.phone_number, c.email
  FROM `orders` o
  LEFT JOIN customer c ON o.customer_id = c.customer_id
  WHERE o.order_id = ?
");
$order_stmt->bind_param("i", $order_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();
$order = $order_result->fetch_assoc();
$order_stmt->close();

if (!$order) {
  header("Location: staff.php");
  exit();
}

// Fetch order items with product details
$items_stmt = $conn->prepare("
  SELECT 
    oi.order_item_id,
    oi.quantity,
    oi.total_price,
    p.product_name,
    p.price,
    p.image,
    p.description
  FROM order_item oi
  LEFT JOIN product p ON oi.product_id = p.product_id
  WHERE oi.order_id = ?
");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order #<?= $order_id ?> - Details</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    
    body { 
      font-family: Arial, sans-serif; 
      background: #f5f5f5;
      padding: 20px;
    }
    
    .container {
      max-width: 900px;
      margin: 0 auto;
    }
    
    .header {
      background: white;
      padding: 20px;
      margin-bottom: 20px;
      border: 1px solid #ddd;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .header h1 {
      font-size: 24px;
      color: #333;
    }
    
    .btn {
      padding: 8px 16px;
      background: #666;
      color: white;
      border: none;
      text-decoration: none;
      font-size: 14px;
      margin-left: 10px;
      display: inline-block;
    }

    .btn:hover {
      background: #555;
    }

    .btn-edit {
      background: #4CAF50;
    }

    .btn-edit:hover {
      background: #45a049;
    }

    .content-box {
      background: white;
      padding: 25px;
      margin-bottom: 20px;
      border: 1px solid #ddd;
    }
    
    .order-info {
      margin-bottom: 25px;
      padding-bottom: 20px;
      border-bottom: 2px solid #eee;
    }
    
    .order-info h2 {
      font-size: 28px;
      color: #333;
      margin-bottom: 10px;
    }
    
    .order-meta {
      color: #666;
      font-size: 14px;
    }
    
    .section-title {
      font-size: 18px;
      color: #333;
      margin-bottom: 15px;
      font-weight: bold;
    }
    
    .info-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 15px;
      margin-bottom: 30px;
    }
    
    .info-item {
      padding: 12px;
      background: #f9f9f9;
      border-left: 3px solid #666;
    }
    
    .info-label {
      font-size: 12px;
      color: #666;
      margin-bottom: 5px;
      text-transform: uppercase;
    }
    
    .info-value {
      font-size: 16px;
      color: #333;
    }
    
    .item-row {
      padding: 15px;
      margin-bottom: 10px;
      background: #fafafa;
      border: 1px solid #eee;
      display: flex;
      gap: 15px;
      align-items: center;
    }
    
    .item-image {
      width: 60px;
      height: 60px;
      object-fit: cover;
      border: 1px solid #ddd;
    }
    
    .item-info {
      flex: 1;
    }
    
    .item-name {
      font-size: 16px;
      font-weight: bold;
      color: #333;
      margin-bottom: 5px;
    }
    
    .item-desc {
      font-size: 13px;
      color: #666;
    }
    
    .item-price {
      font-size: 14px;
      color: #666;
      text-align: right;
      min-width: 100px;
    }
    
    .item-qty {
      font-size: 16px;
      color: #333;
      text-align: center;
      min-width: 60px;
    }
    
    .item-total {
      font-size: 16px;
      font-weight: bold;
      color: #333;
      text-align: right;
      min-width: 120px;
    }
    
    .total-box {
      background: #333;
      color: white;
      padding: 20px;
      text-align: right;
      margin-top: 20px;
    }
    
    .total-box .label {
      font-size: 14px;
      margin-bottom: 5px;
    }
    
    .total-box .amount {
      font-size: 28px;
      font-weight: bold;
    }
    
    .empty {
      text-align: center;
      padding: 40px;
      color: #999;
    }
    
    @media print {
      body { background: white; }
      .header .btn { display: none; }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>Order Details</h1>
      <div>
        <a href="staff.php" class="btn">Back</a>
        <a href="staff_edit_order.php?id=<?= $order_id ?>" class="btn btn-edit">Edit Order</a>
      </div>
    </div>

    <div class="content-box">
      <div class="order-info">
        <h2>Order #<?= htmlspecialchars($order_id) ?></h2>
        <div class="order-meta">
          <?= date('F d, Y', strtotime($order['order_date'])) ?> at <?= date('h:i A', strtotime($order['order_time'])) ?>
        </div>
      </div>

      <div class="section-title">Customer Information</div>
      <div class="info-grid">
        <div class="info-item">
          <div class="info-label">Customer Name</div>
          <div class="info-value"><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></div>
        </div>
        <div class="info-item">
          <div class="info-label">Phone Number</div>
          <div class="info-value"><?= htmlspecialchars($order['phone_number']) ?></div>
        </div>
        <div class="info-item">
          <div class="info-label">Email Address</div>
          <div class="info-value"><?= htmlspecialchars($order['email']) ?></div>
        </div>
      </div>

      <div class="section-title">Order Items</div>
      
      <?php if ($items_result && $items_result->num_rows > 0): ?>
        <?php while ($item = $items_result->fetch_assoc()): ?>
          <div class="item-row">
            <img src="uploads/<?= htmlspecialchars($item['image']) ?>" 
                 alt="<?= htmlspecialchars($item['product_name']) ?>" 
                 class="item-image">
            
            <div class="item-info">
              <div class="item-name"><?= htmlspecialchars($item['product_name']) ?></div>
              <div class="item-desc"><?= htmlspecialchars($item['description']) ?></div>
            </div>
            
            <div class="item-price">
              ₱<?= number_format($item['price'], 2) ?>
            </div>
            
            <div class="item-qty">
              × <?= htmlspecialchars($item['quantity']) ?>
            </div>
            
            <div class="item-total">
              ₱<?= number_format($item['total_price'], 2) ?>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="empty">No items in this order</div>
      <?php endif; ?>

      <div class="total-box">
        <div class="label">Total Amount</div>
        <div class="amount">₱<?= number_format($order['total_amount'], 2) ?></div>
      </div>
    </div>
  </div>
</body>
</html>