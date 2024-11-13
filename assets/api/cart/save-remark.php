<?php
session_start();
require_once '../../../config/database.php';
require_once '../../../pages/cart/cart_actions.php';



header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$cartItemId = $data['cart_item_id'] ?? null;
$remark = $data['remark'] ?? '';

if (!$cartItemId) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    // Check if cart item belongs to current user
    $stmt = $conn->prepare("
        SELECT id FROM cart_items 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$cartItemId, $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        throw new Exception('Invalid cart item');
    }

    // Check if remark exists
    $stmt = $conn->prepare("
        SELECT id FROM order_remarks 
        WHERE cart_item_id = ?
    ");
    $stmt->execute([$cartItemId]);
    $existingRemark = $stmt->fetch();

    if ($existingRemark) {
        // Update existing remark
        $stmt = $conn->prepare("
            UPDATE order_remarks 
            SET remarks = ? 
            WHERE cart_item_id = ?
        ");
        $stmt->execute([$remark, $cartItemId]);
    } else {
        // Insert new remark
        $stmt = $conn->prepare("
            INSERT INTO order_remarks (cart_item_id, remarks) 
            VALUES (?, ?)
        ");
        $stmt->execute([$cartItemId, $remark]);
    }

    echo json_encode(['success' => true, 'message' => 'Remark saved successfully']);
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error saving remark']);
}