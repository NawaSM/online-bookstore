<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user data from database
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: login.php");
    exit();
}

$isLoggedIn = true;

// Handle profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $updateFields = [
        'first_name' => $_POST['firstName'],
        'last_name' => $_POST['lastName'],
        'email' => $_POST['email'],
        'house_number' => $_POST['address'],
        'street_name' => $_POST['street'],
        'city' => $_POST['city'],
        'district' => $_POST['district'],
        'state' => $_POST['state'],
        'country' => $_POST['country']
    ];
    
    $sql = "UPDATE users SET ";
    $updates = [];
    $params = [];
    
    foreach ($updateFields as $field => $value) {
        $updates[] = "$field = ?";
        $params[] = $value;
    }
    
    $sql .= implode(', ', $updates);
    $sql .= " WHERE id = ?";
    $params[] = $user_id;
    
    $updateStmt = $pdo->prepare($sql);
    if ($updateStmt->execute($params)) {
        // Refresh user data after update
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
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
                    <h2>Welcome, <?php echo htmlspecialchars($user['username']); ?></h2>
                </div>

                <form id="profileForm" class="profile-form" method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="userId">User ID:</label>
                            <input type="text" id="userId" name="userId" value="<?php echo htmlspecialchars($user['id']); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="firstName">First Name:</label>
                            <input type="text" id="firstName" name="firstName" value="<?php echo htmlspecialchars($user['first_name']); ?>" disabled>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="lastName">Last Name:</label>
                            <input type="text" id="lastName" name="lastName" value="<?php echo htmlspecialchars($user['last_name']); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="address">House Lot/Building Number/Room Number:</label>
                            <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['house_number']); ?>" disabled>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="street">Street Name:</label>
                            <input type="text" id="street" name="street" value="<?php echo htmlspecialchars($user['street_name']); ?>" disabled>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">City/Town:</label>
                            <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($user['city']); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label for="district">District Area/Region/Mukim:</label>
                            <input type="text" id="district" name="district" value="<?php echo htmlspecialchars($user['district']); ?>" disabled>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="state">State:</label>
                            <input type="text" id="state" name="state" value="<?php echo htmlspecialchars($user['state']); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label for="country">Country:</label>
                            <input type="text" id="country" name="country" value="<?php echo htmlspecialchars($user['country']); ?>" disabled>
                        </div>
                    </div>

                    <div class="button-group">
                        <button type="button" id="editBtn">Edit</button>
                        <button type="submit" id="saveBtn" style="display: none;">Save</button>
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

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const editBtn = document.getElementById('editBtn');
        const saveBtn = document.getElementById('saveBtn');
        const inputs = document.querySelectorAll('#profileForm input:not([readonly])');
        const logoutBtn = document.getElementById('logoutBtn');

        editBtn.addEventListener('click', () => {
            inputs.forEach(input => input.disabled = false);
            saveBtn.style.display = 'inline-block';
            editBtn.style.display = 'none';
        });

        logoutBtn.addEventListener('click', () => {
            if (confirm('Are you sure you want to log out?')) {
                window.location.href = 'logout.php';
            }
        });
    });
    </script>
</body>
</html>