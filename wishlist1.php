<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NawLexKen Books</title>
    <!-- Use the same CSS file as the original for consistent styling -->
    <link rel="stylesheet" href="home.css">
    <script src="https://kit.fontawesome.com/1ff274c5ae.js" crossorigin="anonymous"></script>
    <script>
    function checkLogin(event, destination) {
        event.preventDefault();
        fetch('check_login.php')
            .then(response => response.json())
            .then(data => {
                if (data.loggedIn) {
                    window.location.href = destination;
                } else {
                    window.location.href = 'login.php';
                }
            });
    }
    </script>
</head>
<body>
    <header>
        <div class="top-header">
            <a href="home.php" class="logo">NawLexKen Books</a>
            <div class="user-actions">
                <a href="feedback1.php" class="admin-assistance">Admin Assistance</a>
                <?php if ($isLoggedIn): ?>
                    <a href="profile1.php" id="accountLink">Account</a>
                <?php else: ?>
                    <a href="login.php" id="accountLink">Login</a>
                <?php endif; ?>
                <a href="#subscription">Subscription</a>
            </div>
        </div>
        <nav class="main-nav">
            <ul>
                <li><a href="bestsellers.php">Bestsellers</a></li>
                <li><a href="#coming-soon">Coming Soon</a></li>
                <li><a href="#new">New</a></li>
                <li><a href="#specials">Specials</a></li>
            </ul>
            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Search books...">
                <button onclick="clearSearch()">✖</button>
            </div>
            <div class="icons">
                <!-- Ensure buttons are aligned properly using flexbox -->
                <a href="#" onclick="checkLogin(event, 'wishlist1.php')" aria-label="Wishlist"><i class="fas fa-heart"></i></a>
                <a href="#" onclick="checkLogin(event, 'cart.php')" aria-label="Shopping Cart"><i class="fas fa-shopping-cart"></i></a>
            </div> 
        </nav>
    </header>

    <!-- Wishlist Content -->
    <div class="wishlist-container">
        <aside class="sidebar">
            <div class="search-container">
                <input type="text" id="search" placeholder="Search Wishlist..." oninput="filterWishlist()">
            </div>
            <div class="sort-container">
                <button id="sort-btn" onclick="toggleSortMenu()">Sort</button>
                <div id="sort-menu" class="sort-menu">
                    <button onclick="sortWishlist('fiction')">Fiction</button>
                    <button onclick="sortWishlist('nonFiction')">Non-Fiction</button>
                    <button onclick="sortWishlist('recent')">Date Added (Recently)</button>
                    <button onclick="sortWishlist('oldest')">Date Added (Oldest)</button>
                </div>
            </div>
            <div class="wishlist-count">
                <p>Items in Wishlist: <span id="item-count">0</span></p>
            </div>
            <button id="share-btn" onclick="shareWishlist()">Share Wishlist</button>
        </aside>

        <main class="wishlist-items">
            <div id="wishlist" class="items-container">
                <!-- Wishlist items will be dynamically added here -->
            </div>
            <button onclick="removeSelected()">Remove Selected</button>
            <button onclick="addSelectedToCart()">Add Selected to Cart</button>
        </main>
    </div>

    <!-- Footer copied from Cart page -->
    <footer>
        <p>&copy; 2024 Online Bookstore. All rights reserved.</p>
        <nav>
            <a href="#">Terms of Service</a> |
            <a href="#">Privacy Policy</a> |
            <a href="#">Contact Us</a>
        </nav>
    </footer>
    
    <script src="wishlist.js"></script> <!-- Include Wishlist JS -->
</body>
</html>
