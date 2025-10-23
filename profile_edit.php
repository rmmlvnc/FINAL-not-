<!-- profile_edit.php -->
<?php
session_start();
include 'database.php';

if (!isset($_SESSION['customer_id'])) {
  header("Location: login.php");
  exit();
}

$customer_id = $_SESSION['customer_id'];
$success_message = '';
$error_message = '';

// Fetch customer info
$stmt = $conn->prepare("SELECT * FROM customer WHERE customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();
$stmt->close();

if (!$customer) {
  header("Location: login.php");
  exit();
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $first_name = trim($_POST['first_name']);
  $middle_name = trim($_POST['middle_name']);
  $last_name = trim($_POST['last_name']);
  $email = trim($_POST['email']);
  $phone_number = trim($_POST['phone_number']);
  $address = trim($_POST['address']);

  if ($first_name && $last_name && $email && $phone_number && $address) {
    $update_stmt = $conn->prepare("
      UPDATE customer SET 
        first_name = ?, 
        middle_name = ?, 
        last_name = ?, 
        email = ?, 
        phone_number = ?, 
        address = ?
      WHERE customer_id = ?
    ");
    $update_stmt->bind_param("ssssssi", $first_name, $middle_name, $last_name, $email, $phone_number, $address, $customer_id);

    if ($update_stmt->execute()) {
      $success_message = "✅ Profile updated successfully!";
      header("Refresh:2; url=profile.php");
    } else {
      $error_message = "⚠️ Failed to update profile.";
    }

    $update_stmt->close();
  } else {
    $error_message = "⚠️ All fields are required.";
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Edit Profile</title>
  <style>
    body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; padding: 40px; }
    .container { max-width: 600px; margin: auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    h1 { text-align: center; color: #4a4a4a; margin-bottom: 30px; }
    label { display: block; margin-top: 15px; font-weight: 600; color: #333; }
    input, textarea { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 6px; }
    .btn { margin-top: 20px; padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 6px; cursor: pointer; }
    .btn:hover { background: #5568d3; }
    .message { margin-top: 20px; padding: 10px; border-radius: 6px; text-align: center; }
    .success { background: #d4edda; color: #155724; }
    .error { background: #f8d7da; color: #721c24; }
    .back-link { display: inline-block; margin-top: 20px; text-decoration: none; color: #667eea; font-weight: 500; }
  </style>
</head>
<body>
  <div class="container">
    <h1>Edit My Profile</h1>

    <?php if ($success_message): ?>
      <div class="message success"><?= $success_message ?></div>
    <?php elseif ($error_message): ?>
      <div class="message error"><?= $error_message ?></div>
    <?php endif; ?>

    <form method="POST">
      <label>First Name</label>
      <input type="text" name="first_name" value="<?= htmlspecialchars($customer['first_name']) ?>" required>

      <label>Middle Name</label>
      <input type="text" name="middle_name" value="<?= htmlspecialchars($customer['middle_name']) ?>">

      <label>Last Name</label>
      <input type="text" name="last_name" value="<?= htmlspecialchars($customer['last_name']) ?>" required>

      <label>Email</label>
      <input type="email" name="email" value="<?= htmlspecialchars($customer['email']) ?>" required>

      <label>Phone Number</label>
      <input type="text" name="phone_number" value="<?= htmlspecialchars($customer['phone_number']) ?>" required>

      <label>Address</label>
      <textarea name="address" rows="3" required><?= htmlspecialchars($customer['address']) ?></textarea>

      <button type="submit" class="btn">💾 Save Changes</button>
    </form>

    <a href="profile.php" class="back-link">← Back to Profile</a>
  </div>
</body>
</html>
