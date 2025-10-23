<?php
include 'database.php';
$id = $_GET['id'];

$stmt = $pdo->prepare("UPDATE payment SET status = 'Confirmed' WHERE payment_id = ?");
$stmt->execute([$id]);

header("Location: manage_payments.php");