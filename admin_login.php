<?php
include("database.php");
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = $_POST['username'];
  $password = $_POST['password'];

  $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $result = $stmt->get_result();
  $admin = $result->fetch_assoc();

  if ($admin && $password == $admin['password']) {
    $_SESSION['admin'] = $admin['username'];
    echo "<script>alert('Login successful!'); window.location.href='admin_dashboard.php';</script>";
    exit();
  } else {
    echo "<script>alert('Invalid credentials.');</script>";
  }
  
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Login</title>
  <link rel="stylesheet" href="admin_style.css" />
</head>
<body class="admin-body">
  <div class="admin-container">
    <h2>Admin Login</h2>
    <form method="POST">
      <label>Username</label>
      <input type="text" name="username" required />

      <label>Password</label>
      <input type="password" name="password" required />

      <button type="submit">Login</button>
    </form>
    <p>Need an account? <a href="admin_register.php">Register here</a></p>
  </div>
</body>
</html>