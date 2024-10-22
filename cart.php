<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

$isLoggedIn = isset($_SESSION['user_id']);

// For debugging:
/*var_dump($_SESSION);

echo "<h2>Debug Information:</h2>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";*/

// Check cart items in the database
$user_id = $_SESSION['user_id'] ?? 0;
$stmt = $pdo->prepare("SELECT ci.*, i.book_name, i.price 
                       FROM cart_items ci
                       JOIN inventory i ON ci.book_id = i.id
                       WHERE ci.user_id = ?");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*echo "<h3>Cart Items in Database:</h3>";
echo "<pre>";
print_r($cart_items);
echo "</pre>";*/
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



    <div class="container">
        <h1>Your Shopping Cart</h1>
        <div id="cart-items"></div>
        <div id="wishlist"></div>
        <div id="cart-summary">
            <h2>Cart Summary</h2>
            <div id="summary-details"></div>
            <div id="discount-section">
                <input type="text" id="discount-code" placeholder="Enter promo code">
                <button id="apply-discount" class="navy-button">Apply Promo</button>
            </div>
            <textarea id="special-remarks" placeholder="Special remarks for your order"></textarea>
            <button id="pay-button" class="navy-button">Proceed to Checkout</button>
        </div>
        <div id="payment-status"></div>
    </div>
    
   


    <footer>
        <p>&copy; 2024 Online Bookstore. All rights reserved.</p>
        <nav>
            <a href="#">Terms of Service</a> |
            <a href="#">Privacy Policy</a> |
            <a href="#">Contact Us</a>
        </nav>
    </footer>
    <script src="cart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM fully loaded');
        try {
            loadCart();
        } catch (error) {
            console.error('Error loading cart:', error);
        }
    });
    </script>
</body>
</html>