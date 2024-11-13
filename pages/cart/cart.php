<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/currency.php';
require_once '../../includes/functions.php';
include '../../includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../pages/login.php');
    exit();
}

// Get cart items for initial display
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("  
    SELECT ci.*, i.book_name, i.price, i.special_price, i.quantity as available_quantity
    FROM cart_items ci
    JOIN inventory i ON ci.book_id = i.id
    WHERE ci.user_id = ?
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate initial total
$total = 0;
foreach ($cart_items as $item) {
    $price = $item['special_price'] ?? $item['price'];
    $total += $price * $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Online Bookstore</title>
    <base href="<?php echo getBaseUrl(); ?>">
    <link rel="stylesheet" href="<?php echo getBaseUrl(); ?>assets/css/layout.css">
    <link rel="stylesheet" href="<?php echo getBaseUrl(); ?>pages/cart/cart.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
</head>
<body>
    <div class="container">
        <h1>Shopping Cart</h1>

        <!-- Cart Section -->
        <div id="cart-container">
            <?php if (empty($cart_items)): ?>
                <div class="empty-cart">
                    <p>Your cart is empty</p>
                    <a href="<?php echo getBaseUrl(); ?>index.php" class="btn">Continue Shopping</a>
                </div>
            <?php else: ?>
                <div class="cart-items">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item" data-id="<?php echo $item['book_id']; ?>">
                            <div class="item-details">
                                <h3><?php echo htmlspecialchars($item['book_name']); ?></h3>
                                <div class="price-info">
                                    <?php if (isset($item['special_price'])): ?>
                                        <span class="special-price">RM<?php echo number_format($item['special_price'], 2); ?></span>
                                        <span class="original-price">RM<?php echo number_format($item['price'], 2); ?></span>
                                    <?php else: ?>
                                        <span class="price">RM<?php echo number_format($item['price'], 2); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="quantity-controls">
                                <button type="button" class="quantity-btn minus">-</button>
                                <input type="number" class="quantity-input" 
                                       value="<?php echo $item['quantity']; ?>" 
                                       min="1" 
                                       max="<?php echo $item['available_quantity']; ?>">
                                <button type="button" class="quantity-btn plus">+</button>
                                <button type="button" class="remove-btn">Remove</button>
                            </div>

                            <div class="item-total">
                                RM<?php echo number_format(($item['special_price'] ?? $item['price']) * $item['quantity'], 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="shipping-note">
                    <?php if ($total < 150): ?>
                    <p>Add RM<?php echo number_format(150 - $total, 2); ?> more to your cart for free shipping!</p>
                    <?php else: ?>
                    <p>Your order qualifies for free shipping!</p>
                    <?php endif; ?>
                </div>

                <div class="cart-extras">
                    <!-- Remarks Section -->
                    <div class="remarks-section">
                        <h3>Special Instructions</h3>
                        <textarea id="cart-remarks" placeholder="Add any special instructions for your order"><?php echo isset($cart_items[0]['remarks']) ? htmlspecialchars($cart_items[0]['remarks']) : ''; ?></textarea>
                        <button class="btn btn-secondary save-remarks">Save Instructions</button>
                    </div>

                    <!-- Promo Code Section -->
                    <div class="promo-section">
                        <h3>Promo Code</h3>
                        <div class="promo-input">
                            <input type="text" id="promo-code" placeholder="Enter promo code">
                            <button class="btn btn-secondary apply-promo">Apply</button>
                        </div>
                        <div id="promo-message"></div>
                    </div>
                </div>

                <div class="cart-summary">
                    <div class="summary-details">
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span>RM<?php echo number_format($total, 2); ?></span>
                        </div>
                        
                        <?php if (isset($_SESSION['applied_discount'])): ?>
                        <div class="summary-row discount">
                            <span>Discount:</span>
                            <span>-RM<?php echo number_format($_SESSION['applied_discount'], 2); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="summary-row shipping">
                            <span>Shipping:</span>
                            <span>RM<?php echo $total > 150 ? '0.00' : '10.00'; ?></span>
                        </div>
                        
                        <div class="summary-row total">
                            <span>Total:</span>
                            <span>RM<?php 
                                $shipping = $total > 150 ? 0 : 10;
                                $final_total = $total - ($_SESSION['applied_discount'] ?? 0) + $shipping;
                                echo number_format($final_total, 2); 
                            ?></span>
                        </div>
                    </div>
                    
                    <div class="cart-actions">
                        <button id="clear-cart" class="btn btn-secondary">Clear Cart</button>
                        <a href="/onlinebookstore/pages/cart/checkout.php" class="btn btn-primary">Proceed to Checkout</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="notification" class="notification"></div>
    
    <?php include '../../includes/footer.php'; ?>

    <script>
        const basePath = '<?php echo BASE_URL; ?>';
    </script>
    <script src="<?php echo getBaseUrl(); ?>pages/cart/cart.js"></script>
</body>
</html>