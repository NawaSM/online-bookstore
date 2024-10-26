<?php
session_start();
require_once 'includes/db_connect.php';
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NawLexKen Books</title>
    <link rel="stylesheet" href="home.css">
    <script src="https://kit.fontawesome.com/1ff274c5ae.js" crossorigin="anonymous"></script>
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
                <li><a href="#bestsellers">Bestsellers</a></li>
                <li><a href="#coming-soon">Coming Soon</a></li>
                <li><a href="#new">New</a></li>
                <li><a href="#specials">Specials</a></li>
            </ul>
            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Search books...">
                <button onclick="clearSearch()">✖</button>
            </div>
            <div class="icons">
                <a href="#" onclick="checkLogin(event, 'wishlist1.php')" aria-label="Wishlist">
                    <i class="fas fa-heart"></i>
                </a>
                <a href="#" onclick="checkLogin(event, 'cart.php')" aria-label="Shopping Cart">
                    <i class="fas fa-shopping-cart"></i>
                </a>
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

        <!-- Bestsellers Section -->
        <section id="bestsellers" class="books-section">
            <div class="section-header">
                <h3>Bestsellers</h3>
                <button class="view-more">View More</button>
            </div>
            <div class="book-slider-container">
                <button class="slider-button left">‹</button>
                <div id="bookSlider" class="book-slider" data-category="bestseller">
                    <!-- Books will be loaded dynamically -->
                    <div class="loading-spinner">Loading books...</div>
                </div>
                <button class="slider-button right">›</button>
            </div>
        </section>

        <!-- Coming Soon Section -->
        <section id="coming-soon" class="books-section">
            <div class="section-header">
                <h3>Coming Soon</h3>
                <button class="view-more">View More</button>
            </div>
            <div class="book-slider-container">
                <button class="slider-button left">‹</button>
                <div class="book-slider" data-category="coming_soon">
                    <!-- Books will be loaded dynamically -->
                </div>
                <button class="slider-button right">›</button>
            </div>
        </section>

        <!-- New Releases Section -->
        <section id="new" class="books-section">
            <div class="section-header">
                <h3>New Releases</h3>
                <button class="view-more">View More</button>
            </div>
            <div class="book-slider-container">
                <button class="slider-button left">‹</button>
                <div class="book-slider" data-category="new">
                    <!-- Books will be loaded dynamically -->
                </div>
                <button class="slider-button right">›</button>
            </div>
        </section>

        <!-- Specials Section -->
        <section id="specials" class="books-section">
            <div class="section-header">
                <h3>Special Offers</h3>
                <button class="view-more">View More</button>
            </div>
            <div class="book-slider-container">
                <button class="slider-button left">‹</button>
                <div class="book-slider" data-category="special">
                    <!-- Books will be loaded dynamically -->
                </div>
                <button class="slider-button right">›</button>
            </div>
        </section>
    </main>

    <!-- Book Details Modal -->
    <div id="book-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div class="modal-book-details">
                <div class="book-image">
                    <img id="modal-image" src="" alt="Book Cover">
                </div>
                <div class="book-info">
                    <h2 id="modal-title"></h2>
                    <p id="modal-author" class="author"></p>
                    <p id="modal-isbn" class="isbn"></p>
                    <p id="modal-price" class="price"></p>
                    <p id="modal-special-price" class="special-price"></p>
                    <p id="modal-category" class="category"></p>
                    <p id="modal-genres" class="genres"></p>
                    <p id="modal-release-year" class="release-year"></p>
                    <p id="modal-status" class="status"></p>
                    <div class="modal-buttons">
                        <button id="modal-add-to-cart" class="add-to-cart">Add to Cart</button>
                        <button id="modal-add-to-wishlist" class="add-to-wishlist">Add to Wishlist</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 Online Bookstore. All rights reserved.</p>
        <nav>
            <a href="#">Terms of Service</a> |
            <a href="#">Privacy Policy</a> |
            <a href="#">Contact Us</a>
        </nav>
    </footer>

    <script src="home.js"></script>
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

        function clearSearch() {
            document.getElementById('searchInput').value = '';
        }
    </script>
</body>
</html>