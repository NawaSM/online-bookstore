<?php
session_start();
require_once '../../config/database.php';  
require_once '../../includes/functions.php';
require_once '../../vendor/autoload.php';
include '../../includes/header.php';

// Get Stripe keys from config
$stripe_config = require_once '../../config/stripe-config.php';
$stripe_publishable_key = $stripe_config['publishable_key'];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get cart items and total
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT ci.*, i.book_name, i.price, i.special_price 
    FROM cart_items ci
    JOIN inventory i ON ci.book_id = i.id
    WHERE ci.user_id = ?
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate subtotal
$subtotal = 0;
foreach ($cart_items as $item) {
    $price = $item['special_price'] ?? $item['price'];
    $subtotal += $price * $item['quantity'];
}

// Calculate total with shipping and discount
$discount = $_SESSION['applied_discount'] ?? 0;
$shipping = $subtotal > 150 ? 0 : 10;  // Free shipping over RM150
$total = $subtotal - $discount + $shipping;

// Convert to cents for Stripe
$amount_in_cents = $total * 100;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Online Bookstore</title>
    <link rel="stylesheet" href="<?php echo getBaseUrl(); ?>pages/cart/cart.css">
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        /* Additional styles specific to payment page */
        .payment-container {
            max-width: 800px;
            margin: 90px auto 20px;
            padding: 20px;
        }

        #payment-form {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 20px;
        }

        #card-element {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
            margin-bottom: 20px;
        }

        #card-errors {
            color: #dc3545;
            margin-bottom: 15px;
            font-size: 0.9em;
        }

        .item-list {
            margin-bottom: 20px;
        }

        .payment-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .payment-item:last-child {
            border-bottom: none;
        }

        /* Match the original payment page layout */
        .order-summary {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .card-details {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        #payment-form {
            margin-top: 20px;
        }

        /* Match the button style from the original */
        .submit-button {
            width: 100%;
            padding: 15px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 15px;
        }

        .submit-button:hover {
            background: #45a049;
        }

        .payment-total {
            font-size: 1.2em;
            font-weight: bold;
            text-align: right;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #eee;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <h1>Payment Details</h1>

        <!-- Order Summary -->
        <div class="summary-box">
            <h2>Order Summary</h2>
            <div class="item-list">
                <?php foreach ($cart_items as $item): ?>
                    <div class="payment-item">
                        <div>
                            <strong><?php echo htmlspecialchars($item['book_name']); ?></strong>
                            <span class="quantity">× <?php echo $item['quantity']; ?></span>
                        </div>
                        <div>RM<?php echo number_format(($item['special_price'] ?? $item['price']) * $item['quantity'], 2); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="summary-details">
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>RM<?php echo number_format($subtotal, 2); ?></span>
                </div>
                
                <?php if ($discount > 0): ?>
                <div class="summary-row discount">
                    <span>Discount:</span>
                    <span>-RM<?php echo number_format($discount, 2); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="summary-row shipping">
                    <span>Shipping:</span>
                    <span>RM<?php echo number_format($shipping, 2); ?></span>
                </div>
                
                <div class="summary-row total">
                    <span>Total:</span>
                    <span>RM<?php echo number_format($total, 2); ?></span>
                </div>
            </div>
        </div>

        <!-- Payment Form -->
        <form id="payment-form">
            <h2>Card Details</h2>
            <div class="form-group">
                <label for="card-element">Credit or Debit Card</label>
                <div id="card-element"></div>
                <div id="card-errors" role="alert"></div>
            </div>

            <button type="submit" class="submit-button">Pay RM<?php echo number_format($total, 2); ?></button>
        </form>
    </div>

    <script>
        const basePath = '<?php echo BASE_URL; ?>';
    </script>

    <script>
        // Create a Stripe client
        var stripe = Stripe('<?php echo $stripe_publishable_key; ?>');
        var elements = stripe.elements();

        // Create card Element
        var card = elements.create('card');
        card.mount('#card-element');

        // Handle form submission
        var form = document.getElementById('payment-form');
        form.addEventListener('submit', function(event) {
            event.preventDefault();

            stripe.createToken(card).then(function(result) {
                if (result.error) {
                    // Display error
                    var errorElement = document.getElementById('card-errors');
                    errorElement.textContent = result.error.message;
                } else {
                    // Send token to server
                    stripeTokenHandler(result.token);
                }
            });
        });

        function stripeTokenHandler(token) {
            // Create hidden input with token
            var form = document.getElementById('payment-form');
            var hiddenInput = document.createElement('input');
            hiddenInput.setAttribute('type', 'hidden');
            hiddenInput.setAttribute('name', 'stripeToken');
            hiddenInput.setAttribute('value', token.id);
            form.appendChild(hiddenInput);

            // Submit the form
            fetch(`${basePath}/pages/cart/process_payment.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'stripeToken=' + token.id + '&amount=<?php echo $amount_in_cents; ?>'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = `${basePath}/pages/cart/order_confirmation.php?order_id=${data.order_id}`;
                } else {
                    document.getElementById('card-errors').textContent = data.error;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('card-errors').textContent = 'An error occurred. Please try again.';
            });
        }
    </script>
    <?php include '../../includes/footer.php'; ?>
</body>
</html>