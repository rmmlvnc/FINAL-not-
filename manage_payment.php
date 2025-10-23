<?php
session_start();
include 'database.php';

if (!isset($_SESSION['admin'])) {
  header("Location: admin_login.php");
  exit();
}

$stmt = $pdo->query("SELECT * FROM payment ORDER BY payment_date DESC");
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
  <title>Manage Payments</title>
  <link rel="stylesheet" href="dashboard.css" />
</head>
<body>
  <h2>Payment Records</h2>

  <?php if (count($payments) === 0): ?>
    <p>No payments have been made yet.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Payment ID</th>
          <th>Order ID</th>
          <th>Date</th>
          <th>Time</th>
          <th>Method</th>
          <th>Amount</th>
          <th>Receipt</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($payments as $row): ?>
          <tr>
            <td><?= $row['payment_id'] ?></td>
            <td><?= $row['order_id'] ?></td>
            <td><?= $row['payment_date'] ?></td>
            <td><?= $row['payment_time'] ?></td>
            <td><?= $row['payment_method'] ?></td>
            <td>₱<?= number_format($row['amount'], 2) ?></td>
            <td>
              <?php if (!empty($row['receipt'])): ?>
                <a href="uploads/<?= $row['receipt'] ?>" target="_blank">View</a>
              <?php else: ?>
                No receipt
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</body>
</html><?php
session_start();
include 'database.php';

if (!isset($_SESSION['admin'])) {
  header("Location: admin_login.php");
  exit();
}

$stmt = $pdo->query("SELECT * FROM payment ORDER BY payment_date DESC");
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
  <title>Manage Payments</title>
  <link rel="stylesheet" href="dashboard.css" />
</head>
<body>
  <h2>Payment Records</h2>

  <?php if (count($payments) === 0): ?>
    <p>No payments have been made yet.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Payment ID</th>
          <th>Order ID</th>
          <th>Date</th>
          <th>Time</th>
          <th>Method</th>
          <th>Amount</th>
          <th>Receipt</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($payments as $row): ?>
          <tr>
            <td><?= $row['payment_id'] ?></td>
            <td><?= $row['order_id'] ?></td>
            <td><?= $row['payment_date'] ?></td>
            <td><?= $row['payment_time'] ?></td>
            <td><?= $row['payment_method'] ?></td>
            <td>₱<?= number_format($row['amount'], 2) ?></td>
            <td>
              <?php if (!empty($row['receipt'])): ?>
                <a href="uploads/<?= $row['receipt'] ?>" target="_blank">View</a>
              <?php else: ?>
                No receipt
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</body>
</html>