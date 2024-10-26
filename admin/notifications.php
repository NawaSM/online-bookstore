<?php
session_start();
require_once '../includes/functions.php';

requireLogin();

if (isAdmin()) {
    redirectTo('../admina/dmin_dashboard.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <script src="admin-notifications.js"></script>
</head>
<body>
    <h2>Welcome, <?php echo $_SESSION['username']; ?>!</h2>
    <p>This is the user dashboard.</p>
    <a href="../admin/logout.php">Logout</a>
</body>
</html>