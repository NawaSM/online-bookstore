<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$remarks = $_POST['remarks'] ?? '';
$promo_code = $_POST['promo_code'] ?? '';

try {
    $pdo->beginTransaction();

    // Get cart items
    $stmt = $pdo->prepare("SELECT c.book_id, c.quantity, b.price 
                           FROM cart_items c 
                           JOIN inventory b ON c.book_id = b.id 
                           WHERE c.user_id = ?");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cart_items)) {
        throw new Exception('Cart is empty');
    }

    // Calculate total and apply discount
    $total = array_reduce($cart_items, function($sum, $item) {
        return $sum + ($item['price'] * $item['quantity']);
    }, 0);

    $discount = 0;
    if ($promo_code) {
        $stmt = $pdo->prepare("SELECT * FROM promo_codes WHERE code = ? AND is_active = 1");
        $stmt->execute([$promo_code]);
        $promo = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($promo) {
            $discount = ($promo['discount_type'] == 'percentage') 
                ? $total * ($promo['discount_value'] / 100) 
                : $promo['discount_value'];
            $total -= $discount;
        }
    }

    // Create order
    $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, discount_amount, status) VALUES (?, ?, ?, 'pending')");
    $stmt->execute([$user_id, $total, $discount]);
    $order_id = $pdo->lastInsertId();

    // Add order items
    $stmt = $pdo->prepare("INSERT INTO order_items (order_id, book_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($cart_items as $item) {
        $stmt->execute([$order_id, $item['book_id'], $item['quantity'], $item['price']]);
    }

    // Add remarks if any
    if ($remarks) {
        $stmt = $pdo->prepare("INSERT INTO order_remarks (order_id, remarks) VALUES (?, ?)");
        $stmt->execute([$order_id, $remarks]);
    }

    // Clear cart
    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?");
    $stmt->execute([$user_id]);

    $pdo->commit();
    echo json_encode(['success' => true, 'order_id' => $order_id]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}