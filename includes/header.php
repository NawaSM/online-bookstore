<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('BASE_URL', '/onlinebookstore');

function getBaseUrl() {
    $currentPath = $_SERVER['PHP_SELF'];
    $pathInfo = pathinfo($currentPath);
    $hostName = $_SERVER['HTTP_HOST'];
    $protocol = strtolower(substr($_SERVER['SERVER_PROTOCOL'],0,5))=='https'?'https':'http';
    return $protocol.'://'.$hostName.'/onlinebookstore/';
}

function getUserThemePreference($userId) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT theme_preference FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 'light';
    }
}

require_once __DIR__ . '/../config/database.php';
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo isset($_SESSION['user_id']) ? (getUserThemePreference($_SESSION['user_id']) ?? 'light') : 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NawLexKen Books</title>
    <base href="<?php echo BASE_URL; ?>/">
    <link rel="stylesheet" href="<?php echo getBaseUrl(); ?>assets/css/layout.css">
    <link rel="stylesheet" href="<?php echo getBaseUrl(); ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo getBaseUrl(); ?>assets/css/index.css">
    <link rel="stylesheet" href="<?php echo getBaseUrl(); ?>assets/css/search.css">
    <link rel="stylesheet" href="<?php echo getBaseUrl(); ?>assets/css/book-card.css">
    <link rel="stylesheet" href="<?php echo getBaseUrl(); ?>assets/css/profile.css">
    <link rel="stylesheet" href="<?php echo getBaseUrl(); ?>assets/css/catalog.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo getBaseUrl(); ?>assets/css/notifications.css">
    <link rel="stylesheet" href="<?php echo getBaseUrl(); ?>assets/css/orders.css">
    <script src="<?php echo getBaseUrl(); ?>assets/js/notifications.js"></script>
    <script src="<?php echo getBaseUrl(); ?>assets/js/wishlist-handler.js"></script>
    <script src="<?php echo getBaseUrl(); ?>assets/js/headerSearch.js"></script> 
    <script src="<?php echo getBaseUrl(); ?>assets/js/cart-handler.js"></script>
    <script src="<?php echo getBaseUrl(); ?>assets/js/orders.js" defer></script>
</head>
<body data-authenticated="<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>">
    <header>
        <div class="header-container">
            <div class="logo">
                <a href="<?php echo getBaseUrl(); ?>index.php">NawLexKen Books</a>
            </div>
            <div class="search-bar">
                <form id="headerSearchForm" onsubmit="return false;">
                    <input type="text" 
                           id="headerSearchInput" 
                           name="search" 
                           placeholder="Search Book Names, Author, ISBN.."
                           autocomplete="off">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
                <div id="searchResults" class="search-results-dropdown"></div>
            </div>
            <div class="nav-icons">
                <a href="<?php echo getBaseUrl(); ?>pages/wishlist.php" class="icon-link">
                    <i class="fas fa-heart"></i>
                    <span class="icon-text">Wishlist</span>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <?php 
                        try {
                            $wishlistCountStmt = $conn->prepare("
                                SELECT COUNT(*) as count 
                                FROM wishlists 
                                WHERE user_id = ?
                            ");
                            $wishlistCountStmt->execute([$_SESSION['user_id']]);
                            $wishlistCount = $wishlistCountStmt->fetch(PDO::FETCH_ASSOC)['count'];
                            $_SESSION['wishlist_count'] = $wishlistCount;
                        } catch (PDOException $e) {
                            $wishlistCount = 0;
                            error_log("Error getting wishlist count: " . $e->getMessage());
                        }
                        ?>
                        <span class="count-badge wishlist-count"><?php echo $wishlistCount; ?></span>
                    <?php endif; ?>
                </a>

                <a href="<?php echo getBaseUrl(); ?>pages/cart/cart.php" class="icon-link">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="icon-text">Cart</span>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <?php 
                        try {
                            $cartCountStmt = $conn->prepare("
                                SELECT COALESCE(SUM(quantity), 0) as count 
                                FROM cart_items 
                                WHERE user_id = ?
                            ");
                            $cartCountStmt->execute([$_SESSION['user_id']]);
                            $cartCount = $cartCountStmt->fetch(PDO::FETCH_ASSOC)['count'];
                        } catch (PDOException $e) {
                            $cartCount = 0;
                            error_log("Error getting cart count: " . $e->getMessage());
                        }
                        ?>
                        <span class="count-badge cart-count"><?php echo $cartCount; ?></span>
                    <?php endif; ?>
                </a>

                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="<?php echo getBaseUrl(); ?>pages/account.php" class="icon-link">
                        <i class="fas fa-user"></i>
                        <span class="icon-text">Profile</span>
                    </a>
                    <a href="<?php echo getBaseUrl(); ?>logout.php" class="icon-link logout">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="icon-text">Logout</span>
                    </a>
                <?php else: ?>
                    <a href="<?php echo getBaseUrl(); ?>pages/login.php" class="icon-link">
                        <i class="fas fa-sign-in-alt"></i>
                        <span class="icon-text">Login</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>