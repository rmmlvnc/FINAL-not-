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

// Get POST data
$product_id = (int)$_POST['product_id'];
$quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;
$category = $_POST['category'] ?? '';

// Check if product is available
$prod_stmt = $conn->prepare("SELECT product_name, price, stock_quantity FROM product WHERE product_id = ?");
if ($prod_stmt === false) {
  die("Error preparing product query: " . $conn->error);
}

$prod_stmt->bind_param("i", $product_id);
$prod_stmt->execute();
$prod_result = $prod_stmt->get_result();
$product = $prod_result->fetch_assoc();
$prod_stmt->close();

if (!$product) {
  die("Product not found");
}

if ($product['stock_quantity'] < $quantity) {
  die("Insufficient stock available");
}

// Initialize session cart if not exists
if (!isset($_SESSION['cart'])) {
  $_SESSION['cart'] = [];
}

// Add or update item in session cart
if (isset($_SESSION['cart'][$product_id])) {
  // Update quantity
  $_SESSION['cart'][$product_id]['quantity'] += $quantity;
} else {
  // Add new item
  $_SESSION['cart'][$product_id] = [
    'product_name' => $product['product_name'],
    'price' => $product['price'],
    'quantity' => $quantity,
    'stock_quantity' => $product['stock_quantity']
  ];
}

// Redirect back to menu
$redirect_url = 'menu.php' . ($category ? '?category=' . urlencode($category) : '');
header("Location: $redirect_url");
exit();
?>