<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_admin_logged_in()) {
    redirect('login.php');
}

// Initialize filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Pagination settings
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Build base query
$query = "SELECT o.*, 
          u.first_name, u.last_name,
          u.house_number, u.street_name, u.city, u.district, u.state, u.country
          FROM orders o
          JOIN users u ON o.user_id = u.id
          WHERE 1=1";

$count_query = "SELECT COUNT(*) FROM orders o WHERE 1=1";
$params = [];

// Apply filters
if ($status_filter) {
    $query .= " AND o.status = ?";
    $count_query .= " AND o.status = ?";
    $params[] = $status_filter;
}

if ($date_from) {
    $query .= " AND DATE(o.created_at) >= ?";
    $count_query .= " AND DATE(o.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $query .= " AND DATE(o.created_at) <= ?";
    $count_query .= " AND DATE(o.created_at) <= ?";
    $params[] = $date_to;
}

if ($search) {
    $query .= " AND (o.order_number LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?)";
    $count_query .= " AND (o.order_number LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

// Get total records for pagination
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);

// Add sorting and pagination to main query
$query .= " ORDER BY o.created_at DESC LIMIT $records_per_page OFFSET $offset";

// Initialize orders array
$orders = [];

// Execute the main query
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);  // Use original params without pagination values
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching orders: " . $e->getMessage();
    error_log($error);
}

// Add error display
if (isset($error)) {
    echo '<div class="error">' . htmlspecialchars($error) . '</div>';
}

// Handle status update via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['new_status'];
    
    try {
        $pdo->beginTransaction();
        
        // Update order status
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $order_id]);
        
        // Add to status history
        $stmt = $pdo->prepare("INSERT INTO order_status_history (order_id, status, changed_by) VALUES (?, ?, ?)");
        $stmt->execute([$order_id, $new_status, $_SESSION['admin_id']]);
        
        $pdo->commit();
        echo json_encode(['success' => true]);
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management</title>
    <link rel="stylesheet" href="../css/admin-panel.css">
    <script src="../js/admin-notifications.js"></script>
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        <div class="main-content">
            <h1>Order Management</h1>

            <!-- Filters -->
            <div class="filters-section">
                <form method="GET" class="filters-form">
                    <div class="form-group">
                        <label for="status">Status:</label>
                        <select name="status" id="status">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="date_from">Date From:</label>
                        <input type="date" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                    </div>

                    <div class="form-group">
                        <label for="date_to">Date To:</label>
                        <input type="date" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                    </div>

                    <div class="form-group">
                        <label for="search">Search:</label>
                        <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Order number or customer name">
                    </div>

                    <button type="submit" class="btn">Apply Filters</button>
                    <a href="orders.php" class="btn">Clear Filters</a>
                </form>
            </div>

            <!-- Orders Table -->
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Order Number</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Payment Status</th>
                            <th>Shipping Address</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                                <td>RM<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <select class="status-select" data-order-id="<?php echo $order['id']; ?>">
                                        <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                        <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                        <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </td>
                                <td><?php echo ucfirst($order['payment_status']); ?></td>
                                <td>
                                    <?php
                                    echo htmlspecialchars($order['house_number'] . ', ' . 
                                                        $order['street_name'] . ', ' . 
                                                        $order['city'] . ', ' . 
                                                        $order['district'] . ', ' . 
                                                        $order['state'] . ', ' . 
                                                        $order['country']);
                                    ?>
                                </td>
                                <td>
                                    <a href="view_order.php?id=<?php echo $order['id']; ?>" class="btn btn-small">View Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="pagination">
                <?php if ($total_pages > 1): ?>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&search=<?php echo urlencode($search); ?>" 
                           class="btn <?php echo $page === $i ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle status changes
        document.querySelectorAll('.status-select').forEach(select => {
            select.addEventListener('change', function() {
                const orderId = this.dataset.orderId;
                const newStatus = this.value;
                
                if (confirm('Are you sure you want to update this order\'s status?')) {
                    fetch('orders.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=update_status&order_id=${orderId}&new_status=${newStatus}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('Order status updated successfully', 'success');
                        } else {
                            showNotification('Error updating order status: ' + data.error, 'error');
                            // Reset select to previous value
                            this.value = this.dataset.originalValue;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Error updating order status', 'error');
                        this.value = this.dataset.originalValue;
                    });
                } else {
                    // Reset select to previous value if user cancels
                    this.value = this.dataset.originalValue;
                }
            });
            
            // Store original value for reverting if needed
            select.dataset.originalValue = select.value;
        });
    });
    </script>
</body>
</html>