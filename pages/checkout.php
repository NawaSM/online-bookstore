<?php

session_start();
require_once '../config/database.php';
require_once '../config/stripe.php';
require_once '../config/currency.php';
require_once(__DIR__ . '/../config.php');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug checkpoints
echo "<!-- Debug: Starting checkout page -->";

if (!isset($_SESSION['user_id'])) {
    echo "<!-- Debug: No user session -->";
    $_SESSION['redirect_url'] = '../pages/checkout.php';
    header('Location: login.php');
    exit;
}

echo "<!-- Debug: User session found -->";

try {
    $stmt = $conn->prepare("SELECT 1");
    $stmt->execute();
    echo "<!-- Debug: Database connection successful -->";
} catch (PDOException $e) {
    echo "<!-- Debug: Database error: " . htmlspecialchars($e->getMessage()) . " -->";
    exit;
}

echo "<!-- Debug: About to fetch cart items -->";


// Fetch cart items
$stmt = $conn->prepare("
    SELECT 
        c.id as cart_id,
        c.quantity,
        i.id as book_id,
        i.book_name,
        i.price,
        i.is_special,
        i.special_price
    FROM cart_items c
    JOIN inventory i ON c.book_id = i.id
    WHERE c.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals in MYR
$subtotal = 0;
$shipping_fee = 10.00; // Fixed shipping fee in MYR

foreach ($cartItems as $item) {
    $price = $item['is_special'] && $item['special_price'] 
        ? $item['special_price'] 
        : $item['price'];
    $subtotal += $price * $item['quantity'];
}

// Apply any discounts
$discount = isset($_SESSION['promo_discount']) ? $_SESSION['promo_discount'] : 0;
$total = $subtotal + $shipping_fee - $discount;

// Fetch user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - NawLexKen Books</title>
    
    <meta http-equiv="Content-Security-Policy" content="default-src 'self' https://*.stripe.com https://*.stripe.network https://*.kaspersky-labs.com; 
        script-src 'self' 'unsafe-inline' 'unsafe-eval' https://*.stripe.com https://*.stripe.network;
        style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com;
        font-src 'self' https://cdnjs.cloudflare.com;
        img-src 'self' data: https://*.stripe.com;
        connect-src 'self' https://*.stripe.com https://*.stripe.network https://r.stripe.com;
        frame-src 'self' https://*.stripe.com https://*.stripe.network;">
    
    <!-- Updated CSS paths with BASE_URL -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/layout.css">
    <link rel="stylesheet" href="/onlinebookstore/assets/css/style.css" type="text/css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://js.stripe.com/v3/"></script>
    <script>
        const stripe = Stripe('<?php echo STRIPE_PUBLISHABLE_KEY; ?>');
        const TOTAL_AMOUNT = <?php echo $total; ?>;
    </script>
    <script src="<?php echo BASE_URL; ?>/assets/js/stripe.js"></script>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="checkout-container">
        <h1>Checkout</h1>

        <div class="checkout-grid">
            <div class="checkout-form">
               <form id="payment-form" action="https://localhost/onlinebookstorepages/pages/process_payment.php" method="POST">
                    <div class="form-section">
                        <h2>Shipping Information</h2>
                        <div class="form-group">
                            <label for="shipping_name">Full Name</label>
                            <input type="text" id="shipping_name" name="shipping_name" 
                                   value="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="shipping_email">Email</label>
                                <input type="email" id="shipping_email" name="shipping_email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="shipping_phone">Phone</label>
                                <input type="tel" id="shipping_phone" name="shipping_phone" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="shipping_address">Address</label>
                            <input type="text" id="shipping_address" name="shipping_address" 
                                   value="<?php echo htmlspecialchars($user['house_number'] . ' ' . $user['street_name']); ?>" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="shipping_city">City</label>
                                <input type="text" id="shipping_city" name="shipping_city" 
                                       value="<?php echo htmlspecialchars($user['city']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="shipping_state">State</label>
                                <input type="text" id="shipping_state" name="shipping_state" 
                                       value="<?php echo htmlspecialchars($user['state']); ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="shipping_country">Country</label>
                                <input type="text" id="shipping_country" name="shipping_country" 
                                       value="<?php echo htmlspecialchars($user['country']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="shipping_zip">ZIP Code</label>
                                <input type="text" id="shipping_zip" name="shipping_zip" required>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Information Section -->
                    <div class="form-section">
                        <h2>Payment Method</h2>
                        <div class="payment-methods">
                            <label class="payment-method">
                                <input type="radio" name="payment_method" value="credit_card" checked>
                                <i class="fas fa-credit-card"></i>
                                Credit Card
                            </label>
                            <label class="payment-method">
                                <input type="radio" name="payment_method" value="paypal">
                                <i class="fab fa-paypal"></i>
                                PayPal
                            </label>
                        </div>

                        <div id="cardInputs" class="card-inputs active">
                            <div class="form-group">
                                <label for="card-element">Credit or Debit Card</label>
                                <div id="card-element" class="form-control"></div>
                                <div id="card-errors" role="alert"></div>
                            </div>
                            <button type="submit" class="checkout-btn">
                                <span class="button-text">Pay RM <?php echo number_format($total, 2); ?></span>
                                <div class="spinner hidden"></div>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Order Summary Section -->
            <div class="order-summary">
                <h2>Order Summary</h2>

                <div class="order-items">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="order-item">
                            <span><?php echo htmlspecialchars($item['book_name']); ?> × <?php echo $item['quantity']; ?></span>
                            <span>RM <?php 
                                $price = $item['is_special'] && $item['special_price'] 
                                    ? $item['special_price'] 
                                    : $item['price'];
                                echo number_format($price * $item['quantity'], 2); 
                            ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="summary-item">
                    <span>Subtotal</span>
                    <span>RM <?php echo number_format($subtotal, 2); ?></span>
                </div>

                <div class="summary-item">
                    <span>Shipping</span>
                    <span>RM <?php echo number_format($shipping_fee, 2); ?></span>
                </div>

                <?php if (isset($_SESSION['promo_discount'])): ?>
                    <div class="summary-item">
                        <span>Discount</span>
                        <span>-RM <?php echo number_format($discount, 2); ?></span>
                    </div>
                <?php endif; ?>

                <div class="summary-item summary-total">
                    <span>Total</span>
                    <span>RM <?php echo number_format($total, 2); ?></span>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>