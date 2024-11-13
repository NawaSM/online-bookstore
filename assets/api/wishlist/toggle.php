<?php
session_start();
require_once '../../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Single session check
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$userId = $_SESSION['user_id'];
$bookId = $data['book_id'] ?? null;

if (!$bookId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid book ID']);
    exit;
}

try {
    $conn->beginTransaction();

    // Check if in wishlist
    $stmt = $conn->prepare("SELECT id FROM wishlists WHERE user_id = ? AND book_id = ?");
    $stmt->execute([$userId, $bookId]);
    
     if ($stmt->fetch()) {
        // Remove from wishlist
        $stmt = $conn->prepare("DELETE FROM wishlists WHERE user_id = ? AND book_id = ?");
        $stmt->execute([$userId, $bookId]);
        $action = 'removed from';
    } else {
        // Add to wishlist
        $stmt = $conn->prepare("INSERT INTO wishlists (user_id, book_id) VALUES (?, ?)");
        $stmt->execute([$userId, $bookId]);
        $action = 'added to';
    }
    // Get updated count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM wishlists WHERE user_id = ?");
    $stmt->execute([$userId]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Update session count
    $_SESSION['wishlist_count'] = $count;

    $conn->commit();

    echo json_encode([
        'success' => true, 
        'message' => "Book $action wishlist",
        'action' => $action,
        'count' => $count
    ]);

}  catch (PDOException $e) {
    $conn->rollBack();
    error_log('Database error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}