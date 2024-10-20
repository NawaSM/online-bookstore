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

// Check if book ID is provided
if (!isset($_GET['id'])) {
    redirect('inventory.php');
}

$book_id = intval($_GET['id']);

// Fetch book details
try {
    $stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = ?");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$book) {
        redirect('inventory.php');
    }
} catch (PDOException $e) {
    $error = "Error fetching book details: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $book_name = sanitize_input($_POST['book_name']);
    $author = sanitize_input($_POST['author']);
    $isbn = sanitize_input($_POST['isbn']);
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);
    $release_date = sanitize_input($_POST['release_date']);
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
            $stmt = $pdo->prepare("UPDATE inventory SET book_name = ?, author = ?, isbn = ?, price = ?, quantity = ?, release_date = ?, img = ?, genres = ?, category = ?, status = ?, is_special = ?, special_price = ? WHERE id = ?");
            $stmt->execute([$book_name, $author, $isbn, $price, $quantity, $release_date, $img, $genres, $category, $status, $is_special, $special_price, $book_id]);
            $_SESSION['success_message'] = "Book updated successfully!";
            header("Location: inventory_edit.php?id=" . $book_id);
            exit();
        } catch (PDOException $e) {
            $error = "Error updating book: " . $e->getMessage();
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
    <title>Edit Book</title>
    <link rel="stylesheet" href="../css/admin-panel.css">
    <script src="../js/admin-notifications.js"></script>
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        <div class="main-content">
            <h1>Edit Book</h1>
            <?php if ($error): ?>
                <script>showNotification("<?php echo addslashes($error); ?>", "error");</script>
            <?php endif; ?>
            <?php if ($success): ?>
                <script>showNotification("<?php echo addslashes($success); ?>", "success");</script>
            <?php endif; ?>
            <form action="inventory_edit.php?id=<?php echo $book_id; ?>" method="post">
                <div class="form-group">
                    <label for="book_name">Book Name:</label>
                    <input type="text" id="book_name" name="book_name" value="<?php echo htmlspecialchars($book['book_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="author">Author:</label>
                    <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($book['author']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="isbn">ISBN:</label>
                    <input type="text" id="isbn" name="isbn" value="<?php echo htmlspecialchars($book['isbn']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="price">Price:</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo $book['price']; ?>" required>
                </div>
                <div class="form-group">
                    <label for="quantity">Quantity:</label>
                    <input type="number" id="quantity" name="quantity" min="0" value="<?php echo $book['quantity']; ?>" required>
                </div>
                <div class="form-group">
                    <label for="release_date">Release Date:</label>
                    <input type="date" id="release_date" name="release_date" value="<?php echo $book['release_date']; ?>">
                </div>
                <div class="form-group">
                    <label for="img">Image URL:</label>
                    <input type="text" id="img" name="img" value="<?php echo htmlspecialchars($book['img']); ?>">
                </div>
                <div class="form-group">
                    <label>Genres:</label>
                    <?php 
                    $book_genres = explode(',', $book['genres']);
                    foreach ($genres as $genre): 
                    ?>
                        <label>
                            <input type="checkbox" name="genres[]" value="<?php echo $genre['id']; ?>" 
                                <?php echo in_array($genre['id'], $book_genres) ? 'checked' : ''; ?>>
                            <?php echo htmlspecialchars($genre['name']); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="form-group">
                    <label for="category">Category:</label>
                    <select id="category" name="category" required>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $book['category'] == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status">Book Status:</label>
                    <select id="status" name="status" required>
                        <option value="regular" <?php echo $book['status'] == 'regular' ? 'selected' : ''; ?>>Regular</option>
                        <option value="bestseller" <?php echo $book['status'] == 'bestseller' ? 'selected' : ''; ?>>Best Seller</option>
                        <option value="coming_soon" <?php echo $book['status'] == 'coming_soon' ? 'selected' : ''; ?>>Coming Soon</option>
                        <option value="new" <?php echo $book['status'] == 'new' ? 'selected' : ''; ?>>New</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_special" value="1" <?php echo $book['is_special'] ? 'checked' : ''; ?>> Mark as Special (On Sale)
                    </label>
                </div>
                <div class="form-group" id="special-price-group" style="display: <?php echo $book['is_special'] ? 'block' : 'none'; ?>;">
                    <label for="special_price">Special Price:</label>
                    <input type="number" id="special_price" name="special_price" step="0.01" min="0" value="<?php echo $book['special_price']; ?>">
                </div>
                <button type="submit" class="btn">Update Book</button>
            </form>
        </div>
    </div>
    <script>
    document.querySelector('input[name="is_special"]').addEventListener('change', function() {
        document.getElementById('special-price-group').style.display = this.checked ? 'block' : 'none';
    });
    </script>
</body>
</html>