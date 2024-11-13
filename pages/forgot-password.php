<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once '../config/database.php';
require_once '../config/sendgrid.php';
require_once '../includes/functions.php';
require_once '../vendor/autoload.php'; 
use SendGrid\Mail\Mail; 

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize_input($_POST['email']);
    
    if (empty($email)) {
        $error = 'Please enter your email address';
    } else {
        try {
            $stmt = $conn->prepare("SELECT id, email, first_name FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Generate token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Save token to database
                $updateStmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
                $updateStmt->execute([$token, $expires, $user['id']]);
                
                // Prepare reset link
                $resetLink = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset-password.php?token=" . $token;
                
                // Prepare email
                $emailBody = "
                    <html>
                    <body>
                        <h2>Password Reset Request</h2>
                        <p>Hi " . htmlspecialchars($user['first_name']) . ",</p>
                        <p>You recently requested to reset your password. Click the link below to reset it:</p>
                        <p><a href='" . $resetLink . "'>" . $resetLink . "</a></p>
                        <p>This link will expire in 1 hour.</p>
                        <p>If you didn't request this, you can safely ignore this email.</p>
                        <br>
                        <p>Best regards,<br>NawLexKen Books Team</p>
                    </body>
                    </html>
                ";
                
                try {
                    $email = new Mail();
                    $email->setFrom("d4rth02@gmail.com", "NawLexKen Books");
                    $email->setSubject("Password Reset Request");
                    $email->addTo($user['email'], $user['first_name']);
                    $email->addContent("text/html", $emailBody);
                    
                    $sendgrid = new \SendGrid(SENDGRID_API_KEY);
                    $response = $sendgrid->send($email);
                    
                    if ($response->statusCode() >= 200 && $response->statusCode() < 300) {
                        $success = 'Password reset instructions have been sent to your email.';
                    } else {
                        error_log('SendGrid Error: ' . $response->body());
                        $success = 'If an account exists with that email, you will receive password reset instructions.';
                    }
                } catch (Exception $e) {
                    error_log('SendGrid Error: ' . $e->getMessage());
                    $success = 'If an account exists with that email, you will receive password reset instructions.';
                }
            } else {
                // Still show success to prevent email enumeration
                $success = 'If an account exists with that email, you will receive password reset instructions.';
            }
        } catch (PDOException $e) {
            error_log('Database Error: ' . $e->getMessage());
            $error = 'An error occurred. Please try again later.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - NawLexKen Books</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <a href="../index.php" class="back-button">
            <i class="fas fa-arrow-left"></i>
            Back to Home
        </a>
        
        <div class="auth-header">
            <h1>Forgot Password</h1>
            <p>Enter your email to reset your password</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>

        <form class="auth-form" method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>

            <button type="submit" class="auth-btn">Send Reset Link</button>

            <div class="auth-links">
                <a href="login.php">Back to Login</a>
            </div>
        </form>
    </div>
</body>
</html>