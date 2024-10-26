<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_admin_logged_in()) {
    redirect('../admin/login.php');
}

// Fetch inventory items
$stmt = $pdo->query("SELECT i.*, c.name AS category_name FROM inventory i LEFT JOIN categories c ON i.category = c.id ORDER BY i.book_name ASC");
$inventory_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all genres
$stmt = $pdo->query("SELECT * FROM genres");
$all_genres = $stmt->fetchAll(PDO::FETCH_ASSOC);
$genres_map = array_column($all_genres, 'name', 'id');

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management</title>
    <script src="admin-notifications.js"></script>
    <link rel="stylesheet" href="../css/admin-panel.css">
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        <div class="main-content">
            <h1>Inventory Management</h1>
            <a href="../admin/inventory_add.php" class="btn">Add New Book</a>
            <table class="inventory-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Book Name</th>
                        <th>Author</th>
                        <th>ISBN</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Category</th>
                        <th>Release Year</th>
                        <th>Genres</th>
                        <th>Status</th>
                        <th>Special</th>
                        <th>Special Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inventory_items as $item): ?>
                    <tr>
                        <td>
                            <?php if ($item['image_data']): ?>
                                <img src="serve_image.php?type=book&id=<?php echo $item['id']; ?>" 
                                    alt="<?php echo htmlspecialchars($item['book_name']); ?>"
                                    class="inventory-table-image">
                            <?php else: ?>
                                No Image
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($item['book_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['author']); ?></td>
                        <td><?php echo htmlspecialchars($item['isbn']); ?></td>
                        <td>RM<?php echo number_format($item['price'], 2); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                        <td><?php echo $item['release_year']; ?></td>
                        <td>
                            <?php
                            $book_genres = explode(',', $item['genres']);
                            $genre_names = array_map(function($genre_id) use ($genres_map) {
                                return $genres_map[$genre_id] ?? '';
                            }, $book_genres);
                            echo htmlspecialchars(implode(', ', array_filter($genre_names)));
                            ?>
                        </td>
                        <td class="status <?php echo $item['status']; ?>"><?php echo ucfirst($item['status']); ?></td>
                        <td><?php echo $item['is_special'] ? 'Yes' : 'No'; ?></td>
                        <td>
                            <?php
                            if ($item['is_special'] && $item['special_price']) {
                                echo '<span class="special-price">RM' . number_format($item['special_price'], 2) . '</span>';
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </td>
                        <td>
                            <a href="../admin/inventory_edit.php?id=<?php echo $item['id']; ?>" class="btn btn-small">Edit</a>
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