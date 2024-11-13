<?php
session_start();
require_once '../../../config/database.php'; 

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
    // Check if already in wishlist
    $stmt = $conn->prepare("SELECT id FROM wishlists WHERE user_id = ? AND book_id = ?");
    $stmt->execute([$userId, $bookId]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Book already in wishlist']);
        exit;
    }

    // Add to wishlist
    $stmt = $conn->prepare("INSERT INTO wishlists (user_id, book_id) VALUES (?, ?)");
    $stmt->execute([$userId, $bookId]);

    echo json_encode(['success' => true, 'message' => 'Added to wishlist']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}