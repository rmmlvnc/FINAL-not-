<?php
session_start();

// cart count sa session
$cart_count = 0;
if (isset($_SESSION['cart'])) {
  foreach ($_SESSION['cart'] as $item) {
    $cart_count += $item['quantity'];
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Kyla's Bistro | Home</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body class="index">
  <header>
    <div class="nav-bar">
      <img src="pictures/logo.jpg" alt="Kyla Logo" class="logo" />
      <div class="nav-actions">
        <?php if (isset($_SESSION['username'])): ?>
          <span class="welcome-text">👋 Welcome, <?= htmlspecialchars($_SESSION['username']) ?></span>
          <a href="profile.php" class="btn profile-btn" title="View Profile">👤 Profile</a>
          <a href="cart.php" class="cart-icon" title="View Cart">🛒<?= $cart_count > 0 ? " ($cart_count)" : "" ?></a>
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
      <li><a href="aboutus.php">ABOUT US</a></li>
    </ul>
  </nav>

  <section class="hero">
    <h1>Welcome to Kyla's Bistro</h1>
    <p>Experience culinary excellence in every bite. Where great food meets unforgettable moments.</p>
  </section>

  <section class="reservation-highlight">
    <div class="reservation-card">
      <div class="reservation-content">
        <h2>Reserve Your Table</h2>
        <p>Looking for a cozy, stylish spot for your next special event? Kyla's Bistro is now open for event bookings from intimate birthdays, romantic weddings, private meetings, and more.</p>
        
        <div class="reservation-features">
          <div class="feature-item">Intimate Birthday Parties</div>
          <div class="feature-item">Romantic Weddings</div>
          <div class="feature-item">Private Meetings</div>
          <div class="feature-item">Corporate Events</div>
        </div>

        <a href="reservation.php" class="reserve-btn-large">Reserve Now</a>
      </div>
      
      <div class="reservation-image">
        <img src="pictures/reserve.jpg" alt="Kyla's Bistro Interior" />
      </div>
    </div>
  </section>

  <section class="featured-menu">
    <div class="section-header">
      <h2>Featured Dishes</h2>
      <p>Discover our chef's signature creations</p>
    </div>

    <div class="menu-grid">
      <div class="menu-card">
        <img src="pictures/pizza/kassy-kass.jpg" alt="Kassy Kass" class="menu-card-image" />
        <div class="menu-card-content">
          <h3>Kassy Kass</h3>
          <p>Heavy ground beef, pineapple, mushroom, black olives</p>
          <div class="menu-card-footer">
            <span class="price">₱378.00</span>
            <button class="order-btn-small" onclick="window.location.href='menu.php'">Order Now</button>
          </div>
        </div>
      </div>

      <div class="menu-card">
        <img src="pictures/Pork/back-ribs.jpg" alt="Baby Back Ribs" class="menu-card-image" />
        <div class="menu-card-content">
          <h3>Baby Back Ribs</h3>
          <p>Pugon roasted baby back ribs in smokey barbeque sauce</p>
          <div class="menu-card-footer">
            <span class="price">₱368.00</span>
            <button class="order-btn-small" onclick="window.location.href='menu.php'">Order Now</button>
          </div>
        </div>
      </div>

      <div class="menu-card">
        <img src="pictures/Appetizer/pork-sisig.jpg" alt="Milkshake" class="menu-card-image" />
        <div class="menu-card-content">
          <h3>Pork Sisig</h3>
          <p>Shimmered, grilled and sauted pork topped with egg.</p>
          <div class="menu-card-footer">
            <span class="price">₱378.00</span>
            <button class="order-btn-small" onclick="window.location.href='menu.php'">Order Now</button>
          </div>
        </div>
      </div>

    </div>
  </section>

  <section class="cta-section">
    <div class="cta-box">
      <h2>Ready to Enjoy Kyla's Bistro?</h2>
      <p>Whether you're dining in or planning a special event, we've got you covered.</p>
      <div class="cta-buttons">
        <a href="menu.php" class="cta-btn cta-btn-primary">
          Browse Full Menu
        </a>
        <a href="reservation.php" class="cta-btn cta-btn-secondary">
          Book an Event
        </a>
      </div>
    </div>
  </section>

</body>
</html>