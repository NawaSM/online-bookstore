<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_admin_logged_in()) {
    redirect('login.php');
}

$error = '';
$success = '';
$item = null;

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        redirect('inventory.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = intval($_POST['id']);
    $book_name = sanitize_input($_POST['book_name']);
    $author = sanitize_input($_POST['author']);
    $isbn = sanitize_input($_POST['isbn']);
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);
    $release_date = sanitize_input($_POST['release_date']);

    if (empty($book_name) || empty($author) || empty($isbn) || $price <= 0 || $quantity < 0) {
        $error = "Please fill all required fields with valid data.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE inventory SET book_name = ?, author = ?, isbn = ?, price = ?, quantity = ?, release_date = ? WHERE id = ?");
            $stmt->execute([$book_name, $author, $isbn, $price, $quantity, $release_date, $id]);
            $success = "Book updated successfully!";
            
            // Refresh item data
            $stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = ?");
            $stmt->execute([$id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error = "Error updating book: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Book</title>
    <link rel="stylesheet" href="../css/admin-panel.css">
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        <div class="main-content">
            <h1>Edit Book</h1>
            <?php if ($error): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php endif; ?>
            <?php if ($success): ?>
                <p class="success"><?php echo $success; ?></p>
            <?php endif; ?>
            <?php if ($item): ?>
                <form action="inventory_edit.php" method="post">
                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                    <div class="form-group">
                        <label for="book_name">Book Name:</label>
                        <input type="text" id="book_name" name="book_name" value="<?php echo htmlspecialchars($item['book_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="author">Author:</label>
                        <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($item['author']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="isbn">ISBN:</label>
                        <input type="text" id="isbn" name="isbn" value="<?php echo htmlspecialchars($item['isbn']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="price">Price:</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo $item['price']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="quantity">Quantity:</label>
                        <input type="number" id="quantity" name="quantity" min="0" value="<?php echo $item['quantity']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="release_date">Release Date:</label>
                        <input type="date" id="release_date" name="release_date" value="<?php echo $item['release_date']; ?>">
                    </div>
                    <button type="submit" class="btn">Update Book</button>
                </form>
            <?php else: ?>
                <p>Book not found.</p>
            <?php endif; ?>
        </div>
    </div>
    <script src="../js/scripts.js"></script>
</body>
</html>