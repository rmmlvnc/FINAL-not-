<?php
session_start();
include 'database.php';

if (!isset($_SESSION['admin'])) {
  header("Location: admin_login.php");
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $payment_id = $_POST['payment_id'] ?? null;
  $new_status = $_POST['payment_status'] ?? null;

  if ($payment_id && $new_status) {
    $stmt = $conn->prepare("UPDATE payment SET payment_status = ? WHERE payment_id = ?");
    $stmt->bind_param("si", $new_status, $payment_id);
    $stmt->execute();
  }
}

header("Location: admin_dashboard.php");
exit();