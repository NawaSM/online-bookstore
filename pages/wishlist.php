<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../config/currency.php';
include '../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

try {
    // Fetch wishlist items with book details
    $stmt = $conn->prepare("
        SELECT 
            w.id as wishlist_id,
            i.id as book_id,
            i.book_name,
            i.author,
            i.price,
            i.is_special,
            i.special_price,
            i.quantity,
            i.image_data,
            i.image_type,
            i.status,
            CASE 
                WHEN i.quantity > 0 THEN true 
                ELSE false 
            END as is_in_stock
        FROM wishlists w
        JOIN inventory i ON w.book_id = i.id
        WHERE w.user_id = ?
        ORDER BY 
            w.created_at DESC,
            is_in_stock DESC,
            i.book_name ASC
    ");
    
    $stmt->execute([$_SESSION['user_id']]);
    $wishlistItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Update wishlist count in session
    $_SESSION['wishlist_count'] = count($wishlistItems);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = 'Error retrieving wishlist items';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - NawLexKen Books</title>
    <base href="<?php echo getBaseUrl(); ?>">
    <link rel="stylesheet" href="assets/css/wishlist.css">
</head>
<body data-authenticated="<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>">
    <div class="content-wrapper">
        <main class="wishlist-page">
            <div class="wishlist-container">
                <div class="wishlist-header">
                    <h1>My Wishlist (<?php echo count($wishlistItems); ?> items)</h1>
                    <?php if (!empty($wishlistItems)): ?>
                        <div class="wishlist-actions">
                            <label class="select-all">
                                <input type="checkbox" id="selectAllWishlist">
                                <span>Select All</span>
                            </label>
                            <button id="addSelectedToCart" class="bulk-action-btn" disabled>
                                <i class="fas fa-shopping-cart"></i> Add Selected to Cart
                            </button>
                            <button id="removeSelected" class="bulk-action-btn" disabled>
                                <i class="fas fa-trash"></i> Remove Selected
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if (empty($wishlistItems)): ?>
                    <div class="empty-wishlist">
                        <i class="fas fa-heart"></i>
                        <p>Your wishlist is empty</p>
                        <a href="index.php" class="continue-shopping">Browse Books</a>
                    </div>
                <?php else: ?>
                    <div class="wishlist-grid">
                        <?php foreach ($wishlistItems as $item): ?>
                            <div class="wishlist-item" data-book-id="<?php echo $item['book_id']; ?>">
                                <div class="wishlist-item-controls">
                                    <input type="checkbox" class="wishlist-item-checkbox"
                                           <?php echo !$item['is_in_stock'] ? 'disabled' : ''; ?>>
                                    <button class="remove-wishlist" title="Remove from wishlist">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                
                                <a href="pages/books/details.php?id=<?php echo $item['book_id']; ?>" class="book-image">
                                    <img src="data:<?php echo $item['image_type']; ?>;base64,<?php echo base64_encode($item['image_data']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['book_name']); ?>"
                                         onerror="this.src='assets/images/book-placeholder.jpg'">
                                    <?php if ($item['status'] !== 'regular'): ?>
                                        <span class="book-status status-<?php echo $item['status']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $item['status'])); ?>
                                        </span>
                                    <?php endif; ?>
                                </a>

                                <div class="book-info">
                                    <h3>
                                        <a href="pages/books/details.php?id=<?php echo $item['book_id']; ?>">
                                            <?php echo htmlspecialchars($item['book_name']); ?>
                                        </a>
                                    </h3>
                                    <p class="author">by <?php echo htmlspecialchars($item['author']); ?></p>
                                    
                                    <div class="price-section">
                                        <?php if ($item['is_special'] && $item['special_price']): ?>
                                            <?php $prices = getDisplayPrice($item['price'], $item['special_price']); ?>
                                            <span class="original-price"><?php echo $prices['original']; ?></span>
                                            <span class="special-price"><?php echo $prices['special']; ?></span>
                                        <?php else: ?>
                                            <span class="price"><?php echo formatPrice($item['price']); ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="stock-status">
                                        <?php if ($item['is_in_stock']): ?>
                                            <span class="in-stock">In Stock</span>
                                        <?php else: ?>
                                            <span class="out-of-stock">Out of Stock</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="assets/js/wishlist.js"></script>
</body>
</html>