<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_admin_logged_in()) {
    redirect('login.php');
}

// Get order ID from URL
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$order_id) {
    redirect('orders.php');
}

// Fetch order details with customer information
$stmt = $pdo->prepare("
    SELECT o.*, 
           u.first_name, u.last_name, u.email,
           u.house_number, u.street_name, u.city, 
           u.district, u.state, u.country
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    redirect('orders.php');
}

// Fetch order items
$stmt = $pdo->prepare("
    SELECT oi.*, i.book_name, i.isbn
    FROM order_items oi
    JOIN inventory i ON oi.inventory_id = i.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch status history
$stmt = $pdo->prepare("
    SELECT h.*, a.username as admin_name
    FROM order_status_history h
    LEFT JOIN admins a ON h.changed_by = a.id
    WHERE h.order_id = ?
    ORDER BY h.created_at DESC
");
$stmt->execute([$order_id]);
$status_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = sanitize_input($_POST['new_status']);
    
    try {
        $pdo->beginTransaction();
        
        // Update order status
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $order_id]);
        
        // Add to status history
        $stmt = $pdo->prepare("
            INSERT INTO order_status_history (order_id, status, changed_by) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$order_id, $new_status, $_SESSION['admin_id']]);
        
        $pdo->commit();
        $_SESSION['success_message'] = "Order status updated successfully";
        redirect("view_order.php?id=$order_id");
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error updating status: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Order #<?php echo htmlspecialchars($order['order_number']); ?></title>
    <link rel="stylesheet" href="../css/admin-panel.css">
    <script src="../js/admin-notifications.js"></script>
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        <div class="main-content">
            <div class="order-header">
                <h1>Order #<?php echo htmlspecialchars($order['order_number']); ?></h1>
                <a href="orders.php" class="btn">Back to Orders</a>
            </div>

            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Order Status Update -->
            <div class="status-update-section">
                <h2>Order Status</h2>
                <form method="post" class="status-form">
                    <select name="new_status" class="form-select">
                        <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                        <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                    <button type="submit" name="update_status" class="btn">Update Status</button>
                </form>
            </div>

            <div class="order-grid">
                <!-- Order Information -->
                <div class="order-section">
                    <h2>Order Information</h2>
                    <div class="info-group">
                        <p><strong>Order Date:</strong> <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></p>
                        <p><strong>Status:</strong> <?php echo ucfirst($order['status']); ?></p>
                        <p><strong>Payment Method:</strong> <?php echo ucfirst($order['payment_method']); ?></p>
                        <p><strong>Payment Status:</strong> <?php echo ucfirst($order['payment_status']); ?></p>
                    </div>
                </div>

                <!-- Customer Information -->
                <div class="order-section">
                    <h2>Customer Information</h2>
                    <div class="info-group">
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                        <p><strong>Shipping Address:</strong><br>
                            <?php echo htmlspecialchars($order['house_number'] . ', ' . 
                                                      $order['street_name'] . ', ' . 
                                                      $order['city'] . ', ' . 
                                                      $order['district'] . ', ' . 
                                                      $order['state'] . ', ' . 
                                                      $order['country']); ?>
                        </p>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="order-section full-width">
                    <h2>Order Items</h2>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Book</th>
                                <th>ISBN</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $subtotal = 0;
                            foreach ($order_items as $item): 
                                $item_subtotal = $item['price'] * $item['quantity'];
                                $subtotal += $item_subtotal;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['book_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['isbn']); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>RM<?php echo number_format($item['price'], 2); ?></td>
                                <td>RM<?php echo number_format($item_subtotal, 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-right"><strong>Subtotal:</strong></td>
                                <td>RM<?php echo number_format($subtotal, 2); ?></td>
                            </tr>
                            <?php if ($order['discount_amount'] > 0): ?>
                            <tr>
                                <td colspan="4" class="text-right"><strong>Discount:</strong></td>
                                <td>-RM<?php echo number_format($order['discount_amount'], 2); ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td colspan="4" class="text-right"><strong>Total:</strong></td>
                                <td>RM<?php echo number_format($order['total_amount'], 2); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Customer Remarks -->
                <?php if (!empty($order['remarks'])): ?>
                <div class="order-section">
                    <h2>Order Remarks</h2>
                    <div class="remark">
                        <p><?php echo htmlspecialchars($order['remarks']); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Status History -->
                <div class="order-section">
                    <h2>Status History</h2>
                    <div class="status-timeline">
                        <?php foreach ($status_history as $history): ?>
                            <div class="timeline-item">
                                <div class="timeline-status">
                                    <span class="status-badge <?php echo $history['status']; ?>">
                                        <?php echo ucfirst($history['status']); ?>
                                    </span>
                                </div>
                                <div class="timeline-details">
                                    <p>Changed by: <?php echo htmlspecialchars($history['admin_name']); ?></p>
                                    <p>Date: <?php echo date('F j, Y g:i A', strtotime($history['created_at'])); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (isset($_SESSION['success_message'])): ?>
            showNotification('<?php echo $_SESSION['success_message']; ?>', 'success');
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
    });
    </script>
</body>
</html>