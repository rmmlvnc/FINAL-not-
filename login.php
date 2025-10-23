<?php
include("database.php");
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = $_POST['username'];
  $password = $_POST['password'];

  $stmt = $conn->prepare("SELECT * FROM customer WHERE username = ?");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $result = $stmt->get_result();
  $user = $result->fetch_assoc();

  if ($user && $password === $user['password']) {
    $_SESSION['customer_id'] = $user['customer_id'];
    $_SESSION['username'] = $user['username'];
    echo "<script>alert('Login successful!'); window.location.href='index.php';</script>";
    exit();
  } else {
    echo "<script>alert('Invalid username or password.');</script>";
  }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BLUEBACK Login</title>
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
  <div class="container">
    <div class="left-panel">
      <div class="logo">
        <img src="pictures/logo.jpg" alt="BLUEBACK Logo" class="logo-icon">
        <h1>BLUEBACK</h1>
        <p class="placeholder-text">Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
      </div>
    </div>

    <div class="right-panel">
      <div class="login-box">
        <h2>Welcome</h2>
        <p>Log in to your account to continue</p>
        <a href="index.php" class="back-button">← Back to Home</a>
        <form method="POST">
          <label for="username">Username</label>
          <input type="text" name="username" placeholder="Enter your username" required />

          <label for="password">Password</label>
          <input type="password" name="password" placeholder="Enter your password" required />

          <a href="#" class="forgot-link">Forgot your password?</a>

          <button type="submit">LOG IN</button>
        </form>

        <p class="signup-prompt">Don't have an account? <a href="registration.php">Sign Up</a></p>
      </div>
    </div>
  </div>
</body>
</html>