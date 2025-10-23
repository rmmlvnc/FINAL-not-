<?php
session_start();
include 'database.php';

// Redirect if not logged in
if (!isset($_SESSION['admin'])) {
  header("Location: admin.php");
  exit();
}

$username = $_SESSION['admin'];

// Fetch admin's name
$stmt = $conn->prepare("SELECT first_name, last_name FROM admin WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$full_name = $admin['first_name'] . ' ' . $admin['last_name'];

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="dashboard.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body>
  <header class="dashboard-header">
    <div class="logo">Kyla's Bistro Admin</div>
    <div class="admin-info">
      <span>👋 Welcome, <?= htmlspecialchars($full_name) ?></span>
      <a href="logout.php" class="logout-btn">Logout</a>
    </div>
  </header>

  <main class="dashboard-main">
    <h2>Dashboard Overview</h2>
    <div class="card-grid">
      <div class="card">
        <i class="fas fa-users"></i>
        <h3>Manage Users</h3>
        <p>View and edit customer accounts</p>
        <a href="manage_users.php">Go</a>
      </div>
      <div class="card">
        <i class="fas fa-box"></i>
        <h3>Manage Products</h3>
        <p>Update menu items and pricing</p>
        <a href="manage_products.php">Go</a>
      </div>
      <div class="card">
        <i class="fas fa-receipt"></i>
        <h3>Orders</h3>
        <p>Track and fulfill customer orders</p>
        <a href="manage_orders.php">Go</a>
      </div>
    </div>
  </main>
</body>
</html>