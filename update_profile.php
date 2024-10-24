<?php
// Assuming you have a database connection named $conn
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $shippingAddress = $_POST['shippingAddress'];
    $dob = $_POST['dob'];
    $mobile = $_POST['mobile'];
    $password = $_POST['password'];

    // Encrypt the password before saving it to the database
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Update the profile in the database (replace with your own table and columns)
    $sql = "UPDATE users SET username = ?, shipping_address = ?, dob = ?, mobile = ?, password = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $username, $shippingAddress, $dob, $mobile, $hashedPassword, $userId);

    if ($stmt->execute()) {
        echo "Profile updated successfully!";
    } else {
        echo "Error updating profile: " . $conn->error;
    }
}
?>
