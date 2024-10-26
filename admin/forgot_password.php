<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    
    // Check if email exists
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin) {
        // Generate token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Save token to database
        $stmt = $pdo->prepare("UPDATE admins SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
        $stmt->execute([$token, $expires, $admin['id']]);

        // Send email with reset link //PLACEHOLDER
        $reset_link = "http://yourdomain.com/admin/reset_password.php?token=$token";
        $to = $email;
        $subject = "Password Reset Request";
        $message = "Click the following link to reset your password: $reset_link";
        $headers = "From: noreply@yourdomain.com";

        if (mail($to, $subject, $message, $headers)) {
            $success = "Password reset link has been sent to your email.";
        } else {
            $error = "Failed to send password reset email. Please try again.";
        }
    } else {
        $error = "No account found with that email address.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="../css/admin-panel.css">
</head>
<body>
    <div class="admin-container">
        <div class="main-content">
            <h1>Forgot Password</h1>
            
            <?php if ($error): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php endif; ?>
            <?php if ($success): ?>
                <p class="success"><?php echo $success; ?></p>
            <?php endif; ?>

            <form action="forgot_password.php" method="post">
                <input type="email" name="email" placeholder="Enter your email" required>
                <button type="submit">Reset Password</button>
            </form>
            
            <p><a href="../admin/login.php">Back to Login</a></p>
        </div>
    </div>
</body>
</html>