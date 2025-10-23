<?php
session_start();
include("database.php");

// Check if user is logged in
if (!isset($_SESSION['username'])) {
  header("Location: login.php");
  exit();
}

$cart = $_SESSION['cart'] ?? [];

// Redirect if cart is empty
if (empty($cart)) {
  header("Location: cart.php");
  exit();
}

// Get customer info
$username = $_SESSION['username'];
$cust_stmt = $conn->prepare("SELECT customer_id, first_name, middle_name, last_name, email, phone_number, address FROM customer WHERE username = ?");
$cust_stmt->bind_param("s", $username);
$cust_stmt->execute();
$cust_result = $cust_stmt->get_result();
$customer = $cust_result->fetch_assoc();
$cust_stmt->close();

// Calculate totals
$subtotal = 0;
foreach ($cart as $item) {
  $subtotal += $item['price'] * $item['quantity'];
}
$total = $subtotal;

// Convert PHP to USD for PayPal (approximate conversion rate)
$total_usd = $total / 56;

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $payment_method = $_POST['payment_method'] ?? '';
  $delivery_address = $_POST['delivery_address'] ?? $customer['address'];
  $notes = $_POST['notes'] ?? '';
  $paypal_order_id = $_POST['paypal_order_id'] ?? null;

  if (!empty($payment_method)) {
    $conn->begin_transaction();

    try {
      // Insert order
      $order_stmt = $conn->prepare("INSERT INTO orders (customer_id, order_date, order_time, total_amount) VALUES (?, CURDATE(), CURTIME(), ?)");
      if (!$order_stmt) throw new Exception("Order prepare failed: " . $conn->error);
      $order_stmt->bind_param("id", $customer['customer_id'], $total);
      if (!$order_stmt->execute()) throw new Exception("Order execution failed: " . $order_stmt->error);
      $order_id = $conn->insert_id;
      $order_stmt->close();

      // Insert order items
      $item_stmt = $conn->prepare("INSERT INTO order_item (order_id, product_id, quantity, total_price) VALUES (?, ?, ?, ?)");
      if (!$item_stmt) throw new Exception("Order item prepare failed: " . $conn->error);

      foreach ($cart as $product_id => $item) {
        $quantity = $item['quantity'];
        $total_price = $item['price'] * $quantity;
        $item_stmt->bind_param("iiid", $order_id, $product_id, $quantity, $total_price);
        if (!$item_stmt->execute()) throw new Exception("Order item execution failed: " . $item_stmt->error);
      }
      $item_stmt->close();

      // Insert payment record
      // If PayPal and has order ID, mark as Paid, otherwise Pending
      $payment_status = ($payment_method === 'PayPal' && $paypal_order_id) ? 'Paid' : 'Pending';
      $payment_stmt = $conn->prepare("INSERT INTO payment (order_id, payment_date, payment_time, payment_method, payment_status, total_amount) VALUES (?, CURDATE(), CURTIME(), ?, ?, ?)");
      if (!$payment_stmt) throw new Exception("Payment prepare failed: " . $conn->error);
      $payment_stmt->bind_param("issd", $order_id, $payment_method, $payment_status, $total);
      if (!$payment_stmt->execute()) throw new Exception("Payment execution failed: " . $payment_stmt->error);
      $payment_stmt->close();

      // Generate receipt number
      $receipt_number = "RCP-" . date('Ymd') . "-" . str_pad($order_id, 6, '0', STR_PAD_LEFT);

      $conn->commit();

      // Store order details in session
      $_SESSION['last_order'] = [
        'order_id' => $order_id,
        'receipt_number' => $receipt_number,
        'items' => $cart,
        'total' => $total,
        'payment_method' => $payment_method,
        'delivery_address' => $delivery_address,
        'notes' => $notes,
        'customer_name' => trim($customer['first_name'] . ' ' . $customer['middle_name'] . ' ' . $customer['last_name']),
        'customer_email' => $customer['email'],
        'customer_phone' => $customer['phone_number']
      ];

      unset($_SESSION['cart']);
      header("Location: receipt.php?order_id=" . $order_id);
      exit();

    } catch (Exception $e) {
      $conn->rollback();
      $error_message = "Failed to place order: " . $e->getMessage();
    }
  } else {
    $error_message = "Please select a payment method.";
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Checkout | Kyla's Bistro</title>
  <!-- PayPal SDK -->
  <script src="https://www.paypal.com/sdk/js?client-id=Ad0jyZPG1bHR0wscGdYQqlX7AKF-Xr_F6JCKcJere-ZQRhC0PoDeH5_4InO2DrFV17eMD2byS6tPtvhp&currency=USD"></script>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #f9f6f2;
      color: #2c1810;
      margin: 0;
      padding: 0;
      min-height: 100vh;
    }

    .checkout-wrapper {
      max-width: 1200px;
      margin: 0 auto;
      padding: 40px 20px;
    }

    .header-section {
      margin-bottom: 30px;
    }

    .back-link {
      display: inline-block;
      padding: 10px 20px;
      background: #2c1810;
      color: white;
      text-decoration: none;
      border-radius: 8px;
      margin-bottom: 20px;
      font-weight: 600;
      transition: background-color 0.3s ease;
    }

    .back-link:hover {
      background: #3d2417;
    }

    .page-title {
      font-size: 32px;
      color: #8b4513;
      margin: 0 0 8px 0;
      font-weight: 700;
    }

    .page-subtitle {
      color: #666;
      font-size: 16px;
      margin: 0;
    }

    .checkout-content {
      display: grid;
      grid-template-columns: 1.3fr 1fr;
      gap: 30px;
    }

    .checkout-form,
    .order-summary {
      background: white;
      border-radius: 12px;
      padding: 30px;
      box-shadow: 0 6px 16px rgba(44, 24, 16, 0.1);
    }

    .section-heading {
      font-size: 22px;
      color: #2c1810;
      margin: 0 0 20px 0;
      padding-bottom: 12px;
      border-bottom: 3px solid #d4a574;
      font-weight: 700;
    }

    .form-section {
      margin-bottom: 25px;
    }

    .info-box {
      background: #f5e6d3;
      padding: 18px;
      border-radius: 8px;
      font-size: 15px;
      border-left: 4px solid #8b4513;
    }

    .info-row {
      display: flex;
      justify-content: space-between;
      padding: 6px 0;
      color: #2c1810;
    }

    .info-row strong {
      color: #2c1810;
      font-weight: 600;
    }

    .payment-options {
      display: grid;
      gap: 15px;
    }

    .payment-option {
      position: relative;
    }

    .payment-option input[type="radio"] {
      display: none;
    }

    .payment-label {
      display: flex;
      align-items: center;
      gap: 15px;
      padding: 16px 18px;
      background: #f9f6f2;
      border: 2px solid #d4a574;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .payment-option input[type="radio"]:checked + .payment-label {
      background: #f5e6d3;
      border-color: #8b4513;
      box-shadow: 0 4px 12px rgba(139, 69, 19, 0.15);
    }

    .payment-label:hover {
      border-color: #8b4513;
    }

    .payment-icon {
      font-size: 28px;
      min-width: 40px;
      text-align: center;
    }

    .payment-text {
      flex: 1;
    }

    .payment-name {
      font-weight: 700;
      font-size: 16px;
      color: #2c1810;
      margin-bottom: 3px;
    }

    .payment-desc {
      font-size: 13px;
      color: #666;
    }

    /* PayPal Section */
    .paypal-container {
      display: none;
      margin-top: 15px;
      padding: 20px;
      background: #fff;
      border: 2px solid #0070ba;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0, 112, 186, 0.1);
    }

    .paypal-container.active {
      display: block;
    }

    .paypal-header {
      text-align: center;
      margin-bottom: 15px;
      padding-bottom: 15px;
      border-bottom: 2px solid #f0f0f0;
    }

    .paypal-header h3 {
      color: #0070ba;
      font-size: 18px;
      margin: 0 0 5px 0;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }

    .paypal-header p {
      color: #666;
      font-size: 14px;
      margin: 0;
    }

    .paypal-amount {
      background: #f5f5f5;
      padding: 12px;
      border-radius: 6px;
      text-align: center;
      margin-bottom: 15px;
      font-size: 15px;
    }

    .paypal-amount strong {
      color: #0070ba;
      font-size: 18px;
    }

    #paypal-button-container {
      min-height: 50px;
    }

    .order-items {
      margin-bottom: 20px;
    }

    .order-item {
      display: flex;
      justify-content: space-between;
      padding: 12px 0;
      border-bottom: 1px solid #f0f0f0;
      font-size: 15px;
    }

    .order-item:last-child {
      border-bottom: none;
    }

    .item-details {
      flex: 1;
    }

    .item-name {
      font-weight: 600;
      color: #2c1810;
      margin-bottom: 4px;
    }

    .item-qty {
      font-size: 14px;
      color: #666;
    }

    .item-price {
      font-weight: 700;
      color: #8b4513;
    }

    .summary-totals {
      margin-top: 20px;
      padding-top: 20px;
      border-top: 2px solid #d4a574;
    }

    .summary-row {
      display: flex;
      justify-content: space-between;
      padding: 10px 0;
      font-size: 15px;
    }

    .summary-row.total {
      font-size: 22px;
      font-weight: 700;
      color: #2c1810;
      padding-top: 15px;
      margin-top: 10px;
      border-top: 3px solid #8b4513;
    }

    .place-order-btn {
      width: 100%;
      padding: 16px;
      background-color: #8b4513;
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 18px;
      font-weight: 700;
      cursor: pointer;
      margin-top: 20px;
      box-shadow: 0 6px 16px rgba(139, 69, 19, 0.3);
      transition: all 0.3s ease;
    }

    .place-order-btn:hover:not(:disabled) {
      background-color: #6d3610;
      transform: translateY(-2px);
    }

    .place-order-btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    .error-message {
      background: #fee;
      color: #c00;
      padding: 15px;
      margin-bottom: 20px;
      border-left: 4px solid #c00;
      border-radius: 8px;
      font-size: 15px;
    }

    .payment-note {
      background: #e8f4f8;
      padding: 12px;
      border-radius: 6px;
      margin-top: 15px;
      font-size: 13px;
      color: #555;
      border-left: 4px solid #17a2b8;
    }

    @media (max-width: 968px) {
      .checkout-content {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 576px) {
      .checkout-wrapper {
        padding: 20px 15px;
      }

      .checkout-form,
      .order-summary {
        padding: 20px;
      }

      .page-title {
        font-size: 26px;
      }

      .section-heading {
        font-size: 20px;
      }
    }
  </style>
</head>
<body>
  <div class="checkout-wrapper">
    <div class="header-section">
      <a href="cart.php" class="back-link">← Back to Cart</a>
      <h1 class="page-title">Checkout</h1>
      <p class="page-subtitle">Review your order and complete payment</p>
    </div>

    <?php if (isset($error_message)): ?>
      <div class="error-message"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <form method="POST" id="checkoutForm">
      <input type="hidden" name="paypal_order_id" id="paypal_order_id" value="">
      
      <div class="checkout-content">
        <div class="checkout-form">
          <h2 class="section-heading">Customer Information</h2>

          <div class="form-section">
            <div class="info-box">
              <div class="info-row">
                <span>Name:</span>
                <strong><?= htmlspecialchars(trim($customer['first_name'] . ' ' . $customer['middle_name'] . ' ' . $customer['last_name'])) ?></strong>
              </div>
              <div class="info-row">
                <span>Email:</span>
                <strong><?= htmlspecialchars($customer['email']) ?></strong>
              </div>
              <div class="info-row">
                <span>Phone:</span>
                <strong><?= htmlspecialchars($customer['phone_number']) ?></strong>
              </div>
              <div class="info-row">
                <span>Address:</span>
                <strong><?= htmlspecialchars($customer['address']) ?></strong>
              </div>
            </div>
          </div>

          <h2 class="section-heading">Payment Method</h2>

          <div class="payment-options">
            <!-- Cash on Pickup Option -->
            <div class="payment-option">
              <input type="radio" id="cop" name="payment_method" value="Cash on Pickup" required checked>
              <label for="cop" class="payment-label">
                <span class="payment-icon">💵</span>
                <div class="payment-text">
                  <div class="payment-name">Cash on Pickup</div>
                  <div class="payment-desc">Pay with cash when you pick up your order at the restaurant</div>
                </div>
              </label>
            </div>

            <!-- PayPal Option -->
            <div class="payment-option">
              <input type="radio" id="paypal_radio" name="payment_method" value="PayPal" required>
              <label for="paypal_radio" class="payment-label">
                <span class="payment-icon">💳</span>
                <div class="payment-text">
                  <div class="payment-name">PayPal</div>
                  <div class="payment-desc">Pay securely online with PayPal or credit/debit card</div>
                </div>
              </label>
            </div>
          </div>

          <!-- PayPal Payment Container (Hidden by default) -->
          <div class="paypal-container" id="paypalContainer">
            <div class="paypal-header">
              <h3>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="#0070ba">
                  <path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944 3.72a.77.77 0 0 1 .76-.633h8.625c2.23 0 3.716.4 4.547 1.223.814.804 1.188 2.003 1.11 3.56-.078 1.634-.502 2.993-1.265 4.046-.74 1.027-1.79 1.778-3.12 2.23-1.296.44-2.86.66-4.646.66H8.97a.641.641 0 0 0-.633.552l-1.261 6.02zm13.9-16.91c-.83-1.03-2.4-1.55-4.665-1.55H7.686a1.537 1.537 0 0 0-1.518 1.267L2.964 20.916a1.283 1.283 0 0 0 1.267 1.484h4.605a1.283 1.283 0 0 0 1.267-1.054l1.093-5.22h1.838c3.996 0 7.084-1.639 8.425-4.485.635-1.346.935-2.95.74-4.78-.195-1.83-.975-3.254-2.223-4.334z"/>
                </svg>
                Complete Payment with PayPal
              </h3>
              <p>You will be redirected to PayPal to complete your payment securely</p>
            </div>

            <div class="paypal-amount">
              <div style="color: #666; font-size: 13px; margin-bottom: 5px;">Total Amount</div>
              <div>
                <strong>$<?= number_format($total_usd, 2) ?> USD</strong>
                <span style="color: #999; font-size: 13px; margin-left: 8px;">(₱<?= number_format($total, 2) ?>)</span>
              </div>
            </div>

            <div id="paypal-button-container"></div>
          </div>

          <div class="payment-note">
            ℹ️ <strong>Note:</strong> For Cash on Pickup, you'll pay when you arrive at the restaurant. For PayPal, complete the secure payment now to confirm your order instantly.
          </div>
        </div>

        <div class="order-summary">
          <h2 class="section-heading">Order Summary</h2>

          <div class="order-items">
            <?php foreach ($cart as $id => $item): ?>
              <div class="order-item">
                <div class="item-details">
                  <div class="item-name"><?= htmlspecialchars($item['product_name']) ?></div>
                  <div class="item-qty"><?= $item['quantity'] ?> × ₱<?= number_format($item['price'], 2) ?></div>
                </div>
                <div class="item-price">
                  ₱<?= number_format($item['price'] * $item['quantity'], 2) ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="summary-totals">
            <div class="summary-row">
              <span>Subtotal:</span>
              <strong>₱<?= number_format($subtotal, 2) ?></strong>
            </div>
            <div class="summary-row total">
              <span>Total:</span>
              <strong>₱<?= number_format($total, 2) ?></strong>
            </div>
          </div>

          <button type="submit" class="place-order-btn" id="placeOrderBtn">
            Place Order
          </button>
        </div>
      </div>
    </form>
  </div>

  <script>
    const totalAmountUSD = <?= json_encode($total_usd) ?>;
    let paypalButtonRendered = false;

    // Handle payment method change
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
      radio.addEventListener('change', function() {
        const paypalContainer = document.getElementById('paypalContainer');
        const placeOrderBtn = document.getElementById('placeOrderBtn');
        
        if (this.value === 'PayPal') {
          // Show PayPal container
          paypalContainer.classList.add('active');
          placeOrderBtn.style.display = 'none';
          
          // Render PayPal button only once
          if (!paypalButtonRendered) {
            renderPayPalButtons();
            paypalButtonRendered = true;
          }
        } else {
          // Hide PayPal container, show regular submit button
          paypalContainer.classList.remove('active');
          placeOrderBtn.style.display = 'block';
        }
      });
    });

    function renderPayPalButtons() {
      paypal.Buttons({
        style: {
          layout: 'vertical',
          color: 'gold',
          shape: 'rect',
          label: 'paypal',
          height: 45
        },
        
        createOrder: function(data, actions) {
          return actions.order.create({
            purchase_units: [{
              amount: {
                value: totalAmountUSD.toFixed(2),
                currency_code: 'USD'
              },
              description: 'Kyla\'s Bistro - Food Order'
            }]
          });
        },
        
        onApprove: function(data, actions) {
          return actions.order.capture().then(function(details) {
            // Store PayPal order ID
            document.getElementById('paypal_order_id').value = data.orderID;
            
            // Show success message
            alert('✅ Payment successful! Processing your order...');
            
            // Submit the form
            document.getElementById('checkoutForm').submit();
          });
        },
        
        onError: function(err) {
          console.error('PayPal Error:', err);
          alert('❌ Payment failed. Please try again or select Cash on Pickup.');
        },
        
        onCancel: function(data) {
          alert('Payment was cancelled. You can try again or choose Cash on Pickup.');
        }
      }).render('#paypal-button-container');
    }

    // Handle form submission for Cash on Pickup
    document.getElementById('checkoutForm').addEventListener('submit', function(e) {
      const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
      
      if (!paymentMethod) {
        e.preventDefault();
        alert('Please select a payment method');
        return false;
      }
      
      // Prevent submission if PayPal is selected but not completed
      if (paymentMethod.value === 'PayPal' && !document.getElementById('paypal_order_id').value) {
        e.preventDefault();
        alert('Please complete the PayPal payment by clicking the PayPal button above.');
        return false;
      }
      
      // For Cash on Pickup
      if (paymentMethod.value === 'Cash on Pickup') {
        const btn = document.getElementById('placeOrderBtn');
        btn.disabled = true;
        btn.textContent = 'Processing Order...';
      }
    });
  </script>
</body>
</html>