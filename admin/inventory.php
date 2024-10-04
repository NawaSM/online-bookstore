<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_admin_logged_in()) {
    redirect('login.php');
}

// Fetch inventory items
$stmt = $pdo->query("SELECT * FROM inventory ORDER BY book_name ASC");
$inventory_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management</title>
    <link rel="stylesheet" href="../css/admin-panel.css">
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        <div class="main-content">
            <h1>Inventory Management</h1>
            <a href="inventory_add.php" class="btn">Add New Book</a>
            <table class="inventory-table">
                <thead>
                    <tr>
                        <th>Book Name</th>
                        <th>Author</th>
                        <th>ISBN</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inventory_items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['book_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['author']); ?></td>
                        <td><?php echo htmlspecialchars($item['isbn']); ?></td>
                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td>
                            <a href="inventory_edit.php?id=<?php echo $item['id']; ?>" class="btn btn-small">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="../js/scripts.js"></script>
</body>
</html>