<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_admin_logged_in()) {
    redirect('login.php');
}

$error = '';
$success = '';

// Fetch special books
$stmt = $pdo->query("SELECT i.*, c.name AS category_name FROM inventory i 
                     LEFT JOIN categories c ON i.category = c.id 
                     WHERE i.is_special = 1 
                     ORDER BY i.book_name ASC");
$specials = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle removing a book from specials
if (isset($_POST['remove_special'])) {
    $book_id = intval($_POST['book_id']);
    try {
        $stmt = $pdo->prepare("UPDATE inventory SET is_special = 0, special_price = NULL WHERE id = ?");
        $stmt->execute([$book_id]);
        $success = "Book removed from specials successfully.";
        header("Location: manage_specials.php");
        exit();
    } catch (PDOException $e) {
        $error = "Error removing book from specials: " . $e->getMessage();
    }
}

// Handle adding a book to specials
if (isset($_POST['add_special'])) {
    $book_id = intval($_POST['add_special']);
    $special_price = floatval($_POST['special_price']);
    try {
        $stmt = $pdo->prepare("UPDATE inventory SET is_special = 1, special_price = ? WHERE id = ?");
        $stmt->execute([$special_price, $book_id]);
        $success = "Book added to specials successfully.";
        header("Location: manage_specials.php");
        exit();
    } catch (PDOException $e) {
        $error = "Error adding book to specials: " . $e->getMessage();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Specials</title>
    <link rel="stylesheet" href="../css/admin-panel.css">
    <script src="../js/admin-notifications.js"></script>
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        <div class="main-content">
            <h1>Manage Specials</h1>
            <?php if ($error): ?>
                <script>showNotification("<?php echo addslashes($error); ?>", "error");</script>
            <?php endif; ?>
            <?php if ($success): ?>
                <script>showNotification("<?php echo addslashes($success); ?>", "success");</script>
            <?php endif; ?>
            
            <form method="post" class="add-form">
                <select name="add_special" required>
                    <option value="">Select a book to add as special</option>
                    <?php
                    $stmt = $pdo->query("SELECT id, book_name, price FROM inventory WHERE is_special = 0 ORDER BY book_name ASC");
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo "<option value='" . $row['id'] . "' data-price='" . $row['price'] . "'>" . htmlspecialchars($row['book_name']) . "</option>";
                    }
                    ?>
                </select>
                <input type="number" name="special_price" step="0.01" min="0" placeholder="Special Price" required>
                <button type="submit" class="btn">Add to Specials</button>
            </form>

            <table class="inventory-table">
                <thead>
                    <tr>
                        <th>Book Name</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>Regular Price</th>
                        <th>Special Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($specials as $book): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($book['book_name']); ?></td>
                        <td><?php echo htmlspecialchars($book['author']); ?></td>
                        <td><?php echo htmlspecialchars($book['category_name']); ?></td>
                        <td>$<?php echo number_format($book['price'], 2); ?></td>
                        <td>$<?php echo number_format($book['special_price'], 2); ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                <button type="submit" name="remove_special" class="btn btn-small btn-danger" onclick="return confirm('Are you sure you want to remove this book from specials?');">Remove from Specials</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
    document.querySelector('select[name="add_special"]').addEventListener('change', function() {
        var selectedOption = this.options[this.selectedIndex];
        var regularPrice = selectedOption.getAttribute('data-price');
        document.querySelector('input[name="special_price"]').value = regularPrice;
    });
    </script>
</body>
</html>