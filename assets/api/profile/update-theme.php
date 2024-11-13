<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/onlinebookstore/config/database.php');

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$theme = $data['theme'] ?? 'light';

try {
    $stmt = $conn->prepare("UPDATE users SET theme_preference = :theme WHERE id = :userId");
    $stmt->bindParam(':theme', $theme);
    $stmt->bindParam(':userId', $_SESSION['user_id']);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log('Database Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to save theme preference']);
}
?>