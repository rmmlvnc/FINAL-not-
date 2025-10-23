<?php
session_start();
include 'database.php';

if (!isset($_SESSION['admin'])) {
  header("Location: admin_login.php");
  exit();
}

$username = $_SESSION['admin'];

// Get admin name
$stmt = $conn->prepare("SELECT first_name FROM admin WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$first_name = $admin ? $admin['first_name'] : 'Admin';

// Get recent orders
$order_result = $conn->query("
  SELECT o.order_id, o.customer_id, o.order_date, o.order_time, o.total_amount,
         c.first_name, c.last_name,
         p.payment_status
  FROM orders o
  JOIN customer c ON o.customer_id = c.customer_id
  LEFT JOIN payment p ON o.order_id = p.order_id
  ORDER BY o.order_date DESC, o.order_time DESC
  LIMIT 10
");

$orders = [];
if ($order_result) {
  while ($row = $order_result->fetch_assoc()) {
    $orders[] = $row;
  }
}

// Get system reports/statistics
$stats = [];

// Total orders
$total_orders_result = $conn->query("SELECT COUNT(*) as total FROM orders");
$stats['total_orders'] = $total_orders_result->fetch_assoc()['total'];

// Total revenue
$total_revenue_result = $conn->query("SELECT SUM(total_amount) as revenue FROM orders");
$stats['total_revenue'] = $total_revenue_result->fetch_assoc()['revenue'] ?? 0;

// Total customers
$total_customers_result = $conn->query("SELECT COUNT(*) as total FROM customer");
$stats['total_customers'] = $total_customers_result->fetch_assoc()['total'];

// Total products
$total_products_result = $conn->query("SELECT COUNT(*) as total FROM product");
$stats['total_products'] = $total_products_result->fetch_assoc()['total'];

// Pending payments
$pending_payments_result = $conn->query("SELECT COUNT(*) as total FROM payment WHERE payment_status = 'Pending'");
$stats['pending_payments'] = $pending_payments_result->fetch_assoc()['total'];

// Confirmed reservations
$confirmed_reservations_result = $conn->query("SELECT COUNT(*) as total FROM reservation WHERE status = 'Confirmed'");
$stats['confirmed_reservations'] = $confirmed_reservations_result->fetch_assoc()['total'];

// Monthly sales report
$monthly_sales_result = $conn->query("
  SELECT 
    DATE_FORMAT(order_date, '%Y-%m') as month,
    COUNT(*) as order_count,
    SUM(total_amount) as total_sales
  FROM orders
  WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
  GROUP BY DATE_FORMAT(order_date, '%Y-%m')
  ORDER BY month DESC
");

$monthly_sales = [];
if ($monthly_sales_result) {
  while ($row = $monthly_sales_result->fetch_assoc()) {
    $monthly_sales[] = $row;
  }
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Dashboard - Kyla's Bistro</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    :root {
      --primary: #2D3436;
      --secondary: #636E72;
      --accent: #00B894;
      --danger: #D63031;
      --warning: #FDCB6E;
      --light: #DFE6E9;
      --white: #FFFFFF;
      --text: #2D3436;
      --text-light: #636E72;
      --shadow: rgba(0, 0, 0, 0.08);
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
      background: #F8F9FA;
      color: var(--text);
      line-height: 1.6;
    }

    .header {
      background: var(--white);
      padding: 1.5rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid var(--light);
      position: sticky;
      top: 0;
      z-index: 100;
    }

    .header h1 {
      font-size: 1.5rem;
      font-weight: 600;
      color: var(--primary);
    }

    .header-right {
      display: flex;
      align-items: center;
      gap: 1.5rem;
    }

    .welcome {
      color: var(--text-light);
      font-size: 0.95rem;
    }

    .btn-logout {
      background: var(--danger);
      color: var(--white);
      padding: 0.5rem 1.25rem;
      border-radius: 6px;
      text-decoration: none;
      font-size: 0.9rem;
      font-weight: 500;
      transition: opacity 0.2s;
    }

    .btn-logout:hover {
      opacity: 0.9;
    }

    .container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 2rem;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1.25rem;
      margin-bottom: 2.5rem;
    }

    .stat-card {
      background: var(--white);
      padding: 1.5rem;
      border-radius: 8px;
      border-left: 4px solid var(--accent);
    }

    .stat-card.revenue { border-left-color: #00B894; }
    .stat-card.orders { border-left-color: #0984E3; }
    .stat-card.customers { border-left-color: #6C5CE7; }
    .stat-card.products { border-left-color: #FD79A8; }
    .stat-card.pending { border-left-color: #FDCB6E; }
    .stat-card.reservations { border-left-color: #00CEC9; }

    .stat-label {
      color: var(--text-light);
      font-size: 0.85rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 0.5rem;
    }

    .stat-value {
      font-size: 2rem;
      font-weight: 700;
      color: var(--primary);
    }

    .section {
      background: var(--white);
      border-radius: 8px;
      padding: 2rem;
      margin-bottom: 2rem;
    }

    .section-title {
      font-size: 1.25rem;
      font-weight: 600;
      color: var(--primary);
      margin-bottom: 1.5rem;
      padding-bottom: 0.75rem;
      border-bottom: 2px solid var(--light);
    }

    .action-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
    }

    .btn-primary {
      background: var(--accent);
      color: var(--white);
      padding: 0.6rem 1.25rem;
      border-radius: 6px;
      text-decoration: none;
      font-size: 0.9rem;
      font-weight: 500;
      transition: opacity 0.2s;
      display: inline-block;
    }

    .btn-primary:hover {
      opacity: 0.9;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th {
      text-align: left;
      padding: 0.75rem 1rem;
      background: #F8F9FA;
      color: var(--text-light);
      font-weight: 600;
      font-size: 0.85rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    td {
      padding: 1rem;
      border-bottom: 1px solid var(--light);
    }

    tr:last-child td {
      border-bottom: none;
    }

    tr:hover {
      background: #FAFBFC;
    }

    .empty-state {
      text-align: center;
      padding: 3rem;
      color: var(--text-light);
    }

    .empty-state svg {
      width: 64px;
      height: 64px;
      margin-bottom: 1rem;
      opacity: 0.3;
      stroke: var(--text-light);
    }

    .badge {
      padding: 0.25rem 0.75rem;
      border-radius: 12px;
      font-size: 0.8rem;
      font-weight: 500;
    }

    .badge-paid { background: #D4EDDA; color: #155724; }
    .badge-pending { background: #FFF3CD; color: #856404; }
    .badge-unpaid { background: #F8D7DA; color: #721C24; }



    @media (max-width: 768px) {
      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
      }

      .container {
        padding: 1rem;
      }
    }
  </style>
</head>
<body>
  <header class="header">
    <h1>🍽️ Kyla's Bistro Admin</h1>
    <div class="header-right">
      <span class="welcome">Welcome, <?= htmlspecialchars($first_name) ?></span>
      <a href="admin_logout.php" class="btn-logout">Logout</a>
    </div>
  </header>

  <div class="container">
    <!-- Statistics Overview -->
    <div class="stats-grid">
      <div class="stat-card revenue">
        <div class="stat-label">Total Revenue</div>
        <div class="stat-value">₱<?= number_format($stats['total_revenue'], 2) ?></div>
      </div>
      <div class="stat-card orders">
        <div class="stat-label">Total Orders</div>
        <div class="stat-value"><?= $stats['total_orders'] ?></div>
      </div>
      <div class="stat-card customers">
        <div class="stat-label">Customers</div>
        <div class="stat-value"><?= $stats['total_customers'] ?></div>
      </div>
      <div class="stat-card products">
        <div class="stat-label">Products</div>
        <div class="stat-value"><?= $stats['total_products'] ?></div>
      </div>
      <div class="stat-card pending">
        <div class="stat-label">Pending Payments</div>
        <div class="stat-value"><?= $stats['pending_payments'] ?></div>
      </div>
      <div class="stat-card reservations">
        <div class="stat-label">Reservations</div>
        <div class="stat-value"><?= $stats['confirmed_reservations'] ?></div>
      </div>
    </div>

    <!-- Reports Section -->
    <div class="section">
      <h2 class="section-title">Monthly Sales Report</h2>
      <?php if (count($monthly_sales) > 0): ?>
        <table>
          <thead>
            <tr>
              <th>Month</th>
              <th>Orders</th>
              <th>Revenue</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($monthly_sales as $month): ?>
              <tr>
                <td><?= date('F Y', strtotime($month['month'] . '-01')) ?></td>
                <td><?= $month['order_count'] ?></td>
                <td><strong>₱<?= number_format($month['total_sales'], 2) ?></strong></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="empty-state">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
          <p>No sales data available</p>
        </div>
      <?php endif; ?>
    </div>

    <!-- Staff Management -->
    <div class="section">
      <div class="action-bar">
        <h2 class="section-title" style="margin: 0; padding: 0; border: none;">Staff Management</h2>
        <a href="manage_staff.php" class="btn-primary">Manage Staff</a>
      </div>
      <p style="color: var(--text-light);">View and edit staff accounts, permissions, and roles.</p>
    </div>

    <!-- Recent Orders -->
    <div class="section">
      <h2 class="section-title">Recent Orders</h2>
      <?php if (count($orders) > 0): ?>
        <table>
          <thead>
            <tr>
              <th>Order ID</th>
              <th>Customer</th>
              <th>Date</th>
              <th>Time</th>
              <th>Total</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($orders as $order): ?>
              <tr>
                <td><strong>#<?= $order['order_id'] ?></strong></td>
                <td><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></td>
                <td><?= date('M d, Y', strtotime($order['order_date'])) ?></td>
                <td><?= date('h:i A', strtotime($order['order_time'])) ?></td>
                <td><strong>₱<?= number_format($order['total_amount'], 2) ?></strong></td>
                <td>
                  <?php 
                    $status = $order['payment_status'] ?? 'Unpaid';
                    $badge_class = 'badge-' . strtolower($status);
                  ?>
                  <span class="badge <?= $badge_class ?>"><?= $status ?></span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="empty-state">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <p>No orders found</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>