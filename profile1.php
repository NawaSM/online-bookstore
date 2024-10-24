<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NawLexKen Books - User Profile</title>
    <link rel="stylesheet" href="profile.css">
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
    function clearSearch() {
        document.getElementById('searchInput').value = '';
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
                <a href="#" onclick="checkLogin(event, 'wishlist1.php')" aria-label="Wishlist"><i class="fas fa-heart"></i></a>
                <a href="#" onclick="checkLogin(event, 'cart.php')" aria-label="Shopping Cart"><i class="fas fa-shopping-cart"></i></a>
            </div> 
        </nav>
    </header>

    <main>
        <div class="profile-container">
            <div class="sidebar">
                <button class="nav-btn active">Manage Profile</button>
                <button class="nav-btn">Order Tracking</button>
                <button class="nav-btn">Order History</button>
                <button class="nav-btn">Wishlist</button>
                <button id="logoutBtn" class="nav-btn logout-btn">Log Out</button>
            </div>

            <div class="profile-content">
                <div class="profile-header">
                    <h2>Welcome, John Doe</h2>
                </div>

                <form id="profileForm" class="profile-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="userId">User ID:</label>
                            <input type="text" id="userId" name="userId" readonly>
                        </div>
                        <div class="form-group">
                            <label for="firstName">First Name:</label>
                            <input type="text" id="firstName" name="firstName" disabled>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="lastName">Last Name:</label>
                            <input type="text" id="lastName" name="lastName" disabled>
                        </div>
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" disabled>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="address">House Lot/Building Number/Room Number:</label>
                            <input type="text" id="address" name="address" disabled>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="street">Street Name:</label>
                            <input type="text" id="street" name="street" disabled>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">City/Town:</label>
                            <input type="text" id="city" name="city" disabled>
                        </div>
                        <div class="form-group">
                            <label for="district">District Area/Region/Mukim:</label>
                            <input type="text" id="district" name="district" disabled>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="state">State:</label>
                            <input type="text" id="state" name="state" disabled>
                        </div>
                        <div class="form-group">
                            <label for="country">Country:</label>
                            <input type="text" id="country" name="country" disabled>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group password-group">
                            <label for="password">Password:</label>
                            <div class="password-row">
                                <input type="password" id="password" name="password" disabled>
                                <span id="togglePassword" class="eye">👁️</span>
                            </div>
                        </div>
                    </div>

                    <div class="button-group">
                        <button type="button" id="editBtn">Edit</button>
                        <button type="button" id="saveBtn" style="display: none;">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <footer>
        <p>© 2024 NawLexKen Books. All rights reserved.</p>
        <nav>
            <a href="#">Terms of Service</a> |
            <a href="#">Privacy Policy</a> |
            <a href="#">Contact Us</a>
        </nav>
    </footer>

    <script src="profile.js"></script>
</body>
</html>