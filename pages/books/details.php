<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/currency.php'; 

// Use absolute paths
require_once $_SERVER['DOCUMENT_ROOT'] . '/onlinebookstore/config/database.php';
include $_SERVER['DOCUMENT_ROOT'] . '/onlinebookstore/includes/header.php';

$bookId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$bookId) {
    header('Location: /onlinebookstore/index.php');
    exit;
}

try {
    $stmt = $conn->prepare("SELECT * FROM inventory WHERE id = ?");
    $stmt->execute([$bookId]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$book) {
        header('Location: /onlinebookstore/index.php');
        exit;
    }
    
    // Convert genres string to array
    $genres = $book['genres'] ? explode(',', trim($book['genres'])) : [];
    // Create categories array from single category
    $categories = $book['category'] ? [$book['category']] : [];
    
    // Check wishlist status
    $isInWishlist = false;
    if (isset($_SESSION['user_id'])) {
        $stmt = $conn->prepare("
            SELECT 1 FROM wishlists 
            WHERE user_id = ? AND book_id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $bookId]);
        $isInWishlist = (bool)$stmt->fetch();
    }

} catch (PDOException $e) {
    error_log($e->getMessage());
    header('Location: /onlinebookstore/index.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($book['book_name']); ?> - NawLexKen Books</title>
    <link rel="stylesheet" href="/onlinebookstore/assets/css/books/details.css">
</head>
<body data-authenticated="<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>">
    <div class="book-details-container">
        <div class="book-details-wrapper">
            <div class="book-image-container">
                <img src="data:<?php echo $book['image_type']; ?>;base64,<?php echo base64_encode($book['image_data']); ?>" 
                     alt="<?php echo htmlspecialchars($book['book_name']); ?>"
                     onerror="this.src='/onlinebookstore/assets/images/book-placeholder.jpg'">
                <?php if ($book['status'] !== 'regular'): ?>
                    <div class="book-status status-<?php echo $book['status']; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $book['status'])); ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="book-info-container">
                <div class="book-header">
                    <h1 class="book-title"><?php echo htmlspecialchars($book['book_name']); ?></h1>
                    <p class="book-author">by <?php echo htmlspecialchars($book['author']); ?></p>
                </div>

                <div class="book-price-section">
                    <?php if ($book['is_special'] && $book['special_price']): ?>
                        <?php $prices = getDisplayPrice($book['price'], $book['special_price']); ?>
                        <span class="original-price"><?php echo $prices['original']; ?></span>
                        <span class="book-price"><?php echo $prices['special']; ?></span>
                    <?php else: ?>
                        <span class="book-price"><?php echo getDisplayPrice($book['price']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="book-meta">
                    <div class="meta-item">
                        <span class="meta-label">Category</span>
                        <div class="meta-value">
                            <?php if (!empty($book['category'])): ?>
                                <span class="category-tag"><?php echo htmlspecialchars($book['category']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="meta-item">
                        <span class="meta-label">Genres</span>
                        <div class="meta-value">
                            <?php foreach ($genres as $genre): ?>
                                <span class="genre-tag"><?php echo htmlspecialchars($genre); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="meta-item">
                        <span class="meta-label">Stock Status</span>
                        <span class="meta-value <?php echo $book['quantity'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                            <?php echo $book['quantity'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                            <?php if ($book['quantity'] > 0): ?>
                                (<?php echo $book['quantity']; ?> available)
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

                <div class="book-actions">
                    <button class="add-to-cart" 
                                data-book-id="<?php echo $book['id']; ?>"
                                <?php echo !isset($_SESSION['user_id']) ? 'data-redirect="login"' : ''; ?>>
                            Add to Cart
                    </button>
                    <button class="wishlist-btn <?php echo $isInWishlist ? 'active' : ''; ?>" 
                            onclick="<?php echo isset($_SESSION['user_id']) ? 
                                    'toggleWishlist('.$book['id'].')' : 
                                    'window.location.href=\'/onlinebookstore/pages/login.php\'' ?>">
                        <i class="fa<?php echo $isInWishlist ? 's' : 'r'; ?> fa-heart"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
   

    <?php include '../../includes/footer.php'; ?>

    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
</body>
</html>