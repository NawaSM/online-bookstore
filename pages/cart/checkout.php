<?php
session_start();
require_once '../../config/database.php';  
require_once '../../includes/functions.php';
include '../../includes/header.php';


// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../pages/login.php');
    exit();
}

// Get user's shipping details
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get cart items
$stmt = $conn->prepare("
    SELECT ci.*, i.book_name, i.price, i.special_price, i.quantity as available_quantity, ci.remarks
    FROM cart_items ci
    JOIN inventory i ON ci.book_id = i.id
    WHERE ci.user_id = ?
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $price = $item['special_price'] ?? $item['price'];
    $subtotal += $price * $item['quantity'];
}

$discount = $_SESSION['applied_discount'] ?? 0;
$shipping = $subtotal > 150 ? 0 : 10;
$total = $subtotal - $discount + $shipping;

// Check if cart is empty
if (empty($cart_items)) {
    header('Location: onlinebookstore/pages/cart/cart.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Online Bookstore</title>
    <link rel="stylesheet" href="<?php echo getBaseUrl(); ?>pages/cart/cart.css">
    <style>
        .checkout-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        .checkout-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }

        .order-details, .shipping-details, .order-summary {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .item-row:last-child {
            border-bottom: none;
        }

        .address-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-top: 10px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .summary-row.total {
            font-weight: bold;
            font-size: 1.2em;
            border-top: 2px solid #eee;
            border-bottom: none;
            margin-top: 10px;
            padding-top: 15px;
        }

        .proceed-payment {
            background: #2ecc71;
            color: white;
            padding: 15px 25px;
            border: none;
            border-radius: 4px;
            width: 100%;
            font-size: 1.1em;
            cursor: pointer;
            margin-top: 20px;
        }

        .proceed-payment:hover {
            background: #27ae60;
        }

        .edit-button {
            color: #3498db;
            text-decoration: none;
            font-size: 0.9em;
        }

        .remarks-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-top: 10px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <h1>Checkout</h1>
        
        <div class="checkout-grid">
            <div class="left-column">
                <div class="order-details">
                    <div class="section-header">
                        <h2>Order Details</h2>
                        <a href="<?php echo getBaseUrl(); ?>pages/cart/cart.php" class="edit-button">Edit Cart</a>
                    </div>
                    <?php foreach ($cart_items as $item): ?>
                        <div class="item-row">
                            <div>
                                <strong><?php echo htmlspecialchars($item['book_name']); ?></strong> × <?php echo $item['quantity']; ?>
                            </div>
                            <div>
                                RM<?php echo number_format(($item['special_price'] ?? $item['price']) * $item['quantity'], 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="shipping-details">
                    <div class="section-header">
                        <h2>Shipping Address</h2>
                        <a href="<?php echo getBaseUrl(); ?>pages/account.php" class="edit-button">Edit Address</a>
                    </div>
                    <div class="address-box">
                        <p><strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong></p>
                        <p><?php echo htmlspecialchars($user['house_number']); ?></p>
                        <p><?php echo htmlspecialchars($user['street_name']); ?></p>
                        <p><?php echo htmlspecialchars($user['city'] . ', ' . $user['district']); ?></p>
                        <p><?php echo htmlspecialchars($user['state'] . ', ' . $user['country']); ?></p>
                    </div>
                </div>

                <?php if (!empty($cart_items[0]['remarks'])): ?>
                <div class="order-details">
                    <h2>Special Instructions</h2>
                    <div class="remarks-box">
                        <?php echo nl2br(htmlspecialchars($cart_items[0]['remarks'])); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="right-column">
                <div class="order-summary">
                    <h2>Order Summary</h2>
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>RM<?php echo number_format($subtotal, 2); ?></span>
                    </div>

                    <?php if ($discount > 0): ?>
                    <div class="summary-row">
                        <span>Discount:</span>
                        <span>-RM<?php echo number_format($discount, 2); ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="summary-row">
                        <span>Shipping:</span>
                        <span>RM<?php echo number_format($shipping, 2); ?></span>
                    </div>

                    <div class="summary-row total">
                        <span>Total:</span>
                        <span>RM<?php echo number_format($total, 2); ?></span>
                    </div>

                    <form action="<?php echo getBaseUrl(); ?>pages/cart/payment.php" method="POST">
                        <input type="hidden" name="amount" value="<?php echo $total; ?>">
                        <button type="submit" class="proceed-payment">Proceed to Payment</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    
</body>
</html>