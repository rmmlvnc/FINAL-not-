<?php
session_start();
include("database.php");

if (!isset($_SESSION['username']) || !isset($_SESSION['cart_order_id'])) {
  header("Location: cart.php");
  exit();
}

$order_id = $_SESSION['cart_order_id'];
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
  header("Location: cart.php");
  exit();
}

// Delete the item from order_item
$delete_stmt = $conn->prepare("DELETE FROM order_item WHERE order_id = ? AND product_id = ?");
if ($delete_stmt === false) {
  die("Error preparing delete query: " . $conn->error);
}

$delete_stmt->bind_param("ii", $order_id, $product_id);
$delete_stmt->execute();
$delete_stmt->close();

// Check if cart still has items
$count_stmt = $conn->prepare("SELECT COUNT(*) as item_count FROM order_item WHERE order_id = ?");
if ($count_stmt === false) {
  die("Error preparing count query: " . $conn->error);
}

$count_stmt->bind_param("i", $order_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_row = $count_result->fetch_assoc();
$count_stmt->close();


header("Location: cart.php");
exit();
?>