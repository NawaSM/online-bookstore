<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/onlinebookstore/config/database.php');

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $userId = $_SESSION['user_id'];
    
    // Update user details
    $stmt = $conn->prepare("
        UPDATE users 
        SET first_name = :firstName,
            last_name = :lastName,
            house_number = :houseNumber,
            street_name = :streetName,
            city = :city,
            district = :district,
            state = :state,
            country = :country
        WHERE id = :userId
    ");

    $stmt->bindParam(':firstName', $_POST['firstName']);
    $stmt->bindParam(':lastName', $_POST['lastName']);
    $stmt->bindParam(':houseNumber', $_POST['houseNumber']);
    $stmt->bindParam(':streetName', $_POST['streetName']);
    $stmt->bindParam(':city', $_POST['city']);
    $stmt->bindParam(':district', $_POST['district']);
    $stmt->bindParam(':state', $_POST['state']);
    $stmt->bindParam(':country', $_POST['country']);
    $stmt->bindParam(':userId', $userId);

    $stmt->execute();
    
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    error_log('Database Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while updating profile']);
}
?>