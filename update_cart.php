<?php
session_start();

if (!isset($_SESSION['username'])) {
  header("Location: login.php");
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $item_id = (int)$_POST['item_id'];
  $quantity = max(1, (int)$_POST['quantity']);
  
  if (isset($_SESSION['cart'][$item_id])) {
    $_SESSION['cart'][$item_id]['quantity'] = $quantity;
  }
}

header("Location: cart.php");
exit();
?>