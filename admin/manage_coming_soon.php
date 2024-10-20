<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_admin_logged_in()) {
    redirect('login.php');
}

$error = '';
$success = '';

// Fetch coming soon books
$stmt = $pdo->query("SELECT i.*, c.name AS category_name FROM inventory i 
                     LEFT JOIN categories c ON i.category = c.id 
                     WHERE i.status = 'coming_soon' 
                     ORDER BY i.book_name ASC");
$coming_soon = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle removing a book from coming soon
if (isset($_POST['remove_coming_soon'])) {
    $book_id = intval($_POST['book_id']);
    try {
        $stmt = $pdo->prepare("UPDATE inventory SET status = 'regular' WHERE id = ?");
        $stmt->execute([$book_id]);
        $success = "Book removed from coming soon successfully.";
        header("Location: manage_coming_soon.php");
        exit();
    } catch (PDOException $e) {
        $error = "Error removing book from coming soon: " . $e->getMessage();
    }
}

// Handle adding a book to coming soon
if (isset($_POST['add_coming_soon'])) {
    $book_id = intval($_POST['add_coming_soon']);
    try {
        $stmt = $pdo->prepare("UPDATE inventory SET status = 'coming_soon' WHERE id = ?");
        $stmt->execute([$book_id]);
        $success = "Book added to coming soon successfully.";
        header("Location: manage_coming_soon.php");
        exit();
    } catch (PDOException $e) {
        $error = "Error adding book to coming soon: " . $e->getMessage();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Coming Soon</title>
    <link rel="stylesheet" href="../css/admin-panel.css">
    <script src="../js/admin-notifications.js"></script>
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        <div class="main-content">
            <h1>Manage Coming Soon</h1>
            <?php if ($error): ?>
                <script>showNotification("<?php echo addslashes($error); ?>", "error");</script>
            <?php endif; ?>
            <?php if ($success): ?>
                <script>showNotification("<?php echo addslashes($success); ?>", "success");</script>
            <?php endif; ?>
            
            <form method="post" class="add-form">
                <select name="add_coming_soon" required>
                    <option value="">Select a book to add as coming soon</option>
                    <?php
                    $stmt = $pdo->query("SELECT id, book_name FROM inventory WHERE status != 'coming_soon' ORDER BY book_name ASC");
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['book_name']) . "</option>";
                    }
                    ?>
                </select>
                <button type="submit" class="btn">Add to Coming Soon</button>
            </form>

            <table class="inventory-table">
                <thead>
                    <tr>
                        <th>Book Name</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>Release Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($coming_soon as $book): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($book['book_name']); ?></td>
                        <td><?php echo htmlspecialchars($book['author']); ?></td>
                        <td><?php echo htmlspecialchars($book['category_name']); ?></td>
                        <td><?php echo htmlspecialchars($book['release_date']); ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                <button type="submit" name="remove_coming_soon" class="btn btn-small btn-danger" onclick="return confirm('Are you sure you want to remove this book from coming soon?');">Remove from Coming Soon</button>
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