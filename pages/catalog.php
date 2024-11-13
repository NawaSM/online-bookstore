<?php
require_once '../config/database.php';
require_once '../config/currency.php';
include '../includes/header.php';

function checkWishlist($conn, $userId, $bookId) {
    if (!$userId) return false;
    
    $stmt = $conn->prepare("
        SELECT 1 FROM wishlists 
        WHERE user_id = ? AND book_id = ?
    ");
    $stmt->execute([$userId, $bookId]);
    return (bool)$stmt->fetch();
}

// Get status from URL, default to 'all'
$status = isset($_GET['category']) ? $_GET['category'] : 'all';
$validStatuses = ['bestseller', 'coming_soon', 'new', 'regular'];

// Define status titles
$statusTitles = [
    'all' => 'All Books',
    'bestseller' => 'Bestseller Books',
    'coming_soon' => 'Coming Soon',
    'new' => 'New Arrivals',
    'regular' => 'Regular Books'
];
$pageTitle = $statusTitles[$status] ?? 'All Books';

// Get search term from URL
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    // Base query
    $sql = "SELECT * FROM inventory WHERE 1=1";
    $params = [];

    // Add status filter if not 'all'
    if ($status !== 'all' && in_array($status, $validStatuses)) {
        $sql .= " AND status = :status";
        $params[':status'] = $status;
    }

    // Add search filter if search term exists
    if (!empty($search)) {
        $sql .= " AND (book_name LIKE :search 
                OR author LIKE :search 
                OR isbn LIKE :search)";
        $params[':search'] = "%{$search}%";
    }

    $sql .= " ORDER BY created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log("Query error: " . $e->getMessage());
    $books = [];
}
?>

<link rel="stylesheet" href="../assets/css/catalog.css">

<main class="catalog-layout">
    <aside class="catalog-sidebar">
    <nav class="status-nav">
        <a href="/onlinebookstore/pages/catalog.php?category=all" class="<?php echo $status === 'all' ? 'active' : ''; ?>">
            All Books
        </a>
        <a href="/onlinebookstore/pages/catalog.php?category=bestseller" class="<?php echo $status === 'bestseller' ? 'active' : ''; ?>">
            Bestseller Books
        </a>
        <a href="/onlinebookstore/pages/catalog.php?category=coming_soon" class="<?php echo $status === 'coming_soon' ? 'active' : ''; ?>">
            Coming Soon
        </a>
        <a href="/onlinebookstore/pages/catalog.php?category=new" class="<?php echo $status === 'new' ? 'active' : ''; ?>">
            New Arrivals
        </a>
        <a href="/onlinebookstore/pages/catalog.php?category=regular" class="<?php echo $status === 'regular' ? 'active' : ''; ?>">
            Regular Books
        </a>
    </nav>
    </aside>
    
    <div class="catalog-content">     
        <div class="catalog-header">
            <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
            <form class="search-form" id="searchForm" onsubmit="return false;">
                <input type="text" 
                       id="searchInput"
                       name="search" 
                       placeholder="Search by book name, author, or ISBN..." 
                       value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                       class="search-input">
                <button type="button" class="search-button" onclick="performSearch()">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>

        <div class="book-grid">
            <?php if (empty($books)): ?>
                <p class="no-results">No books found. Try different search terms or filters.</p>
            <?php else: ?>
                <?php foreach ($books as $book): 
                    $isInWishlist = isset($_SESSION['user_id']) ? 
                        checkWishlist($conn, $_SESSION['user_id'], $book['id']) : false;
                ?>
                    <div class="book-card">
                        <div class="book-image">
                            <img src="data:<?php echo $book['image_type']; ?>;base64,<?php echo base64_encode($book['image_data']); ?>" 
                                 alt="<?php echo htmlspecialchars($book['book_name']); ?>">
                            <button class="wishlist-icon <?php echo $isInWishlist ? 'active' : ''; ?>" 
                                    data-book-id="<?php echo $book['id']; ?>"
                                    onclick="wishlist.toggleWishlist(<?php echo $book['id']; ?>)">
                                <i class="<?php echo $isInWishlist ? 'fas' : 'far'; ?> fa-heart"></i>
                            </button>
                        </div>
                        <div class="book-info">
                            <h3>
                                <a href="/onlinebookstore/pages/books/details.php?id=<?php echo $book['id']; ?>">
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
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
let currentStatus = '<?php echo htmlspecialchars($status); ?>';

function performSearch() {
    const searchTerm = document.getElementById('searchInput').value;
    
    fetch(`/onlinebookstore/assets/api/search_books.php?q=${encodeURIComponent(searchTerm)}&category=${encodeURIComponent(currentStatus)}`)
        .then(response => response.json())
        .then(data => {
            const bookGrid = document.querySelector('.book-grid');
            bookGrid.innerHTML = '';
            
            if (!data.books || data.books.length === 0) {
                bookGrid.innerHTML = '<p class="no-results">No books found. Try different search terms or filters.</p>';
                return;
            }

            data.books.forEach(book => {
                const isInWishlist = false; // You might want to handle wishlist status differently
                const bookCard = `
                    <div class="book-card">
                        <div class="book-image">
                            <img src="data:${book.image_type};base64,${book.image_data}" 
                                 alt="${book.book_name}">
                            <button class="wishlist-icon" 
                                    data-book-id="${book.id}"
                                    onclick="wishlist.toggleWishlist(${book.id})">
                                <i class="far fa-heart"></i>
                            </button>
                        </div>
                        <div class="book-info">
                            <h3>
                                <a href="/onlinebookstore/pages/books/details.php?id=${book.id}">
                                    ${book.book_name}
                                </a>
                            </h3>
                            <p class="author">by ${book.author}</p>
                            <p class="price">RM${parseFloat(book.price).toFixed(2)}</p>
                            <button class="add-to-cart" 
                                    onclick="${<?php echo isset($_SESSION['user_id']) ?> ? 
                                        `cart.addToCart(${book.id})` : 
                                        `window.location.href='/onlinebookstore/pages/login.php'`}">
                                Add to Cart
                            </button>
                        </div>
                    </div>
                `;
                bookGrid.innerHTML += bookCard;
            });
        })
        .catch(error => console.error('Error:', error));
}

function changeStatus(newStatus) {
    currentStatus = newStatus;
    document.querySelectorAll('.status-nav a').forEach(a => {
        a.classList.remove('active');
    });
    event.target.classList.add('active');
    performSearch();
}

// Add event listener for real-time search
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(this.searchTimeout);
    this.searchTimeout = setTimeout(performSearch, 500);
});
</script>

<?php include '../includes/footer.php'; ?>