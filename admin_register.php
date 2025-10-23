<?php
include("database.php");
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = $_POST['username'];
  $password = $_POST['password'];
  $confirm_password = $_POST['confirm_password'];
  $first_name = $_POST['first_name'];
  $middle_name = $_POST['middle_name'];
  $last_name = $_POST['last_name'];
  $email = $_POST['email'];
  $contact_number = $_POST['contact_number'];

  if ($password !== $confirm_password) {
    echo "<script>alert('Passwords do not match.');</script>";
    exit();
  }

  $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    echo "<script>alert('Username already taken.');</script>";
  } else {
    $stmt = $conn->prepare("INSERT INTO admin (username, password, first_name, middle_name, last_name, email, contact_number) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $username, $password, $first_name, $middle_name, $last_name, $email, $contact_number);
    $stmt->execute();

    echo "<script>alert('Admin registered successfully!'); window.location.href='admin_login.php';</script>";
    exit();
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Registration</title>
  <link rel="stylesheet" href="admin_style.css" />
</head>
<body class="admin-body">
  <div class="admin-container">
    <h2>Create Admin Account</h2>
    <form method="POST">
        <div class="form-group">
            <label>First Name</label>
            <input type="text" name="first_name" required />
        </div>
        <div class="form-group">
            <label>Middle Name</label>
            <input type="text" name="middle_name" />
        </div>
        <div class="form-group">
            <label>Last Name</label>
            <input type="text" name="last_name" required />
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required />
        </div>
        <div class="form-group">
            <label>Contact Number</label>
            <input type="tel" name="contact_number" required />
        </div>
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" required />
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required />
        </div>
        <div class="form-group">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" required />
        </div>
        <button type="submit">Register</button>
    </form>


    <p>Already an admin? <a href="admin_login.php">Login here</a></p>
  </div>
</body>
</html>