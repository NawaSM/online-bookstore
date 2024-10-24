<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

$error = '';
$success = '';
$isLoggedIn = isset($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    $email = sanitize_input($_POST['email']);
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $house_number = sanitize_input($_POST['house_number']);
    $street_name = sanitize_input($_POST['street_name']);
    $city = sanitize_input($_POST['city']);
    $district = sanitize_input($_POST['district']);
    $state = sanitize_input($_POST['state']);
    $country = sanitize_input($_POST['country']);

    if (empty($username) || empty($password) || empty($email) || empty($first_name) || empty($last_name) ||
        empty($house_number) || empty($street_name) || empty($city) || empty($district) || empty($state) || empty($country)) {
        $error = "All fields are required.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            $existing_user = $stmt->fetch();

            if ($existing_user) {
                $error = "Username or email already exists.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, email, first_name, last_name, house_number, street_name, city, district, state, country) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$username, $hashed_password, $email, $first_name, $last_name, $house_number, $street_name, $city, $district, $state, $country]);
                $success = "Registration successful! You can now log in.";
            }
        } catch (PDOException $e) {
            $error = "Error creating user account: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration - NawLexKen Books</title>
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="css/user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
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
        <div class="container">
            <h2>User Registration</h2>
            <?php if ($error): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php endif; ?>
            <?php if ($success): ?>
                <p class="success"><?php echo $success; ?></p>
            <?php endif; ?>
            <form action="register.php" method="post">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="first_name">First Name:</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name:</label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>
                <div class="form-group">
                    <label for="house_number">House/Lot/Building/Room Number:</label>
                    <input type="text" id="house_number" name="house_number" required>
                </div>
                <div class="form-group">
                    <label for="street_name">Street Name:</label>
                    <input type="text" id="street_name" name="street_name" required>
                </div>
                <div class="form-group">
                    <label for="city">City/Town:</label>
                    <input type="text" id="city" name="city" required>
                </div>
                <div class="form-group">
                    <label for="district">District Area/Region/Mukim:</label>
                    <input type="text" id="district" name="district" required>
                </div>
                <div class="form-group">
                    <label for="state">State:</label>
                    <input type="text" id="state" name="state" required>
                </div>
                <div class="form-group">
                    <label for="country">Country:</label>
                    <input type="text" id="country" name="country" required>
                </div>
                <button type="submit">Register</button>
            </form>
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 Online Bookstore. All rights reserved.</p>
        <nav>
            <a href="#">Terms of Service</a> |
            <a href="#">Privacy Policy</a> |
            <a href="#">Contact Us</a>
        </nav>
    </footer>
</body>
</html>