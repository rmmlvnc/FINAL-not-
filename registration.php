<?php
include("database.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $first_name = $_POST['first_name'];
  $middle_name = $_POST['middle_name'];
  $last_name = $_POST['last_name'];
  $username = $_POST['username'];
  $password = $_POST['password'];
  $confirm_password = $_POST['confirm_password'];
  $email = $_POST['email'];
  $contact_number = $_POST['contact_number'];
  $address = $_POST['address'];

  if ($password !== $confirm_password) {
    echo "<script>alert('Passwords do not match. Please try again.');</script>";
  } else {
    // Check for existing username
    $check = $conn->prepare("SELECT COUNT(*) FROM customer WHERE username = ?");
    if (!$check) {
      die("Prepare failed: " . $conn->error);
    }
    $check->bind_param("s", $username);
    $check->execute();
    $check->bind_result($count);
    $check->fetch();
    $check->close();

    if ($count > 0) {
      echo "<script>alert('Username already exists. Please choose another.');</script>";
    } else {
      // Insert plain password
      $stmt = $conn->prepare("INSERT INTO customer (first_name, middle_name, last_name, username, password, email, phone_number, address)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
      if (!$stmt) {
        die("Prepare failed: " . $conn->error);
      }
      $stmt->bind_param("ssssssss", $first_name, $middle_name, $last_name, $username, $password, $email, $contact_number, $address);
      $stmt->execute();
      $stmt->close();

      echo "<script>alert('Registration successful!'); window.location.href='login.php';</script>";
      exit();
    }
  }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Customer Registration</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body class="registration">
  <div class="form-container">
    <h2>Create Your Account</h2>
    <form method="POST" class="two-column-form">
      <div class="form-group">
        <label for="first_name">First Name</label>
        <input type="text" name="first_name" required />
      </div>
      <div class="form-group">
        <label for="middle_name">Middle Name</label>
        <input type="text" name="middle_name" />
      </div>
      <div class="form-group">
        <label for="last_name">Last Name</label>
        <input type="text" name="last_name" required />
      </div>
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" name="username" required />
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" name="password" required />
      </div>
      <div class="form-group">
        <label for="confirm_password">Confirm Password</label>
        <input type="password" name="confirm_password" required />
      </div>
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" name="email" required />
      </div>
      <div class="form-group">
        <label for="contact_number">Contact Number</label>
        <input type="tel" name="contact_number" required />
      </div>
      <div class="form-group full-width">
        <label for="address">Address</label>
        <textarea name="address" rows="3" required></textarea>
    </div>

    <div class="form-group full-width">
        <button type="submit" class="reg-btn">Register</button>
    </div>

    </form>

    <div class="form-footer">
      <a href="login.php" class="back-btn">← Back to Login</a>
    </div>
  </div>
</body>
</html>