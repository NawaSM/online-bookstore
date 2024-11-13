<?php
$current_path = $_SERVER['PHP_SELF'];
$base_url = '';
if (strpos($current_path, '/pages/') !== false) {
    $base_url = '../';
} else if (strpos($current_path, '/admin/') !== false) {
    $base_url = '../';
} else {
    $base_url = '';
}
?>

<footer>
    <div class="footer-container">
        <div class="footer-section">
            <h3>About Us</h3>
            <p>NawLexKen Books is your premier online destination for books of all genres. We offer a wide selection of books at competitive prices.</p>
        </div>
        <div class="footer-section">
            <h3>Customer Service</h3>
            <ul>
                <li><a href="<?php echo getBaseUrl(); ?>pages/contact.php">Contact Us</a></li>
            </ul>
        </div>
        <div class="footer-section">
            <h3>My Account</h3>
            <ul>
                <li><a href="<?php echo getBaseUrl(); ?>pages/account.php">Account Settings</a></li>
                <li><a href="<?php echo getBaseUrl(); ?>pages/orders.php">Order History</a></li>
                <li><a href="<?php echo getBaseUrl(); ?>pages/wishlist.php">Wishlist</a></li>
            </ul>
        </div>
        <div class="footer-section">
            <h3>Connect With Us</h3>
            <div class="social-icons">
                <a href="https://www.facebook.com/login/"><i class="fab fa-facebook"></i></a>
                <a href="https://twitter.com/login"><i class="fab fa-twitter"></i></a>
                <a href="https://www.instagram.com/accounts/login/?hl=en"><i class="fab fa-instagram"></i></a>
                <a href="https://www.pinterest.com/login/"><i class="fab fa-pinterest"></i></a>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> NawLexKen Books. All rights reserved.</p>
    </div>
</footer>
<script src="assets/js/main.js"></script>
<script src="<?php echo getBaseUrl(); ?>assets/js/profile.js"></script>
</body>
</html>