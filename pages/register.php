<?php
session_start();
require_once '../config/database.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect form data
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $house_number = trim($_POST['house_number']);
    $street_name = trim($_POST['street_name']);
    $city = trim($_POST['city']);
    $district = trim($_POST['district']);
    $state = trim($_POST['state']);
    $country = trim($_POST['country']);

    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($first_name) || 
        empty($last_name) || empty($house_number) || empty($street_name) || 
        empty($city) || empty($district) || empty($state) || empty($country)) {
        $error = 'Please fill in all required fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        try {
            // Check if username or email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error = 'Username or email already exists';
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $stmt = $conn->prepare("
                    INSERT INTO users (
                        username, email, password, first_name, last_name,
                        house_number, street_name, city, district, state, country
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $username, $email, $hashed_password, $first_name, $last_name,
                    $house_number, $street_name, $city, $district, $state, $country
                ]);

                $success = 'Registration successful! You can now login.';
                
                // Optional: Automatically log in the user
                $user_id = $conn->lastInsertId();
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['first_name'] = $first_name;
                
                header('Location: ../index.php');
                exit;
            }
        } catch (PDOException $e) {
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
    <title>Register - NawLexKen Books</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .auth-container {
            max-width: 800px;
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

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .form-section h2 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #555;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .password-requirements {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.5rem;
        }

        .password-requirements ul {
            list-style-type: none;
            padding-left: 0;
        }

        .password-requirements li {
            margin-bottom: 0.25rem;
        }

        .password-requirements li.valid {
            color: #2f855a;
        }

        .password-requirements li i {
            margin-right: 0.5rem;
        }

        .submit-section {
            margin-top: 2rem;
            text-align: center;
        }

        .register-btn {
            background: #4CAF50;
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 4px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .register-btn:hover {
            background: #45a049;
        }

        .login-link {
            display: block;
            margin-top: 1rem;
            color: #666;
            text-decoration: none;
        }

        .login-link:hover {
            text-decoration: underline;
        }

        /* Messages */
        .message {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .error-message {
            background: #fee;
            color: #e53e3e;
        }

        .success-message {
            background: #e6ffe6;
            color: #2f855a;
        }
    </style>
</head>
<body>
    <a href="../index.php" class="back-button">
        <i class="fas fa-arrow-left"></i>
        Back to Home
    </a>
    <div class="auth-container">
        <div class="auth-header">
            <h1>Create Account</h1>
            <p>Join NawLexKen Books and start your reading journey</p>
        </div>

        <?php if ($error): ?>
            <div class="message error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="message success-message"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" action="" id="registerForm">
            <div class="form-grid">
                <!-- Account Information -->
                <div class="form-section">
                    <h2>Account Information</h2>
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" required
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" required
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" required
                               onkeyup="checkPassword()">
                        <div class="password-requirements">
                            <ul>
                                <li id="length"><i class="fas fa-circle"></i> At least 8 characters</li>
                                <li id="uppercase"><i class="fas fa-circle"></i> One uppercase letter</li>
                                <li id="lowercase"><i class="fas fa-circle"></i> One lowercase letter</li>
                                <li id="number"><i class="fas fa-circle"></i> One number</li>
                                <li id="special"><i class="fas fa-circle"></i> One special character</li>
                            </ul>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required
                               onkeyup="checkPasswordMatch()">
                    </div>
                </div>

                <!-- Personal Information -->
                <div class="form-section">
                    <h2>Personal Information</h2>
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" required
                               value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" required
                               value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                    </div>
                </div>

                <!-- Address Information -->
                <div class="form-section">
                    <h2>Address Information</h2>
                    <div class="form-group">
                        <label for="house_number">House/Apt Number *</label>
                        <input type="text" id="house_number" name="house_number" required
                               value="<?php echo isset($_POST['house_number']) ? htmlspecialchars($_POST['house_number']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="street_name">Street Name *</label>
                        <input type="text" id="street_name" name="street_name" required
                               value="<?php echo isset($_POST['street_name']) ? htmlspecialchars($_POST['street_name']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="city">City *</label>
                        <input type="text" id="city" name="city" required
                               value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="district">District *</label>
                        <input type="text" id="district" name="district" required
                               value="<?php echo isset($_POST['district']) ? htmlspecialchars($_POST['district']) : ''; ?>">
                    </div>
                </div>

                <div class="form-section">
                    <h2>&nbsp;</h2>
                    <div class="form-group">
                        <label for="state">State/Province *</label>
                        <input type="text" id="state" name="state" required
                               value="<?php echo isset($_POST['state']) ? htmlspecialchars($_POST['state']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="country">Country *</label>
                        <input type="text" id="country" name="country" required
                               value="<?php echo isset($_POST['country']) ? htmlspecialchars($_POST['country']) : ''; ?>">
                    </div>
                </div>
            </div>

            <div class="submit-section">
                <button type="submit" class="register-btn">Create Account</button>
                <a href="login.php" class="login-link">Already have an account? Login</a>
            </div>
        </form>
    </div>

    <script>
    function checkPassword() {
        const password = document.getElementById('password').value;
        
        // Update requirements list
        document.getElementById('length').className = 
            password.length >= 8 ? 'valid' : '';
        document.getElementById('uppercase').className = 
            /[A-Z]/.test(password) ? 'valid' : '';
        document.getElementById('lowercase').className = 
            /[a-z]/.test(password) ? 'valid' : '';
        document.getElementById('number').className = 
            /[0-9]/.test(password) ? 'valid' : '';
        document.getElementById('special').className = 
            /[^A-Za-z0-9]/.test(password) ? 'valid' : '';
            
        // Update icons
        document.querySelectorAll('.password-requirements li').forEach(li => {
            const icon = li.querySelector('i');
            if (li.className === 'valid') {
                icon.className = 'fas fa-check';
                icon.style.color = '#2f855a';
            } else {
                icon.className = 'fas fa-circle';
                icon.style.color = '#666';
            }
        });
    }

    function checkPasswordMatch() {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        if (confirmPassword) {
            const matchMessage = document.getElementById('password-match');
            if (!matchMessage) {
                const div = document.createElement('div');
                div.id = 'password-match';
                div.style.marginTop = '0.5rem';
                div.style.fontSize = '0.9rem';
                document.getElementById('confirm_password').parentNode.appendChild(div);
            }
            
            if (password === confirmPassword) {
                document.getElementById('password-match').textContent = 'Passwords match';
                document.getElementById('password-match').style.color = '#2f855a';
            } else {
                document.getElementById('password-match').textContent = 'Passwords do not match';
                document.getElementById('password-match').style.color = '#e53e3e';
            }
        }
    }

    // Form validation before submit
    document.getElementById('registerForm').onsubmit = function(event) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        if (password !== confirmPassword) {
            event.preventDefault();
            alert('Passwords do not match');
            return false;
        }
        
        if (password.length < 8 || 
            !/[A-Z]/.test(password) || 
            !/[a-z]/.test(password) || 
            !/[0-9]/.test(password) || 
            !/[^A-Za-z0-9]/.test(password)) {
            event.preventDefault();
            alert('Please meet all password requirements');
            return false;
        }
        
        return true;
    };
    </script>
</body>
</html>