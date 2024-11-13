<?php
session_start();
require_once '../../../config/database.php';
require_once '../../../pages/cart/cart_actions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$bookId = $input['book_id'] ?? null;
$cartId = $input['cart_id'] ?? null;
$userId = $_SESSION['user_id'];

if (!$bookId || !$cartId) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    $conn->beginTransaction();

    // Delete the cart item
    $stmt = $conn->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ? AND book_id = ?");
    $result = $stmt->execute([$cartId, $userId, $bookId]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Item not found or already removed');
    }

    // Calculate new totals
    $stmt = $conn->prepare("
        SELECT 
            SUM(CASE WHEN i.is_special THEN i.special_price * c.quantity 
                ELSE i.price * c.quantity END) as subtotal
        FROM cart_items c 
        JOIN inventory i ON c.book_id = i.id 
        WHERE c.user_id = ?
    ");
    $stmt->execute([$userId]);
    $subtotal = $stmt->fetchColumn() ?? 0;

    $discount = isset($_SESSION['promo_discount']) ? $_SESSION['promo_discount'] : 0;
    $total = $subtotal - $discount;

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Item removed successfully',
        'totals' => [
            'subtotal' => floatval($subtotal),
            'discount' => floatval($discount),
            'total' => floatval($total)
        ]
    ]);

} catch (Exception $e) {
    $conn->rollBack();
    error_log($e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error removing item', 
        'error' => $e->getMessage()
    ]);
}