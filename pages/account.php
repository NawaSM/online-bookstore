<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once('../config/database.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . getBaseUrl() . 'pages/login.php');
    exit();
}

$section = isset($_GET['section']) ? $_GET['section'] : 'details';
echo "<!-- Current section: " . $section . " -->";

// Fetch user data
$userId = $_SESSION['user_id'];
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = :userId");
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('User not found');
    }
} catch (PDOException $e) {
    error_log('Database Error: ' . $e->getMessage());
    $error = 'An error occurred while fetching user data.';
}

require_once('../includes/header.php');  
?>

<div class="account-container">
    <div class="account-sidebar">
        <div class="user-profile">
            <div class="profile-image">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="profile-info">
                <h2><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h2>
                <p><?php echo htmlspecialchars($user['email']); ?></p>
            </div>
        </div>
        <nav class="account-nav">
            <a href="<?php echo getBaseUrl(); ?>pages/account.php" class="nav-item <?php echo (!isset($_GET['section']) || $_GET['section'] === 'details') ? 'active' : ''; ?>">
                <i class="fas fa-user"></i> Personal Details
            </a>
            <a href="pages/profile/orders.php" class="nav-item <?php echo (isset($_GET['section']) && $_GET['section'] === 'orders') ? 'active' : ''; ?>">
                <i class="fas fa-shopping-bag"></i> Order History
            </a>
            <a href="<?php echo getBaseUrl(); ?>pages/wishlist.php" class="nav-item <?php echo (isset($_GET['section']) && $_GET['section'] === 'wishlist') ? 'active' : ''; ?>">
                <i class="fas fa-heart"></i> Wishlist
            </a>
            <a href="<?php echo getBaseUrl(); ?>logout.php" class="nav-item logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>
    
    <div class="account-content">
        <?php
        $section = isset($_GET['section']) ? $_GET['section'] : 'details';
        $allowedSections = ['details', 'orders', 'wishlist'];

        if (in_array($section, $allowedSections)) {
            // Add debug
            error_log("Loading section: " . $section);
            include __DIR__ . "/profile/{$section}.php";
        } else {
            // Add debug
            error_log("Loading default section: details");
            include __DIR__ . "/profile/details.php";
        }
        ?>
    </div>
</div>

<?php include('../includes/footer.php'); ?>