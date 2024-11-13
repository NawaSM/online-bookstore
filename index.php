<?php
require_once 'config/database.php';
require_once 'config/currency.php'; 
include 'includes/header.php';

// Add wishlist check function
function checkWishlist($conn, $userId, $bookId) {
    if (!$userId) return false;
    
    $stmt = $conn->prepare("
        SELECT 1 FROM wishlists 
        WHERE user_id = ? AND book_id = ?
    ");
    $stmt->execute([$userId, $bookId]);
    return (bool)$stmt->fetch();
}

// Fetch banner ads (keeping existing query)
$stmt = $conn->prepare("SELECT * FROM banner_ads WHERE is_active = 1 ORDER BY display_order");
$stmt->execute();
$banners = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch featured books (keeping existing query)
$stmt = $conn->prepare("
    SELECT * FROM inventory 
    WHERE status = 'bestseller' 
    AND quantity > 0 
    ORDER BY created_at DESC 
    LIMIT 8
");
$stmt->execute();
$featuredBooks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch new arrivals (keeping existing query)
$stmt = $conn->prepare("
    SELECT * FROM inventory 
    WHERE status = 'new' 
    AND quantity > 0 
    ORDER BY created_at DESC 
    LIMIT 8
");
$stmt->execute();
$newArrivals = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Fetch regular books
$stmt = $conn->prepare("
    SELECT * FROM inventory 
    WHERE status = 'regular' 
    AND quantity > 0 
    ORDER BY created_at DESC 
    LIMIT 8
");
$stmt->execute();
$regularBooks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch coming soon books
$stmt = $conn->prepare("
    SELECT * FROM inventory 
    WHERE status = 'coming_soon' 
    AND quantity > 0 
    ORDER BY created_at DESC 
    LIMIT 8
");
$stmt->execute();
$comingSoonBooks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main>
    <!-- Banner Section (unchanged) -->
    <div class="banner-container">
        <div class="banner-wrapper">
            <?php foreach ($banners as $banner): ?>
                <div class="banner-slide">
                    <?php if ($banner['type'] === 'image'): ?>
                        <img src="data:<?php echo $banner['image_type']; ?>;base64,<?php echo base64_encode($banner['image_data']); ?>" 
                             alt="<?php echo htmlspecialchars($banner['title']); ?>">
                    <?php else: ?>
                        <div class="promo-banner">
                            <h2><?php echo htmlspecialchars($banner['promo_heading']); ?></h2>
                            <p><?php echo htmlspecialchars($banner['promo_text']); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <button class="banner-control banner-prev"><i class="fas fa-chevron-left"></i></button>
        <button class="banner-control banner-next"><i class="fas fa-chevron-right"></i></button>
    </div>

    <!-- Bestseller Books Section -->
    <section class="book-section">
        <div class="section-header">
            <h2>Featured Books</h2>
            <a href="pages/catalog.php?category=bestseller" class="view-all">View All</a>
        </div>
        <div class="book-grid">
            <?php foreach ($featuredBooks as $book): 
                $isInWishlist = isset($_SESSION['user_id']) ? 
                    checkWishlist($conn, $_SESSION['user_id'], $book['id']) : false;
            ?>
                <div class="book-card">
                    <div class="book-image">
                        <img src="data:<?php echo $book['image_type']; ?>;base64,<?php echo base64_encode($book['image_data']); ?>" 
                             alt="<?php echo htmlspecialchars($book['book_name']); ?>">
                        <button class="wishlist-icon <?php echo $isInWishlist ? 'active' : ''; ?>" 
                                data-book-id="<?php echo $book['id']; ?>">
                            <i class="<?php echo $isInWishlist ? 'fas' : 'far'; ?> fa-heart"></i>
                        </button>
                    </div>
                    <div class="book-info">
                        <h3>
                            <a href="pages/books/details.php?id=<?php echo $book['id']; ?>" class="book-link">
                                <?php echo htmlspecialchars($book['book_name']); ?>
                            </a>
                        </h3>
                        <p class="author">by <?php echo htmlspecialchars($book['author']); ?></p>
                        <p class="price">
                            <?php if ($book['is_special'] && $book['special_price']): ?>
                                <?php $prices = getDisplayPrice($book['price'], $book['special_price']); ?>
                                <span class="original-price"><?php echo $prices['original']; ?></span>
                                <span class="special-price"><?php echo $prices['special']; ?></span>
                            <?php else: ?>
                                <?php echo getDisplayPrice($book['price']); ?>
                            <?php endif; ?>
                        </p>
                        <button class="add-to-cart" 
                                data-book-id="<?php echo $book['id']; ?>"
                                <?php echo !isset($_SESSION['user_id']) ? 'data-redirect="login"' : ''; ?>>
                            Add to Cart
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- New Arrivals Section -->
    <section class="book-section">
        <div class="section-header">
            <h2>New Arrivals</h2>
            <a href="pages/catalog.php?category=new" class="view-all">View All</a>
        </div>
        <div class="book-grid">
            <?php foreach ($newArrivals as $book): 
                $isInWishlist = isset($_SESSION['user_id']) ? 
                    checkWishlist($conn, $_SESSION['user_id'], $book['id']) : false;
            ?>
                <div class="book-card">
                    <div class="book-image">
                        <img src="data:<?php echo $book['image_type']; ?>;base64,<?php echo base64_encode($book['image_data']); ?>" 
                             alt="<?php echo htmlspecialchars($book['book_name']); ?>">
                        <button class="wishlist-icon <?php echo $isInWishlist ? 'active' : ''; ?>" 
                                data-book-id="<?php echo $book['id']; ?>">
                            <i class="<?php echo $isInWishlist ? 'fas' : 'far'; ?> fa-heart"></i>
                        </button>
                    </div>
                    <div class="book-info">
                        <h3><?php echo htmlspecialchars($book['book_name']); ?></h3>
                        <p class="author">by <?php echo htmlspecialchars($book['author']); ?></p>
                        <p class="price">
                            <?php if ($book['is_special'] && $book['special_price']): ?>
                                <?php $prices = getDisplayPrice($book['price'], $book['special_price']); ?>
                                <span class="original-price"><?php echo $prices['original']; ?></span>
                                <span class="special-price"><?php echo $prices['special']; ?></span>
                            <?php else: ?>
                                <?php echo getDisplayPrice($book['price']); ?>
                            <?php endif; ?>
                        </p>
                       
                        <button class="add-to-cart" 
                                data-book-id="<?php echo $book['id']; ?>"
                                <?php echo !isset($_SESSION['user_id']) ? 'data-redirect="login"' : ''; ?>>
                            Add to Cart
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    
        <!-- Regular Books Section -->
    <section class="book-section">
        <div class="section-header">
            <h2>Regular Books</h2>
            <a href="pages/catalog.php?category=regular" class="view-all">View All</a>
        </div>
        <div class="book-grid">
            <?php foreach ($regularBooks as $book): 
                $isInWishlist = isset($_SESSION['user_id']) ? 
                    checkWishlist($conn, $_SESSION['user_id'], $book['id']) : false;
            ?>
                <div class="book-card">
                    <div class="book-image">
                        <img src="data:<?php echo $book['image_type']; ?>;base64,<?php echo base64_encode($book['image_data']); ?>" 
                             alt="<?php echo htmlspecialchars($book['book_name']); ?>">
                        <button class="wishlist-icon <?php echo $isInWishlist ? 'active' : ''; ?>" 
                                data-book-id="<?php echo $book['id']; ?>">
                            <i class="<?php echo $isInWishlist ? 'fas' : 'far'; ?> fa-heart"></i>
                        </button>
                    </div>
                    <div class="book-info">
                        <h3><?php echo htmlspecialchars($book['book_name']); ?></h3>
                        <p class="author">by <?php echo htmlspecialchars($book['author']); ?></p>
                        <p class="price">
                            <?php if ($book['is_special'] && $book['special_price']): ?>
                                <?php $prices = getDisplayPrice($book['price'], $book['special_price']); ?>
                                <span class="original-price"><?php echo $prices['original']; ?></span>
                                <span class="special-price"><?php echo $prices['special']; ?></span>
                            <?php else: ?>
                                <?php echo getDisplayPrice($book['price']); ?>
                            <?php endif; ?>
                        </p>
                        <button class="add-to-cart" 
                                data-book-id="<?php echo $book['id']; ?>"
                                <?php echo !isset($_SESSION['user_id']) ? 'data-redirect="login"' : ''; ?>>
                            Add to Cart
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Coming Soon Section -->
    <section class="book-section">
        <div class="section-header">
            <h2>Coming Soon</h2>
            <a href="pages/catalog.php?category=coming_soon" class="view-all">View All</a>
        </div>
        <div class="book-grid">
            <?php foreach ($comingSoonBooks as $book): 
                $isInWishlist = isset($_SESSION['user_id']) ? 
                    checkWishlist($conn, $_SESSION['user_id'], $book['id']) : false;
            ?>
                <div class="book-card">
                    <div class="book-image">
                        <img src="data:<?php echo $book['image_type']; ?>;base64,<?php echo base64_encode($book['image_data']); ?>" 
                             alt="<?php echo htmlspecialchars($book['book_name']); ?>">
                        <button class="wishlist-icon <?php echo $isInWishlist ? 'active' : ''; ?>" 
                                data-book-id="<?php echo $book['id']; ?>">
                            <i class="<?php echo $isInWishlist ? 'fas' : 'far'; ?> fa-heart"></i>
                        </button>
                    </div>
                    <div class="book-info">
                        <h3><?php echo htmlspecialchars($book['book_name']); ?></h3>
                        <p class="author">by <?php echo htmlspecialchars($book['author']); ?></p>
                        <p class="price">
                            <?php if ($book['is_special'] && $book['special_price']): ?>
                                <?php $prices = getDisplayPrice($book['price'], $book['special_price']); ?>
                                <span class="original-price"><?php echo $prices['original']; ?></span>
                                <span class="special-price"><?php echo $prices['special']; ?></span>
                            <?php else: ?>
                                <?php echo getDisplayPrice($book['price']); ?>
                            <?php endif; ?>
                        </p>
                        <button class="add-to-cart" 
                                data-book-id="<?php echo $book['id']; ?>"
                                <?php echo !isset($_SESSION['user_id']) ? 'data-redirect="login"' : ''; ?>>
                            Add to Cart
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>