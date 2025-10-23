<?php
session_start();
include 'database.php';

if (!isset($_SESSION['staff_id'])) {
  header("Location: staff_login.php");
  exit();
}

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $order_items = $_POST['order_items'] ?? [];
  $new_total = 0;
  
  // Update each order item
  foreach ($order_items as $item_id => $item_data) {
    $quantity = intval($item_data['quantity']);
    $product_id = intval($item_data['product_id']);
    
    // Get product price
    $price_stmt = $conn->prepare("SELECT price FROM product WHERE product_id = ?");
    $price_stmt->bind_param("i", $product_id);
    $price_stmt->execute();
    $price_result = $price_stmt->get_result();
    $product = $price_result->fetch_assoc();
    $price_stmt->close();
    
    $total_price = $quantity * $product['price'];
    $new_total += $total_price;
    
    if ($quantity > 0) {
      // Update order item
      $update_stmt = $conn->prepare("UPDATE order_item SET quantity = ?, total_price = ? WHERE order_item_id = ?");
      $update_stmt->bind_param("idi", $quantity, $total_price, $item_id);
      $update_stmt->execute();
      $update_stmt->close();
    } else {
      // Delete order item if quantity is 0
      $delete_stmt = $conn->prepare("DELETE FROM order_item WHERE order_item_id = ?");
      $delete_stmt->bind_param("i", $item_id);
      $delete_stmt->execute();
      $delete_stmt->close();
    }
  }
  
  // Add new items if any
  if (isset($_POST['new_product']) && isset($_POST['new_quantity'])) {
    $new_product = intval($_POST['new_product']);
    $new_quantity = intval($_POST['new_quantity']);
    
    if ($new_product > 0 && $new_quantity > 0) {
      // Get product price
      $price_stmt = $conn->prepare("SELECT price FROM product WHERE product_id = ?");
      $price_stmt->bind_param("i", $new_product);
      $price_stmt->execute();
      $price_result = $price_stmt->get_result();
      $product = $price_result->fetch_assoc();
      $price_stmt->close();
      
      $total_price = $new_quantity * $product['price'];
      $new_total += $total_price;
      
      // Insert new order item
      $insert_stmt = $conn->prepare("INSERT INTO order_item (order_id, product_id, quantity, total_price) VALUES (?, ?, ?, ?)");
      $insert_stmt->bind_param("iiid", $order_id, $new_product, $new_quantity, $total_price);
      $insert_stmt->execute();
      $insert_stmt->close();
    }
  }
  
  // Update order total
  $update_order = $conn->prepare("UPDATE orders SET total_amount = ? WHERE order_id = ?");
  $update_order->bind_param("di", $new_total, $order_id);
  $update_order->execute();
  $update_order->close();
  
  $_SESSION['success_message'] = "Order updated successfully!";
  header("Location: staff.php");
  exit();
}

// Fetch order details
$order_stmt = $conn->prepare("
  SELECT o.*, c.first_name, c.last_name, c.phone_number
  FROM `orders` o
  LEFT JOIN `customer` c ON o.customer_id = c.customer_id
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

// Fetch order items
$items_stmt = $conn->prepare("
  SELECT oi.*, p.product_name, p.price
  FROM order_item oi
  LEFT JOIN product p ON oi.product_id = p.product_id
  WHERE oi.order_id = ?
");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();

// Fetch all products for adding new items
$products_result = $conn->query("SELECT product_id, product_name, price, stock_quantity FROM product WHERE stock_quantity > 0");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Edit Order #<?= $order_id ?></title>
  <link rel="stylesheet" href="staff_style.css">
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>Edit Order #<?= htmlspecialchars($order_id) ?></h1>
      <a href="staff.php" class="btn">← Back to Dashboard</a>
    </div>

    <div class="order-info">
      <p><strong>Customer:</strong> <?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></p>
      <p><strong>Contact:</strong> <?= htmlspecialchars($order['phone_number']) ?></p>
      <p><strong>Order Date:</strong> <?= htmlspecialchars($order['order_date']) ?></p>
      <p><strong>Order Time:</strong> <?= htmlspecialchars($order['order_time']) ?></p>
    </div>

    <form method="POST">
      <h2>Order Items</h2>
      <table>
        <tr>
          <th>Product</th>
          <th>Unit Price</th>
          <th>Quantity</th>
          <th>Total Price</th>
        </tr>
        <?php 
        $calculated_total = 0;
        while ($item = $items_result->fetch_assoc()): 
          $calculated_total += $item['total_price'];
        ?>
          <tr>
            <td><?= htmlspecialchars($item['product_name']) ?></td>
            <td>₱<?= number_format($item['price'], 2) ?></td>
            <td>
              <input type="hidden" name="order_items[<?= $item['order_item_id'] ?>][product_id]" value="<?= $item['product_id'] ?>">
              <input type="number" name="order_items[<?= $item['order_item_id'] ?>][quantity]" value="<?= $item['quantity'] ?>" min="0" required>
              <small style="color: #666;">(Set to 0 to remove)</small>
            </td>
            <td>₱<?= number_format($item['total_price'], 2) ?></td>
          </tr>
        <?php endwhile; ?>
      </table>

      <div class="total">
        Current Total: ₱<?= number_format($calculated_total, 2) ?>
      </div>

      <div class="add-item">
        <h3>Add New Item</h3>
        <select name="new_product">
          <option value="0">-- Select Product --</option>
          <?php while ($prod = $products_result->fetch_assoc()): ?>
            <option value="<?= $prod['product_id'] ?>">
              <?= htmlspecialchars($prod['product_name']) ?> - ₱<?= number_format($prod['price'], 2) ?> (Stock: <?= $prod['stock_quantity'] ?>)
            </option>
          <?php endwhile; ?>
        </select>
        <input type="number" name="new_quantity" placeholder="Quantity" min="0" value="0">
      </div>

      <div class="form-actions">
        <a href="staff.php" class="btn btn-danger">Cancel</a>
        <button type="submit" class="btn btn-success">Save Changes</button>
      </div>
    </form>
  </div>
</body>
</html>