<?php
session_start();
require_once '../config/database.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $success = 'You have been successfully logged out.';
}

if (isset($_GET['required']) && $_GET['required'] === 'true') {
    $message = 'Please login to continue with your shopping';
    echo '<div class="info-message">' . htmlspecialchars($message) . '</div>';
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        try {
            $stmt = $conn->prepare("SELECT id, username, password, first_name FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Start a new session and clear any old session data
                session_regenerate_id(true);
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['first_name'] = $user['first_name'];
                
                // Fetch cart count
                $stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart_items WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                $cartCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
                $_SESSION['cart_count'] = $cartCount;
                
                // Get wishlist count
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM wishlists WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                $wishlistCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
                $_SESSION['wishlist_count'] = $wishlistCount;
                
                // Store session timestamp
                $_SESSION['last_activity'] = time();
                
                // Ensure all session data is written
                session_write_close();
                
                // Handle redirect
                $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 
                          (isset($_SESSION['redirect_url']) ? $_SESSION['redirect_url'] : '../index.php');
                
                // Clear redirect URL from session
                unset($_SESSION['redirect_url']);
                
                // Redirect with session status
                header("Location: $redirect");
                exit;
            } else {
                $error = 'Invalid username or password';
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'Database error. Please try again later.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - NawLexKen Books</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .auth-container {
            max-width: 400px;
            margin: 2rem auto;
            padding: 2rem;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-header h1 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .auth-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            color: #555;
            font-weight: 500;
        }

        .form-group input {
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .password-field {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
        }

        .auth-btn {
            background: #4CAF50;
            color: white;
            padding: 0.75rem;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .auth-btn:hover {
            background: #45a049;
        }

        .auth-links {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
            font-size: 0.9rem;
        }

        .auth-links a {
            color: #666;
            text-decoration: none;
        }

        .auth-links a:hover {
            text-decoration: underline;
        }

        .error-message {
            background: #fee;
            color: #e53e3e;
            padding: 0.75rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .success-message {
            background: #e6ffe6;
            color: #2f855a;
            padding: 0.75rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .social-login {
            margin-top: 2rem;
            text-align: center;
        }

        .social-login-heading {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .social-login-heading::before,
        .social-login-heading::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #ddd;
        }

        .social-login-heading span {
            padding: 0 1rem;
            color: #666;
            font-size: 0.9rem;
        }

        .social-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .social-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .social-btn:hover {
            background: #f9f9f9;
        }

        .social-btn i {
            font-size: 1.2rem;
        }

        .social-btn.google i {
            color: #DB4437;
        }

        .social-btn.facebook i {
            color: #4267B2;
        }
        
        .back-button {
            position: absolute;
            top: 2rem;
            left: 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            color: #333;
            text-decoration: none;
            font-size: 1rem;
            transition: all 0.2s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .back-button:hover {
            background: #f8f9fa;
            box-shadow: 0 3px 8px rgba(0,0,0,0.15);
        }

        .back-button i {
            font-size: 1.1rem;
        }
        
        .info-message {
            background: #e3f2fd;
            color: #1565c0;
            padding: 0.75rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            text-align: center;
        }

        /* Add this to ensure proper button positioning */
        body {
            position: relative;
            min-height: 100vh;
            padding-top: 1rem;
        }

        @media (max-width: 768px) {
            .back-button {
                top: 1rem;
                left: 1rem;
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body data-authenticated="<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>">    <div class="auth-container">
        <a href="../index.php" class="back-button">
            <i class="fas fa-arrow-left"></i>
            Back to Home
        </a> 
        <div class="auth-header">
            <h1>Login</h1>
            <p>Welcome back to NawLexKen Books</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>

        <form class="auth-form" method="POST" action="">
            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" id="username" name="username" required 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-field">
                    <input type="password" id="password" name="password" required>
                    <button type="button" class="password-toggle" onclick="togglePassword('password')">
                        <i class="far fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="auth-btn">Login</button>

            <div class="auth-links">
                <a href="register.php">Create Account</a>
                <a href="forgot-password.php">Forgot Password?</a>
            </div>
        </form>
    </div>

    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function socialLogin(provider) {
            // Implement social login functionality
            console.log(`Logging in with ${provider}`);
        }
    </script>
</body>

</html>