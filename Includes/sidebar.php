<?php
$stmt = $pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0");
$unread_count = $stmt->fetchColumn();
?>

<div class="sidebar">
    <div class="logo">
        <h2>Bookstore Admin</h2>
    </div>
    <nav>
        <ul>
            <li><a href="index.php">Dashboard</a></li>
            <li><a href="inventory.php">Inventory Management</a></li>
            <li><a href="orders.php">Order Management</a></li>
            <li><a href="manage_bestsellers.php">Manage Best Sellers</a></li>
            <li><a href="manage_coming_soon.php">Manage Coming Soon</a></li>
            <li><a href="manage_new.php">Manage New Books</a></li>
            <li><a href="manage_specials.php">Manage Specials</a></li>
            <li><a href="manage_banner_ads.php">Manage Banner Ads</a></li>
            <li><a href="manage_promo_codes.php">Manage Promo Codes</a></li>
            <?php if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'senior'): ?>
                <li><a href="manage_admins.php">Manage Admins</a></li>
            <?php endif; ?>
            <li>
                <a href="notifications.php">
                    Notifications
                    <?php if ($unread_count > 0): ?>
                        <span class="notification-badge"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
</div> 