<?php
session_start();
include("database.php");

if (!isset($_SESSION['username'])) {
  header("Location: login.php");
  exit();
}

// Get customer ID
$cust_stmt = $conn->prepare("SELECT customer_id FROM customer WHERE username = ?");
if ($cust_stmt === false) {
  die("Error preparing customer query: " . $conn->error);
}

$cust_stmt->bind_param("s", $_SESSION['username']);
$cust_stmt->execute();
$cust_result = $cust_stmt->get_result();
$cust_row = $cust_result->fetch_assoc();

if (!$cust_row) {
  die("Customer not found");
}

$customer_id = $cust_row['customer_id'];
$cust_stmt->close();

// Get cart from session
$cart = $_SESSION['cart'] ?? [];

// Fetch fresh product data for items in cart
$cart_items = [];
$subtotal = 0;

foreach ($cart as $product_id => $item) {
  $prod_stmt = $conn->prepare("SELECT product_name, price, image, stock_quantity FROM product WHERE product_id = ?");
  $prod_stmt->bind_param("i", $product_id);
  $prod_stmt->execute();
  $prod_result = $prod_stmt->get_result();
  $product = $prod_result->fetch_assoc();
  $prod_stmt->close();
  
  if ($product) {
    $cart_items[$product_id] = [
      'product_name' => $product['product_name'],
      'price' => (float)$product['price'],
      'image' => $product['image'],
      'quantity' => (int)$item['quantity'],
      'stock_quantity' => (int)$product['stock_quantity']
    ];
    $subtotal += $product['price'] * $item['quantity'];
  }
}

$total = $subtotal;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Your Cart | Kyla's Bistro</title>
  <link rel="stylesheet" href="style.css" />
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f5f5f5;
      margin: 0;
      padding: 0;
    }

    .cart-wrapper {
      max-width: 1000px;
      margin: 0 auto;
      padding: 30px 20px;
    }

    .cart-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
    }

    .cart-title {
      font-size: 24px;
      color: #333;
      margin: 0;
    }

    .back-link {
      padding: 8px 16px;
      background-color: #007bff;
      color: white;
      text-decoration: none;
      border-radius: 3px;
    }

    .back-link:hover {
      background-color: #0056b3;
    }

    .cart-content {
      display: grid;
      grid-template-columns: 1fr 320px;
      gap: 20px;
    }

    .cart-items {
      background: white;
      border: 1px solid #ddd;
      border-radius: 4px;
      padding: 20px;
    }

    .cart-item {
      display: flex;
      gap: 15px;
      padding: 15px 0;
      border-bottom: 1px solid #eee;
    }

    .cart-item:last-child {
      border-bottom: none;
    }

    .item-image {
      width: 80px;
      height: 80px;
      object-fit: cover;
      border-radius: 3px;
      border: 1px solid #ddd;
    }

    .item-details {
      flex: 1;
    }

    .item-name {
      font-size: 16px;
      font-weight: 600;
      color: #333;
      margin-bottom: 5px;
    }

    .item-price {
      font-size: 14px;
      color: #666;
      margin-bottom: 10px;
    }

    .item-quantity-section {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-top: 10px;
    }

    .quantity-display {
      font-size: 14px;
      color: #666;
    }

    .quantity-edit-form {
      display: none;
      align-items: center;
      gap: 5px;
    }

    .quantity-edit-form.active {
      display: flex;
    }

    .quantity-btn {
      width: 24px;
      height: 24px;
      border: 1px solid #ddd;
      background: white;
      cursor: pointer;
      font-size: 14px;
    }

    .quantity-btn:hover {
      background: #f0f0f0;
    }

    .quantity-input {
      width: 50px;
      padding: 4px;
      border: 1px solid #ddd;
      border-radius: 3px;
      text-align: center;
    }

    .item-actions {
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      gap: 10px;
    }

    .item-total {
      font-size: 16px;
      font-weight: 600;
      color: #333;
    }

    .btn-group {
      display: flex;
      gap: 5px;
    }

    .btn {
      padding: 5px 10px;
      border: none;
      border-radius: 3px;
      font-size: 13px;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
    }

    .btn-edit {
      background: #17a2b8;
      color: white;
    }

    .btn-edit:hover {
      background: #138496;
    }

    .btn-save {
      background: #28a745;
      color: white;
    }

    .btn-save:hover {
      background: #218838;
    }

    .btn-cancel {
      background: #6c757d;
      color: white;
    }

    .btn-cancel:hover {
      background: #5a6268;
    }

    .btn-remove {
      background: #dc3545;
      color: white;
    }

    .btn-remove:hover {
      background: #c82333;
    }

    .cart-summary {
      background: white;
      border: 1px solid #ddd;
      border-radius: 4px;
      padding: 20px;
      height: fit-content;
    }

    .summary-title {
      font-size: 18px;
      color: #333;
      margin-bottom: 15px;
      padding-bottom: 10px;
      border-bottom: 1px solid #eee;
    }

    .summary-row {
      display: flex;
      justify-content: space-between;
      padding: 8px 0;
      font-size: 14px;
      color: #666;
    }

    .summary-row.total {
      margin-top: 10px;
      padding-top: 10px;
      border-top: 1px solid #eee;
      font-size: 16px;
      font-weight: 600;
      color: #333;
    }

    .checkout-btn {
      width: 100%;
      padding: 12px;
      background: #28a745;
      color: white;
      border: none;
      border-radius: 3px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      margin-top: 15px;
      text-decoration: none;
      display: block;
      text-align: center;
    }

    .checkout-btn:hover {
      background: #218838;
    }

    .empty-cart {
      background: white;
      border: 1px solid #ddd;
      border-radius: 4px;
      padding: 40px;
      text-align: center;
    }

    .empty-cart h3 {
      font-size: 20px;
      color: #333;
      margin-bottom: 10px;
    }

    .empty-cart p {
      color: #666;
      margin-bottom: 20px;
    }

    @media (max-width: 768px) {
      .cart-content {
        grid-template-columns: 1fr;
      }

      .cart-item {
        flex-direction: column;
      }

      .item-actions {
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        width: 100%;
      }
    }
  </style>
</head>
<body>
  <div class="cart-wrapper">
    <div class="cart-header">
      <h1 class="cart-title">Shopping Cart</h1>
      <a href="menu.php" class="back-link">Continue Shopping</a>
    </div>

    <?php if (count($cart_items) > 0): ?>
      <div class="cart-content">
        <div class="cart-items">
          <?php foreach ($cart_items as $id => $item): ?>
            <div class="cart-item">
              <img src="uploads/<?= htmlspecialchars($item['image'] ?? 'placeholder.jpg') ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" class="item-image" onerror="this.src='uploads/placeholder.jpg'">
              
              <div class="item-details">
                <div class="item-name"><?= htmlspecialchars($item['product_name']) ?></div>
                <div class="item-price">₱<?= number_format($item['price'], 2) ?></div>
                
                <div class="item-quantity-section">
                  <div class="quantity-display" id="qty-display-<?= $id ?>">
                    Quantity: <strong><?= $item['quantity'] ?></strong>
                  </div>
                  
                  <!-- Edit Mode Form -->
                  <form method="POST" action="update_cart.php" class="quantity-edit-form" id="qty-edit-<?= $id ?>">
                    <input type="hidden" name="item_id" value="<?= $id ?>">
                    <button type="button" class="quantity-btn" onclick="decreaseQuantity(<?= $id ?>)">-</button>
                    <input type="number" class="quantity-input" name="quantity" id="qty-input-<?= $id ?>" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock_quantity'] ?>">
                    <button type="button" class="quantity-btn" onclick="increaseQuantity(<?= $id ?>, <?= $item['stock_quantity'] ?>)">+</button>
                    <button type="submit" class="btn btn-save">Save</button>
                    <button type="button" class="btn btn-cancel" onclick="cancelEdit(<?= $id ?>, <?= $item['quantity'] ?>)">Cancel</button>
                  </form>
                </div>
              </div>

              <div class="item-actions">
                <div class="item-total">
                  ₱<?= number_format($item['price'] * $item['quantity'], 2) ?>
                </div>
                
                <div class="btn-group">
                  <button class="btn btn-edit" id="btn-edit-<?= $id ?>" onclick="enableEdit(<?= $id ?>)">
                    Edit
                  </button>
                  <a href="remove_from_cart.php?id=<?= $id ?>" class="btn btn-remove" onclick="return confirm('Remove this item from cart?')">
                    Remove
                  </a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="cart-summary">
          <h2 class="summary-title">Order Summary</h2>
          
          <div class="summary-row">
            <span>Subtotal:</span>
            <strong>₱<?= number_format($subtotal, 2) ?></strong>
          </div>
          
          <div class="summary-row total">
            <span>Total:</span>
            <strong>₱<?= number_format($total, 2) ?></strong>
          </div>

          <a href="checkout.php" class="checkout-btn">
            Proceed to Checkout
          </a>
        </div>
      </div>
    <?php else: ?>
      <div class="empty-cart">
        <h3>Your cart is empty</h3>
        <p>You haven't added any items to your cart yet.</p>
        <a href="menu.php" class="back-link">Browse Menu</a>
      </div>
    <?php endif; ?>
  </div>

  <script>
    function enableEdit(itemId) {
      document.getElementById('qty-display-' + itemId).style.display = 'none';
      document.getElementById('qty-edit-' + itemId).classList.add('active');
      document.getElementById('btn-edit-' + itemId).style.display = 'none';
    }

    function cancelEdit(itemId, originalQty) {
      document.getElementById('qty-display-' + itemId).style.display = 'block';
      document.getElementById('qty-edit-' + itemId).classList.remove('active');
      document.getElementById('qty-input-' + itemId).value = originalQty;
      document.getElementById('btn-edit-' + itemId).style.display = 'inline-block';
    }

    function increaseQuantity(itemId, maxStock) {
      const input = document.getElementById('qty-input-' + itemId);
      const currentValue = parseInt(input.value);
      if (currentValue < maxStock) {
        input.value = currentValue + 1;
      }
    }

    function decreaseQuantity(itemId) {
      const input = document.getElementById('qty-input-' + itemId);
      const currentValue = parseInt(input.value);
      if (currentValue > 1) {
        input.value = currentValue - 1;
      }
    }
  </script>
</body>
</html>