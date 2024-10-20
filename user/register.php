<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$error = '';
$success = '';

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
    <title>User Registration</title>
    <link rel="stylesheet" href="../css/user.css">
</head>
<body>
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
</body>
</html>