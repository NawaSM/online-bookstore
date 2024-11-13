<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/onlinebookstore/config/database.php');

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

try {
    $userId = $_SESSION['user_id'];
    
    // Get current user's password
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = :userId");
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verify current password
    if (!password_verify($data['currentPassword'], $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        exit;
    }
    
    // Update password
    $newPasswordHash = password_hash($data['newPassword'], PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = :password WHERE id = :userId");
    $stmt->bindParam(':password', $newPasswordHash);
    $stmt->bindParam(':userId', $userId);
    $stmt->execute();
    
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    error_log('Database Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while updating password']);
}
?>