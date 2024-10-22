<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_admin_logged_in()) {
    redirect('login.php');
}

$error = '';
$success = '';

// Fetch genres and categories
try {
    $stmt = $pdo->query("SELECT * FROM genres ORDER BY name");
    $genres = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching genres and categories: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $book_name = sanitize_input($_POST['book_name']);
    $author = sanitize_input($_POST['author']);
    $isbn = sanitize_input($_POST['isbn']);
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);
    $release_year = intval($_POST['release_year']);
    $img = sanitize_input($_POST['img']);
    $genres = isset($_POST['genres']) ? implode(',', $_POST['genres']) : '';
    $category = sanitize_input($_POST['category']);
    $status = sanitize_input($_POST['status']);
    $is_special = isset($_POST['is_special']) ? 1 : 0;
    $special_price = $is_special ? floatval($_POST['special_price']) : null;

    if (empty($book_name) || empty($author) || empty($isbn) || $price <= 0 || $quantity < 0) {
        $error = "Please fill all required fields with valid data.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO inventory (book_name, author, isbn, price, quantity, release_year, img, genres, category, status, is_special, special_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$book_name, $author, $isbn, $price, $quantity, $release_year, $img, $genres, $category, $status, $is_special, $special_price]);
            $_SESSION['success_message'] = "Book added successfully!";
            header("Location: inventory_add.php");
            exit();
        } catch (PDOException $e) {
            $error = "Error adding book: " . $e->getMessage();
        }
    }
}

// Check for success message in session
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Book</title>
    <link rel="stylesheet" href="../css/admin-panel.css">
    <script src="../js/admin-notifications.js"></script>
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        <div class="main-content">
            <h1>Add New Book</h1>
            <?php if ($error): ?>
                <script>showNotification("<?php echo addslashes($error); ?>", "error");</script>
            <?php endif; ?>
            <?php if ($success): ?>
                <script>showNotification("<?php echo addslashes($success); ?>", "success");</script>
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
                    <label for="release_year">Release Year:</label>
                    <input type="number" id="release_year" name="release_year" min="1800" max="<?php echo date('Y') + 10; ?>" value="<?php echo date('Y'); ?>" required>
                </div>

                <div class="form-group">
                    <label for="img">Image URL:</label>
                    <input type="text" id="img" name="img">
                </div>

                <div class="form-group">
                    <label>Genres:</label>
                    <?php if (is_array($genres)): ?>
                        <?php foreach ($genres as $genre): ?>
                            <label>
                                <input type="checkbox" name="genres[]" value="<?php echo $genre['id']; ?>">
                                <?php echo htmlspecialchars($genre['name']); ?>
                            </label>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Error: Unable to load genres.</p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="category">Category:</label>
                    <select id="category" name="category" required>
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="status">Book Status:</label>
                    <select id="status" name="status" required>
                        <option value="regular">Regular</option>
                        <option value="bestseller">Best Seller</option>
                        <option value="coming_soon">Coming Soon</option>
                        <option value="new">New</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_special" value="1"> Mark as Special (On Sale)
                    </label>
                </div>

                <div class="form-group" id="special-price-group" style="display: none;">
                    <label for="special_price">Special Price:</label>
                    <input type="number" id="special_price" name="special_price" step="0.01" min="0">
                </div>

                <script>
                document.querySelector('input[name="is_special"]').addEventListener('change', function() {
                    document.getElementById('special-price-group').style.display = this.checked ? 'block' : 'none';
                });
                </script>
                <button type="submit" class="btn">Add Book</button>
            </form>
        </div>
    </div>
    <?php if ($error): ?>
        <script>showNotification("<?php echo addslashes($error); ?>", "error");</script>
    <?php endif; ?>
    <?php if ($success): ?>
        <script>showNotification("<?php echo addslashes($success); ?>", "success");</script>
    <?php endif; ?>
</body>
</html>