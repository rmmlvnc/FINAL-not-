<?php
session_start();

// Get item ID from URL
$item_id = $_GET['id'] ?? null;

if (!$item_id || !isset($_SESSION['cart'][$item_id])) {
  header('Location: cart.php');
  exit();
}

$item = $_SESSION['cart'][$item_id];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $new_quantity = intval($_POST['quantity'] ?? 1);
  
  if ($new_quantity > 0) {
    $_SESSION['cart'][$item_id]['quantity'] = $new_quantity;
    header('Location: cart.php');
    exit();
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Edit Order | Kyla's Bistro</title>
  <link rel="stylesheet" href="style.css" />
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(135deg, #fff7f0 0%, #ffe0e0 100%);
      margin: 0;
      padding: 0;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .edit-wrapper {
      max-width: 600px;
      width: 100%;
      margin: 40px 20px;
    }

    .edit-card {
      background: white;
      border-radius: 16px;
      padding: 40px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.12);
      animation: slideUp 0.4s ease;
    }

    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .edit-header {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-bottom: 30px;
      padding-bottom: 20px;
      border-bottom: 3px solid #c00;
    }

    .edit-icon {
      font-size: 2.5rem;
    }

    .edit-title {
      font-size: 2rem;
      color: #253745;
      margin: 0;
    }

    .product-preview {
      display: flex;
      gap: 20px;
      margin-bottom: 30px;
      padding: 20px;
      background: #f8f9fa;
      border-radius: 12px;
    }

    .preview-image {
      width: 120px;
      height: 120px;
      object-fit: cover;
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .preview-details {
      flex: 1;
    }

    .preview-name {
      font-size: 1.5rem;
      font-weight: 700;
      color: #253745;
      margin-bottom: 10px;
    }

    .preview-price {
      font-size: 1.3rem;
      color: #c00;
      font-weight: 600;
    }

    .form-group {
      margin-bottom: 30px;
    }

    .form-label {
      display: block;
      font-size: 1rem;
      font-weight: 600;
      color: #253745;
      margin-bottom: 15px;
    }

    .quantity-controls {
      display: flex;
      align-items: center;
      gap: 15px;
      justify-content: center;
      background: #f8f9fa;
      padding: 20px;
      border-radius: 12px;
    }

    .qty-btn {
      width: 50px;
      height: 50px;
      border: none;
      background: #c00;
      color: white;
      border-radius: 10px;
      font-size: 1.5rem;
      font-weight: bold;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .qty-btn:hover {
      background: #a00;
      transform: scale(1.1);
    }

    .qty-btn:active {
      transform: scale(0.95);
    }

    .qty-input {
      width: 100px;
      height: 60px;
      border: 3px solid #e0e0e0;
      border-radius: 10px;
      text-align: center;
      font-size: 1.8rem;
      font-weight: 700;
      color: #253745;
    }

    .qty-input:focus {
      outline: none;
      border-color: #c00;
    }

    .total-section {
      background: linear-gradient(135deg, #253745 0%, #2f4f4f 100%);
      padding: 25px;
      border-radius: 12px;
      margin-bottom: 30px;
      text-align: center;
    }

    .total-label {
      color: rgba(255, 255, 255, 0.8);
      font-size: 1rem;
      margin-bottom: 10px;
    }

    .total-amount {
      color: white;
      font-size: 2.5rem;
      font-weight: 700;
    }

    .action-buttons {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
    }

    .btn {
      padding: 16px;
      border: none;
      border-radius: 25px;
      font-size: 1.1rem;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.3s ease;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .btn-save {
      background: linear-gradient(135deg, #c00 0%, #a00 100%);
      color: white;
      box-shadow: 0 6px 16px rgba(204, 0, 0, 0.3);
    }

    .btn-save:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(204, 0, 0, 0.4);
    }

    .btn-cancel {
      background: #e0e0e0;
      color: #555;
      text-decoration: none;
    }

    .btn-cancel:hover {
      background: #d0d0d0;
      transform: translateY(-2px);
    }

    @media (max-width: 576px) {
      .edit-card {
        padding: 25px;
      }

      .product-preview {
        flex-direction: column;
        align-items: center;
        text-align: center;
      }

      .action-buttons {
        grid-template-columns: 1fr;
      }

      .edit-title {
        font-size: 1.5rem;
      }

      .preview-name {
        font-size: 1.2rem;
      }
    }
  </style>
</head>
<body>
  <div class="edit-wrapper">
    <div class="edit-card">
      <div class="edit-header">
        <span class="edit-icon">✏️</span>
        <h1 class="edit-title">Edit Your Order</h1>
      </div>

      <div class="product-preview">
        <img src="pictures/<?= htmlspecialchars($item['image'] ?? 'placeholder.jpg') ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" class="preview-image" onerror="this.src='pictures/placeholder.jpg'">
        <div class="preview-details">
          <div class="preview-name"><?= htmlspecialchars($item['product_name']) ?></div>
          <div class="preview-price">₱<?= number_format($item['price'], 2) ?> each</div>
        </div>
      </div>

      <form method="POST">
        <div class="form-group">
          <label class="form-label">Adjust Quantity</label>
          <div class="quantity-controls">
            <button type="button" class="qty-btn" onclick="decreaseQty()">−</button>
            <input type="number" name="quantity" id="quantity" class="qty-input" value="<?= $item['quantity'] ?>" min="1" max="99" readonly>
            <button type="button" class="qty-btn" onclick="increaseQty()">+</button>
          </div>
        </div>

        <div class="total-section">
          <div class="total-label">Order Total</div>
          <div class="total-amount" id="total">₱<?= number_format($item['price'] * $item['quantity'], 2) ?></div>
        </div>

        <div class="action-buttons">
          <button type="submit" class="btn btn-save">
            ✓ Save Changes
          </button>
          <a href="cart.php" class="btn btn-cancel">
            ✕ Cancel
          </a>
        </div>
      </form>
    </div>
  </div>

  <script>
    const price = <?= $item['price'] ?>;
    const qtyInput = document.getElementById('quantity');
    const totalDisplay = document.getElementById('total');

    function updateTotal() {
      const qty = parseInt(qtyInput.value);
      const total = price * qty;
      totalDisplay.textContent = '₱' + total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }

    function increaseQty() {
      let currentQty = parseInt(qtyInput.value);
      if (currentQty < 99) {
        qtyInput.value = currentQty + 1;
        updateTotal();
      }
    }

    function decreaseQty() {
      let currentQty = parseInt(qtyInput.value);
      if (currentQty > 1) {
        qtyInput.value = currentQty - 1;
        updateTotal();
      }
    }

    // Allow manual input
    qtyInput.addEventListener('input', function() {
      let value = parseInt(this.value);
      if (isNaN(value) || value < 1) {
        this.value = 1;
      } else if (value > 99) {
        this.value = 99;
      }
      updateTotal();
    });
  </script>
</body>
</html>