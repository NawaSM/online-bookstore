<?php
session_start();
require_once '../../../config/database.php';  // Changed from four levels to three
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$userId = $_SESSION['user_id'];
$bookIds = $data['book_ids'] ?? [];

if (empty($bookIds)) {
    echo json_encode(['success' => false, 'message' => 'No books selected']);
    exit;
}

try {
    $conn->beginTransaction();

    foreach ($bookIds as $bookId) {
        // Check if book exists and is in stock
        $stmt = $conn->prepare("SELECT quantity FROM inventory WHERE id = ?");
        $stmt->execute([$bookId]);
        $book = $stmt->fetch();

        if (!$book || $book['quantity'] <= 0) {
            continue; // Skip out of stock items
        }

        // Check if already in cart
        $stmt = $conn->prepare("SELECT quantity FROM cart_items WHERE user_id = ? AND book_id = ?");
        $stmt->execute([$userId, $bookId]);
        $cartItem = $stmt->fetch();

        if ($cartItem) {
            // Update quantity
            $stmt = $conn->prepare("UPDATE cart_items SET quantity = quantity + 1 WHERE user_id = ? AND book_id = ?");
            $stmt->execute([$userId, $bookId]);
        } else {
            // Add new item
            $stmt = $conn->prepare("INSERT INTO cart_items (user_id, book_id, quantity) VALUES (?, ?, 1)");
            $stmt->execute([$userId, $bookId]);
        }

        // Optionally, remove from wishlist after adding to cart
        $stmt = $conn->prepare("DELETE FROM wishlists WHERE user_id = ? AND book_id = ?");
        $stmt->execute([$userId, $bookId]);
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Items added to cart']);
} catch (PDOException $e) {
    $conn->rollBack();
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error adding items to cart']);
}