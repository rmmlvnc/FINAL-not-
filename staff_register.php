<?php
session_start();
include("database.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $first = $_POST['first_name'];
    $middle = $_POST['middle_name'];
    $last = $_POST['last_name'];
    $email = $_POST['email'];
    $contact = $_POST['contact_number'];
    $address = $_POST['address'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $plain_password = $password;

        $check = $conn->prepare("SELECT * FROM staff WHERE email = ? OR username = ?");
        $check->bind_param("ss", $email, $username);
        $check->execute();
        $result = $check->get_result()->fetch_assoc();

        if ($result) {
            $error = "Email or username already registered.";
        } else {
            $stmt = $conn->prepare("INSERT INTO staff (username, first_name, middle_name, last_name, email, contact_number, address, password) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss", $username, $first, $middle, $last, $email, $contact, $address, $plain_password);
            $stmt->execute();
            header("Location: staff_login.php");
            exit();
        }
    }
}
?>



<!DOCTYPE html>
<html>
<head>
    <title>Staff Registration</title>
    <link rel="stylesheet" href="staff_style.css">
</head>
<body class="form-body">
    <div class="form-container">
        <h2>Create Staff Account</h2>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="POST">
            <div class="form-grid">
                <div class="form-column">
                    <input type="text" name="first_name" placeholder="First Name" required>
                    <input type="text" name="last_name" placeholder="Last Name" required>
                    <input type="text" name="username" placeholder="Username" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                </div>
                <div class="form-column">
                    <input type="text" name="middle_name" placeholder="Middle Name">
                    <input type="text" name="contact_number" placeholder="Contact Number" required>
                    <input type="text" name="address" placeholder="Address" required>
                    <input type="email" name="email" placeholder="Email" required>
                </div>
            </div>
            <button type="submit">Register</button>
        </form>
        <p>Already registered? <a href="staff_login.php">Login here</a></p>
    </div>
</body>
</html>