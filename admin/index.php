<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

error_log("Session data in index.php: " . print_r($_SESSION, true));

if (!is_admin_logged_in() || !isset($_SESSION['admin_role'])) {
    redirect('login.php');
}

// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug: Print session data
//echo "<pre>";
//print_r($_SESSION);
//echo "</pre>";

// Fetch some basic statistics for the dashboard
$stmt = $pdo->query("SELECT COUNT(*) FROM inventory");
$total_books = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
$pending_orders = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$total_users = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/admin-panel.css">
    <script src="../js/admin-notifications.js"></script>
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        <div class="main-content">
            <h1>Welcome, <?php echo $_SESSION['admin_username']; ?>!</h1>
            <div class="dashboard-stats">
                <div class="stat-box">
                    <h3>Total Books</h3>
                    <p><?php echo $total_books; ?></p>
                </div>
                <div class="stat-box">
                    <h3>Pending Orders</h3>
                    <p><?php echo $pending_orders; ?></p>
                </div>
                <div class="stat-box">
                    <h3>Total Users</h3>
                    <p><?php echo $total_users; ?></p>
                </div>
            </div>
            <!-- Add more dashboard content here -->
        </div>
    </div>
    <script src="../js/scripts.js"></script>
</body>
</html>