<?php
session_start();
require_once '../includes/functions.php';

requireLogin();
requireAdmin();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
</head>
<body>
    <h2>Welcome, Admin <?php echo $_SESSION['username']; ?>!</h2>
    <p>This is the admin dashboard. You can add admin-specific content and controls here.</p>
    <a href="logout.php">Logout</a>
</body>
</html>