<?php
session_start();
include("database.php");

$category_result = $conn->query("SELECT * FROM category");

$category_id = $_GET['category'] ?? null;

if ($category_id) {
  $product_stmt = $conn->prepare("SELECT * FROM product WHERE category_id = ?");
  $product_stmt->bind_param("i", $category_id);
  $product_stmt->execute();
  $product_result = $product_stmt->get_result();
  $product_stmt->close();
} else {
  $product_result = $conn->query("SELECT * FROM product");
}

$selected_category_name = null;

if ($category_id) {
  $cat_stmt = $conn->prepare("SELECT category_name FROM category WHERE category_id = ?");
  $cat_stmt->bind_param("i", $category_id);
  $cat_stmt->execute();
  $cat_result = $cat_stmt->get_result();
  $cat_row = $cat_result->fetch_assoc();
  $selected_category_name = $cat_row['category_name'] ?? null;
  $cat_stmt->close();
}

// Get cart count from session
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
      <li><a href="index.php">HOME</a></li>
      <li><a href="menu.php" class="active">MENU</a></li>
      <li><a href="aboutus.php">ABOUT US</a></li>
    </ul>
  </nav>

  <section class="main">
    <aside class="menu-sidebar">
      <h2 class="menu-title">MENU</h2>
      <ul class="menu-list">
        <?php while ($cat = $category_result->fetch_assoc()): ?>
          <li><a href="menu.php?category=<?= $cat['category_id'] ?>">
            <?= htmlspecialchars($cat['category_name']) ?>
          </a></li>
        <?php endwhile; ?>
      </ul>
    </aside>

    <?php if ($selected_category_name): ?>
      <h2 style="text-align:center; margin-bottom:20px;">
        <?= htmlspecialchars($selected_category_name) ?>
      </h2>
    <?php endif; ?>

    <div class="product-grid">
      <?php if ($product_result->num_rows > 0): ?>
        <?php while ($prod = $product_result->fetch_assoc()): ?>
          <div class="product-card">
            <img src="uploads/<?= htmlspecialchars($prod['image']) ?>" alt="<?= htmlspecialchars($prod['product_name']) ?>" onerror="this.src='uploads/placeholder.jpg'" />
            <h3><?= htmlspecialchars($prod['product_name']) ?></h3>
            <p>₱<?= number_format($prod['price'], 2) ?></p>
            <span><?= htmlspecialchars($prod['description']) ?></span>

            <?php if ($prod['stock_quantity'] > 0): ?>
              <?php if (isset($_SESSION['username'])): ?>
                <form method="POST" action="add_to_cart.php" style="margin-top:10px;">
                  <input type="hidden" name="product_id" value="<?= $prod['product_id'] ?>">
                  <input type="hidden" name="category" value="<?= $category_id ?>">

                  <div style="margin: 10px 0;">
                    <label style="font-weight: bold; margin-right: 10px;">Quantity:</label>
                    <div style="display: inline-flex; align-items: center; gap: 8px;">
                      <button type="button" onclick="decreaseQty(this)" style="
                        padding: 6px 10px;
                        font-size: 16px;
                        background-color: #eee;
                        border: 1px solid #ccc;
                        border-radius: 4px;
                        cursor: pointer;
                      ">−</button>

                      <input type="number" name="quantity" value="1" min="1" max="<?= $prod['stock_quantity'] ?>" style="
                        width: 50px;
                        text-align: center;
                        padding: 6px;
                        border: 1px solid #ccc;
                        border-radius: 4px;
                      " />

                      <button type="button" onclick="increaseQty(this)" style="
                        padding: 6px 10px;
                        font-size: 16px;
                        background-color: #eee;
                        border: 1px solid #ccc;
                        border-radius: 4px;
                        cursor: pointer;
                      ">+</button>
                    </div>
                  </div>
                  <button type="submit" class="btn">🛒 Add to Cart</button>
                </form>
              <?php else: ?>
                <p style="margin-top: 10px; font-style: italic; color: #666;">
                  <a href="login.php" style="color: #c00; text-decoration: underline; font-weight: 600;">Login</a> to add items to cart
                </p>
              <?php endif; ?>
            <?php else: ?>
              <p style="color: red; font-weight: bold; margin-top: 10px;">Out of Stock</p>
            <?php endif; ?>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p style="text-align:center; font-style:italic;">No products found in this category.</p>
      <?php endif; ?>
    </div>
  </section>

  <section class="banner">
    <img src="pictures/bg.jpg" alt="bg Kyla's Bistro" />
  </section>

  <script>
    function increaseQty(button) {
      const input = button.previousElementSibling;
      const max = parseInt(input.getAttribute('max'));
      let current = parseInt(input.value);
      if (current < max) {
        input.value = current + 1;
      }
    }

    function decreaseQty(button) {
      const input = button.nextElementSibling;
      let current = parseInt(input.value);
      if (current > 1) {
        input.value = current - 1;
      }
    }
  </script>
</body>
</html>