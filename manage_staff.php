<?php
session_start();
include 'database.php';

if (!isset($_SESSION['admin'])) {
  header("Location: admin_login.php");
  exit();
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_staff'])) {
  $staff_id = $_POST['staff_id'];
  $first_name = $_POST['first_name'];
  $last_name = $_POST['last_name'];
  $email = $_POST['email'];

  $stmt = $conn->prepare("UPDATE staff SET first_name = ?, last_name = ?, email = ? WHERE staff_id = ?");
  $stmt->bind_param("sssi", $first_name, $last_name, $email, $staff_id);
  $stmt->execute();
}

// Fetch staff list
$staff_result = $conn->query("SELECT * FROM staff ORDER BY first_name ASC");
$staff_list = [];
if ($staff_result) {
  while ($row = $staff_result->fetch_assoc()) {
    $staff_list[] = $row;
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Manage Staff</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f8f9fa;
      margin: 0;
      padding: 0;
    }
    header {
      background: #2c3e50;
      color: white;
      padding: 1rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .container {
      padding: 2rem;
    }
    h2 {
      color: #2c3e50;
      margin-bottom: 1rem;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      box-shadow: 0 0 10px rgba(0,0,0,0.05);
    }
    th, td {
      padding: 0.75rem;
      border: 1px solid #ddd;
      text-align: left;
    }
    th {
      background: #34495e;
      color: white;
    }
    input[type="text"], input[type="email"] {
      width: 100%;
      padding: 0.5rem;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
    .update-btn {
      background: #27ae60;
      color: white;
      border: none;
      padding: 0.5rem 1rem;
      border-radius: 4px;
      cursor: pointer;
    }
    .update-btn:hover {
      background: #219150;
    }
    .back-link {
      margin-top: 1rem;
      display: inline-block;
      color: #2980b9;
      text-decoration: none;
    }
  </style>
</head>
<body>
  <header>
    <div>Kyla's Bistro Admin</div>
    <div><a href="admin_dashboard.php" style="color:white;">← Back to Dashboard</a></div>
  </header>

  <div class="container">
    <h2>Manage Staff</h2>
    <?php if (count($staff_list) === 0): ?>
      <p>No staff records found.</p>
    <?php else: ?>
      <form method="POST">
        <table>
          <thead>
            <tr>
              <th>Staff ID</th>
              <th>First Name</th>
              <th>Last Name</th>
              <th>Email</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($staff_list as $staff): ?>
              <tr>
                <td><?= $staff['staff_id'] ?></td>
                <td><input type="text" name="first_name" value="<?= htmlspecialchars($staff['first_name']) ?>" required></td>
                <td><input type="text" name="last_name" value="<?= htmlspecialchars($staff['last_name']) ?>" required></td>
                <td><input type="email" name="email" value="<?= htmlspecialchars($staff['email']) ?>" required></td>
                <td>
                  <input type="hidden" name="staff_id" value="<?= $staff['staff_id'] ?>">
                  <button type="submit" name="update_staff" class="update-btn">Update</button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>