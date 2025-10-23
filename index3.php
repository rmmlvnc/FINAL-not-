<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Kyla's Bistro | Customer Page</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body class="index">
  <header>
    <div class="nav-bar">
      <img src="pictures/logo.jpg" alt="Kyla Logo" class="logo" />
      <div class="nav-actions">
        <?php if (isset($_SESSION['username'])): ?>
          <span class="welcome-text">👋 Welcome, <?= htmlspecialchars($_SESSION['username']) ?></span>
          <a href="customer_logout.php" class="btn logout-btn">LOG OUT</a>
        <?php else: ?>
          <a href="login.php" class="btn login-btn">LOGIN</a>
          <a href="registration.php" class="btn signup-btn">SIGN UP</a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <nav>
    <ul class="links">
      <li><a href="index.php" class="active">HOME</a></li>
      <li><a href="menu.php">MENU</a></li>
      <li><a href="#">FEEDBACK</a></li>
      <li><a href="aboutus.php">ABOUT US</a></li>
    </ul>
  </nav>

  <section class="welcome">
    <a href="reservation.php" class="reserve-btn">🍽️ Reserve a Table</a>
    <h2>Welcome to Kyla's Bistro</h2>
    <p>
      Looking for a cozy, stylish spot for your next special event?<br>
      Kyla's Bistro is now open for event bookings from intimate birthdays, romantic weddings, private meetings, and more.
    </p>
  </section>

  <section class="action-buttons">
    <h2>Ready to enjoy Kyla's Bistro?</h2>
    <p>Whether you're dining in or planning a special event, we've got you covered.</p>
    <div class="button-group">
      <a href="menu.php" class="btn order-btn">🛍️ Start an Order</a>
      <a href="reservation.php" class="btn reserve-btn">🍽️ Reserve a Table</a>
    </div>
  </section>

  
  <section class="main">
    <div class="feature-grid">
      <div class="feature-card">
        <img src="pictures/pizza/kassy-kass.jpg" alt="Signature Burger" />
        <h3>Signature Angus Burger</h3>
        <p>Crafted with premium beef, melted cheddar, and our secret sauce.</p>
        <span>₱250</span>
      </div>
      <div class="feature-card">
        <img src="pictures/Pork/back-ribs.jpg" alt="Truffle Pasta" />
        <h3>Truffle Cream Pasta</h3>
        <p>Rich, creamy, and infused with aromatic truffle oil.</p>
        <span>₱280</span>
      </div>
      <div class="feature-card">
        <img src="pictures/milkshake.png" alt="Milkshake" />
        <h3>Classic Vanilla Milkshake</h3>
        <p>Thick, creamy, and topped with whipped cream and sprinkles.</p>
        <span>₱120</span>
      </div>
      <div class="feature-card">
        <img src="pictures/steak.png" alt="Steak" />
        <h3>Grilled Ribeye Steak</h3>
        <p>Juicy, tender, and served with garlic butter and sides.</p>
        <span>₱450</span>
      </div>
    </div>
  </section>


  <section class="banner">
    <img src="pictures/bg.jpg" alt="bg Kyla's Bistro" />
  </section>
</body>
</html>