<?php
session_start();
require_once '../../../config/database.php'; 
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$userId = $_SESSION['user_id'];
$bookId = $data['book_id'] ?? null;

if (!$bookId) {
    echo json_encode(['success' => false, 'message' => 'Invalid book ID']);
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM wishlists WHERE user_id = ? AND book_id = ?");
    $stmt->execute([$userId, $bookId]);

    echo json_encode([
        'success' => true, 
        'message' => 'Removed from wishlist'
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}