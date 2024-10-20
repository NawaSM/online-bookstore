<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_admin_logged_in()) {
    redirect('login.php');
}

$error = '';
$success = '';

// Fetch bestseller books
$stmt = $pdo->query("SELECT i.*, c.name AS category_name FROM inventory i 
                     LEFT JOIN categories c ON i.category = c.id 
                     WHERE i.status = 'bestseller' 
                     ORDER BY i.book_name ASC");
$bestsellers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle removing a book from bestsellers
if (isset($_POST['remove_bestseller'])) {
    $book_id = intval($_POST['book_id']);
    try {
        $stmt = $pdo->prepare("UPDATE inventory SET status = 'regular' WHERE id = ?");
        $stmt->execute([$book_id]);
        $success = "Book removed from bestsellers successfully.";
        // Refresh the bestsellers list
        header("Location: manage_bestsellers.php");
        exit();
    } catch (PDOException $e) {
        $error = "Error removing book from bestsellers: " . $e->getMessage();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bestsellers</title>
    <link rel="stylesheet" href="../css/admin-panel.css">
    <script src="../js/admin-notifications.js"></script>
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        <div class="main-content">
            <h1>Manage Bestsellers</h1>
            <?php if ($error): ?>
                <script>showNotification("<?php echo addslashes($error); ?>", "error");</script>
            <?php endif; ?>
            <?php if ($success): ?>
                <script>showNotification("<?php echo addslashes($success); ?>", "success");</script>
            <?php endif; ?>
            <table class="inventory-table">
                <thead>
                    <tr>
                        <th>Book Name</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bestsellers as $book): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($book['book_name']); ?></td>
                        <td><?php echo htmlspecialchars($book['author']); ?></td>
                        <td><?php echo htmlspecialchars($book['category_name']); ?></td>
                        <td>$<?php echo number_format($book['price'], 2); ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                <button type="submit" name="remove_bestseller" class="btn btn-small btn-danger" onclick="return confirm('Are you sure you want to remove this book from bestsellers?');">Remove from Bestsellers</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>