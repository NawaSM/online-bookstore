<?php
session_start();
require_once('../../config/database.php');
require_once('../../config/currency.php');


// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . getBaseUrl() . 'pages/login.php');
    exit();
}

include('../../includes/header.php');
?>

<div class="orders-container">
    <div class="orders-header">
        <h1>My Orders</h1>
        <div class="order-filters">
            <select id="statusFilter">
                <option value="">All Orders</option>
                <option value="pending">Pending</option>
                <option value="processing">Processing</option>
                <option value="shipped">Shipped</option>
                <option value="delivered">Delivered</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>
    </div>

    <div class="orders-grid" id="ordersGrid">
        <!-- Orders will be loaded here via JavaScript -->
    </div>
</div>

<?php include('../../includes/footer.php'); ?>