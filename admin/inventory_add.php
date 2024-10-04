<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_admin_logged_in()) {
    redirect('login.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
            $stmt = $pdo->prepare("INSERT INTO inventory (book_name, author, isbn, price, quantity, release_date) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$book_name, $author, $isbn, $price, $quantity, $release_date]);
            $success = "Book added successfully!";
        } catch (PDOException $e) {
            $error = "Error adding book: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Book</title>
    <link rel="stylesheet" href="../css/admin-panel.css">
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        <div class="main-content">
            <h1>Add New Book</h1>
            <?php if ($error): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php endif; ?>
            <?php if ($success): ?>
                <p class="success"><?php echo $success; ?></p>
            <?php endif; ?>
            <form action="inventory_add.php" method="post">
                <div class="form-group">
                    <label for="book_name">Book Name:</label>
                    <input type="text" id="book_name" name="book_name" required>
                </div>
                <div class="form-group">
                    <label for="author">Author:</label>
                    <input type="text" id="author" name="author" required>
                </div>
                <div class="form-group">
                    <label for="isbn">ISBN:</label>
                    <input type="text" id="isbn" name="isbn" required>
                </div>
                <div class="form-group">
                    <label for="price">Price:</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label for="quantity">Quantity:</label>
                    <input type="number" id="quantity" name="quantity" min="0" required>
                </div>
                <div class="form-group">
                    <label for="release_date">Release Date:</label>
                    <input type="date" id="release_date" name="release_date">
                </div>
                <button type="submit" class="btn">Add Book</button>
            </form>
        </div>
    </div>
    <script src="../js/scripts.js"></script>
</body>
</html>