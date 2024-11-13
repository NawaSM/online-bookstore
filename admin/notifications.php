<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_admin_logged_in()) {
    redirect('login.php');
}

// Mark notifications as read if requested
if (isset($_POST['mark_read'])) {
    $notification_id = intval($_POST['notification_id']);
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    $stmt->execute([$notification_id]);
}

// Delete notification if requested
if (isset($_POST['delete'])) {
    $notification_id = intval($_POST['notification_id']);
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
    $stmt->execute([$notification_id]);
}

// Fetch notifications with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get total count
$stmt = $pdo->query("SELECT COUNT(*) FROM notifications");
$total_notifications = $stmt->fetchColumn();
$total_pages = ceil($total_notifications / $per_page);

// Fetch notifications
$stmt = $pdo->prepare("
    SELECT * FROM notifications 
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $per_page, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Admin Panel</title>
    <link rel="stylesheet" href="../css/admin-panel.css">
    <style>
        .notification-item {
            padding: 15px;
            margin-bottom: 10px;
            background: white;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notification-item.unread {
            border-left: 4px solid #007bff;
            background: #f8f9fa;
        }

        .notification-content {
            flex-grow: 1;
        }

        .notification-type {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            margin-right: 10px;
        }

        .type-low_stock { background: #dc3545; color: white; }
        .type-new_order { background: #28a745; color: white; }
        .type-system { background: #6c757d; color: white; }

        .notification-actions {
            display: flex;
            gap: 10px;
        }

        .notification-time {
            font-size: 0.8em;
            color: #6c757d;
            margin-top: 5px;
        }

        .pagination {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        <div class="main-content">
            <h1>Notifications</h1>

            <div class="notifications-container">
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?>">
                        <div class="notification-content">
                            <span class="notification-type type-<?php echo $notification['type']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $notification['type'])); ?>
                            </span>
                            <?php echo htmlspecialchars($notification['message']); ?>
                            <div class="notification-time">
                                <?php echo date('F j, Y g:i A', strtotime($notification['created_at'])); ?>
                            </div>
                        </div>
                        <div class="notification-actions">
                            <?php if (!$notification['is_read']): ?>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                    <button type="submit" name="mark_read" class="btn btn-small">Mark as Read</button>
                                </form>
                            <?php endif; ?>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                <button type="submit" name="delete" class="btn btn-small btn-danger" 
                                        onclick="return confirm('Are you sure you want to delete this notification?')">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($notifications)): ?>
                    <p class="text-center">No notifications found.</p>
                <?php endif; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" 
                           class="btn <?php echo $page === $i ? 'btn-primary' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="../js/admin-notifications.js"></script>
</body>
</html>