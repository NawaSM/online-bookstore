<?php
require_once '../../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . getBaseUrl() . 'pages/login.php');
    exit();
}

// Debug check
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get user's orders
$userId = $_SESSION['user_id'];
try {
    // Check if database connection exists
    if (!isset($conn) || !$conn) {
        throw new Exception("Database connection not found");
    }

    // First query to get orders
    $query = "SELECT DISTINCT o.* 
              FROM orders o 
              WHERE o.user_id = ? 
              ORDER BY o.created_at DESC";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Failed to prepare orders query");
    }

    $stmt->execute([$userId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug output
    echo "<!-- Number of orders found: " . count($orders) . " -->";
    
    // Get order items for each order
    $orderItems = [];
    if (!empty($orders)) {
        $orderIds = array_column($orders, 'id');
        $placeholders = str_repeat('?,', count($orderIds) - 1) . '?';
        
        $itemsQuery = "
            SELECT 
                oi.id as item_id,
                oi.order_id,
                oi.inventory_id,
                oi.quantity,
                oi.price,
                i.book_name,
                i.author,
                i.isbn,
                i.image_data,
                (oi.quantity * oi.price) as total_price
            FROM order_items oi
            INNER JOIN inventory i ON oi.inventory_id = i.id
            WHERE oi.order_id IN ($placeholders)
            ORDER BY oi.order_id, i.book_name
        ";
        
        $itemsStmt = $conn->prepare($itemsQuery);
        if (!$itemsStmt) {
            throw new Exception("Failed to prepare items query");
        }

        $itemsStmt->execute($orderIds);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Organize items by order_id
        foreach ($items as $item) {
            $orderId = $item['order_id'];
            if (!isset($orderItems[$orderId])) {
                $orderItems[$orderId] = [];
            }
            $orderItems[$orderId][] = $item;
        }
    }
    
    // Organize orders by status
    $ordersByStatus = [
        'pending' => [],
        'processing' => [],
        'shipped' => [],
        'delivered' => [],
        'cancelled' => []
    ];
    
    foreach ($orders as $order) {
        $order['items'] = $orderItems[$order['id']] ?? [];
        $ordersByStatus[$order['status']][] = $order;
    }
    
    // Count orders by status
    $statusCounts = array_map('count', $ordersByStatus);
    
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo "<!-- Error: " . htmlspecialchars($e->getMessage()) . " -->";
    $orders = [];
    $ordersByStatus = [];
    $statusCounts = [];
}
?>

<main class="orders-container">
    <div class="orders-header">
        <h1>My Orders</h1>
    </div>

    <?php if (empty($orders)): ?>
        <div class="empty-state">
            <i class="fas fa-shopping-bag"></i>
            <h2>No Orders Yet</h2>
            <p>Your order history is empty. Start shopping to see your orders here!</p>
            <a href="<?php echo getBaseUrl(); ?>" class="btn btn-primary">Browse Books</a>
        </div>
    <?php else: ?>
        <!-- Order Status Tabs -->
        <div class="order-tabs">
            <button class="tab-btn active" data-status="all">
                All Orders <span class="count"><?php echo count($orders); ?></span>
            </button>
            <button class="tab-btn" data-status="pending">
                Pending <span class="count"><?php echo $statusCounts['pending']; ?></span>
            </button>
            <button class="tab-btn" data-status="processing">
                Processing <span class="count"><?php echo $statusCounts['processing']; ?></span>
            </button>
            <button class="tab-btn" data-status="shipped">
                Shipped <span class="count"><?php echo $statusCounts['shipped']; ?></span>
            </button>
            <button class="tab-btn" data-status="delivered">
                Delivered <span class="count"><?php echo $statusCounts['delivered']; ?></span>
            </button>
            <button class="tab-btn" data-status="cancelled">
                Cancelled <span class="count"><?php echo $statusCounts['cancelled']; ?></span>
            </button>
        </div>

        <!-- Orders List -->
        <div class="orders-list">
            <?php foreach ($orders as $order): ?>
                <div class="order-card" data-status="<?php echo $order['status']; ?>">
                    <div class="order-card-header">
                        <div class="order-meta">
                            <div class="order-number">
                                <h3>Order #<?php echo htmlspecialchars($order['order_number']); ?></h3>
                                <time datetime="<?php echo $order['created_at']; ?>">
                                    Placed on <?php echo date('F j, Y', strtotime($order['created_at'])); ?>
                                </time>
                            </div>
                            <span class="order-status <?php echo strtolower($order['status']); ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                    </div>

                    <div class="order-card-body">
                        <!-- Ordered Books Section -->
                        <div class="ordered-books">
                            <h4>Ordered Books</h4>
                            <div class="book-list">
                                <?php if (isset($order['items']) && !empty($order['items'])): ?>
                                    <?php foreach ($order['items'] as $item): ?>
                                        <div class="book-item">
                                            <div class="book-image">
                                                <?php 
                                                $imageData = $item['image_data'];
                                                $imageSrc = $imageData ? "data:image/jpeg;base64," . base64_encode($imageData) : getBaseUrl() . 'assets/images/no-cover.png';
                                                ?>
                                                <img src="<?php echo $imageSrc; ?>" 
                                                     alt="<?php echo htmlspecialchars($item['book_name']); ?>">
                                            </div>
                                            <div class="book-details">
                                                <h5><?php echo htmlspecialchars($item['book_name']); ?></h5>
                                                <p class="author">By <?php echo htmlspecialchars($item['author']); ?></p>
                                                <p class="isbn">ISBN: <?php echo htmlspecialchars($item['isbn']); ?></p>
                                                <div class="item-price-details">
                                                    <span class="quantity">Quantity: <?php echo $item['quantity']; ?></span>
                                                    <span class="price">RM <?php echo number_format($item['price'], 2); ?> each</span>
                                                    <span class="total">Subtotal: RM <?php echo number_format($item['total_price'], 2); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="no-items">No book details available</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="order-details">
                            <div class="shipping-info">
                                <h4>Shipping Details</h4>
                                <address>
                                    <strong><?php echo htmlspecialchars($order['shipping_name']); ?></strong><br>
                                    <?php echo htmlspecialchars($order['shipping_house_number'] . ' ' . $order['shipping_street_name']); ?><br>
                                    <?php echo htmlspecialchars($order['shipping_city'] . ', ' . $order['shipping_district']); ?><br>
                                    <?php echo htmlspecialchars($order['shipping_state'] . ', ' . $order['shipping_country']); ?>
                                </address>
                            </div>

                            <div class="payment-info">
                                <h4>Payment Information</h4>
                                <div class="payment-details">
                                    <p class="payment-method">
                                        <span>Method:</span> <?php echo strtoupper($order['payment_method']); ?>
                                    </p>
                                    <p class="payment-status">
                                        <span>Status:</span> 
                                        <span class="status-badge <?php echo $order['payment_status']; ?>">
                                            <?php echo ucfirst($order['payment_status']); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <div class="order-summary">
                                <h4>Order Summary</h4>
                                <div class="price-breakdown">
                                    <div class="price-row">
                                        <span>Subtotal</span>
                                        <span>RM <?php echo number_format($order['original_subtotal'], 2); ?></span>
                                    </div>
                                    <?php if ($order['discount_amount'] > 0): ?>
                                        <div class="price-row discount">
                                            <span>Discount</span>
                                            <span>-RM <?php echo number_format($order['discount_amount'], 2); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="price-row">
                                        <span>Shipping Fee</span>
                                        <span>RM <?php echo number_format($order['shipping_fee'], 2); ?></span>
                                    </div>
                                    <div class="price-row total">
                                        <span>Total</span>
                                        <span>RM <?php echo number_format($order['total_amount'], 2); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($order['remarks'])): ?>
                            <div class="remarks-section">
                                <h4>Remarks</h4>
                                <p><?php echo htmlspecialchars($order['remarks']); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const orderCards = document.querySelectorAll('.order-card');
    
    function filterOrders(status) {
        orderCards.forEach(card => {
            if (status === 'all' || card.dataset.status === status) {
                card.style.display = 'block';
                card.style.animation = 'fadeIn 0.5s ease forwards';
            } else {
                card.style.display = 'none';
            }
        });
    }
    
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Remove active class from all buttons
            tabButtons.forEach(btn => btn.classList.remove('active'));
            // Add active class to clicked button
            button.classList.add('active');
            // Filter orders
            filterOrders(button.dataset.status);
        });
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>