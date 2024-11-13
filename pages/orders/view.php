<?php
session_start();
require_once('../../config/database.php');

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header('Location: ' . getBaseUrl() . 'pages/login.php');
    exit();
}

include('../../includes/header.php');

$orderId = $_GET['id'];
$userId = $_SESSION['user_id'];

try {
    $stmt = $conn->prepare("
        SELECT o.*, oi.*, i.book_name, i.author, i.image_data, i.image_type
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN inventory i ON oi.inventory_id = i.id
        WHERE o.id = :orderId AND o.user_id = :userId
    ");
    $stmt->execute(['orderId' => $orderId, 'userId' => $userId]);
    $orderItems = $stmt->fetchAll();

    if (empty($orderItems)) {
        throw new Exception('Order not found');
    }
    
    $order = [
        'id' => $orderItems[0]['id'],
        'status' => $orderItems[0]['status'],
        'total_amount' => $orderItems[0]['total_amount'],
        'discount_amount' => $orderItems[0]['discount_amount'],
        'created_at' => $orderItems[0]['created_at']
    ];
} catch (Exception $e) {
    header('Location: ' . getBaseUrl() . 'pages/orders/');
    exit();
}
?>

<div class="order-details-container">
    <div class="order-header">
        <h1>Order #<?php echo $orderId; ?></h1>
        <div class="order-meta">
            <span class="order-date">
                Ordered on: <?php echo date('F j, Y', strtotime($order['created_at'])); ?>
            </span>
            <span class="order-status status-<?php echo $order['status']; ?>">
                <?php echo ucfirst($order['status']); ?>
            </span>
        </div>
    </div>

    <div class="order-items">
        <?php foreach ($orderItems as $item): ?>
            <div class="order-item">
                <div class="item-image">
                    <?php if ($item['image_data']): ?>
                        <img src="data:<?php echo $item['image_type']; ?>;base64,<?php 
                            echo base64_encode($item['image_data']); ?>" 
                            alt="<?php echo htmlspecialchars($item['book_name']); ?>">
                    <?php else: ?>
                        <img src="<?php echo getBaseUrl(); ?>assets/images/book-placeholder.jpg" 
                             alt="Book placeholder">
                    <?php endif; ?>
                </div>
                <div class="item-details">
                    <h3><?php echo htmlspecialchars($item['book_name']); ?></h3>
                    <p class="author">By <?php echo htmlspecialchars($item['author']); ?></p>
                    <div class="item-meta">
                        <span class="quantity">Qty: <?php echo $item['quantity']; ?></span>
                        <span class="price"><?php echo formatPrice($item['price']); ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="order-summary">
        <div class="summary-row">
            <span>Subtotal:</span>
            <span><?php echo formatPrice($order['total_amount'] + $order['discount_amount']); ?></span>
        </div>
        <?php if ($order['discount_amount'] > 0): ?>
            <div class="summary-row discount">
                <span>Discount:</span>
                <span>-<?php echo formatPrice($order['discount_amount']); ?></span>
            </div>
        <?php endif; ?>
        <div class="summary-row total">
            <span>Total:</span>
            <span><?php echo formatPrice($order['total_amount']); ?></span>
        </div>
    </div>

    <?php if ($order['status'] === 'shipped'): ?>
        <div class="tracking-section">
            <h2>Track Your Order</h2>
            <div id="trackingTimeline" class="tracking-timeline">
                <!-- Tracking info will be loaded via JavaScript -->
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include('../../includes/footer.php'); ?>