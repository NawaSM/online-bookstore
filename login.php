<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

$error = '';
$isLoggedIn = isset($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header("Location: profile1.php");
        exit();
    } else {
        $error = "Invalid username or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login - NawLexKen Books</title>
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
            <h2>User Login</h2>
            <?php if ($error): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php endif; ?>
            <form action="login.php" method="post">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit">Login</button>
            </form>
            <p>Don't have an account? <a href="register.php">Register here</a></p>
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