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


    <main>
        <section class="ad-carousel">
            <div class="ad-container">
                <!-- Banners will be loaded dynamically -->
            </div>
            <button class="nav-button prev">‹</button>
            <button class="nav-button next">›</button>
        </section>

        <section class="bestsellers">
            <div class="section-header">
                <h3>Bestsellers</h3>
                <button class="view-more">View More</button>
            </div>
            <div class="book-slider-container">
                <button class="slider-button left">‹</button>
                <div id="bookSlider" class="book-slider">
                    <!-- Book items will be dynamically added here -->
                </div>
                <button class="slider-button right">›</button>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2024 Online Bookstore. All rights reserved.</p>
        <nav>
            <a href="#">Terms of Service</a> |
            <a href="#">Privacy Policy</a> |
            <a href="#">Contact Us</a>
        </nav>
    </footer>

    <div id="book-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <img id="modal-image" src="" alt="Book Cover">
            <h2 id="modal-title"></h2>
            <p id="modal-price"></p>
            <div id="modal-rating"></div>
            <p id="modal-description"></p>
            <button class="add-to-cart">Add to Cart</button>
            <button class="add-to-wishlist">Add to Wishlist</button>
        </div>
    </div>

    <script src="home.js"></script>
</body>
</html>