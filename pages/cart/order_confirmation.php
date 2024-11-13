<?php
session_start();
require_once '../../config/database.php';  
require_once '../../includes/functions.php';
include '../../includes/header.php';


if (!isset($_SESSION['user_id']) || !isset($_GET['order_id'])) {
    header('Location: onlinebookstore/pages/cart/cart.php');
    exit();
}

$order_id = intval($_GET['order_id']);
$user_id = $_SESSION['user_id'];

// Fetch order details
$stmt = $conn->prepare("
    SELECT o.*, u.first_name, u.last_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: onlinebookstore/pages/cart/cart.php');
    exit();
}

// Fetch order items
$stmt = $conn->prepare("
    SELECT oi.*, i.book_name
    FROM order_items oi
    JOIN inventory i ON oi.inventory_id = i.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Online Bookstore</title>
    <link rel="stylesheet" href="<?php echo getBaseUrl(); ?>pages/cart/cart.css">
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 30px;
        }

        .success-message h2 {
            color: #155724;
            margin-bottom: 10px;
        }

        .order-info {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .order-number {
            font-size: 1.2em;
            color: #666;
            margin-bottom: 20px;
        }

        .confirmation-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .confirmation-item:last-child {
            border-bottom: none;
        }

        .next-steps {
            text-align: center;
            margin-top: 30px;
        }

        .next-steps .btn {
            margin: 10px;
            padding: 12px 25px;
        }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <div class="success-message">
            <h2>Thank You for Your Order!</h2>
            <p>Your order has been successfully placed and is being processed.</p>
        </div>

        <div class="order-info">
            <div class="order-number">
                Order #<?php echo str_pad($order['id'], 8, '0', STR_PAD_LEFT); ?>
            </div>

            <h3>Order Details</h3>
            

            <div class="summary-details">
                <?php
                // Calculate the original subtotal (before any discounts)
                $original_subtotal = 0;
                foreach ($order_items as $item) {
                    $original_subtotal += $item['price'] * $item['quantity'];
                }
                
                // Determine shipping (based on original subtotal before discounts)
                $shipping = $original_subtotal >= 150 ? 0 : 10;
                
                // Final total calculation
                $final_total = $original_subtotal - $order['discount_amount'] + $shipping;
                ?>
                <!-- Original prices per item -->
                <div class="item-list">
                    <?php foreach ($order_items as $item): ?>
                        <div class="confirmation-item">
                            <div>
                                <strong><?php echo htmlspecialchars($item['book_name']); ?></strong>
                                <span class="quantity">× <?php echo $item['quantity']; ?></span>
                            </div>
                            <div>RM<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Order summary -->
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>RM<?php echo number_format($original_subtotal, 2); ?></span>
                </div>
                
                <?php if ($order['discount_amount'] > 0): ?>
                <div class="summary-row discount">
                    <span>Discount:</span>
                    <span>-RM<?php echo number_format($order['discount_amount'], 2); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="summary-row shipping">
                    <span>Shipping:</span>
                    <span>RM<?php echo number_format($shipping, 2); ?></span>
                </div>
                
                <div class="summary-row total">
                    <span>Total:</span>
                    <span>RM<?php echo number_format($final_total, 2); ?></span>
                </div>
            </div>
        </div>

        <div class="next-steps">
            <p>A confirmation email has been sent to your registered email address.</p>
            <a href="<?php echo getBaseUrl(); ?>index.php" class="btn btn-primary">Continue Shopping</a>
            <a href="../profile/orders.php" class="btn btn-secondary">View My Orders</a>
        </div>
    </div>
    <?php include '../../includes/footer.php'; ?>

</body>
</html>