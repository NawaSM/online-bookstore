<!-- includes/header.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Online Bookstore'; ?></title>
    <link rel="stylesheet" href="/css/user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <header>
        <nav class="main-nav">
            <div class="logo">LOGO</div>
            <ul class="nav-links">
                <li><a href="/user/index.php">Top Sellers</a></li>
                <li><a href="/user/coming-soon.php">Coming Soon</a></li>
                <li><a href="/user/new-releases.php">New</a></li>
                <li><a href="/user/specials.php">Specials</a></li>
            </ul>
            <div class="search-bar">
                <input type="text" placeholder="Search...">
                <button type="submit">
                    <i class="fas fa-search"></i>
                </button>
            </div>
            <div class="user-actions">
                <a href="/user/cart.php" class="cart-icon"><i class="fas fa-shopping-cart"></i></a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/user/profile.php" class="user-icon"><i class="fas fa-user"></i></a>
                    <a href="/user/logout.php">Logout</a>
                <?php else: ?>
                    <a href="/user/login.php">Login</a>
                    <a href="/user/register.php">Register</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>
    <main>