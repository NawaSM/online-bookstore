<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$promo_code = $_POST['code'] ?? '';
$subtotal = floatval($_POST['subtotal'] ?? 0);

try {
    $stmt = $pdo->prepare("SELECT * FROM promo_codes WHERE code = ? AND is_active = 1 AND start_date <= CURDATE() AND end_date >= CURDATE()");
    $stmt->execute([$promo_code]);
    $promo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($promo) {
        if ($subtotal >= $promo['min_purchase']) {
            $discount = ($promo['discount_type'] == 'percentage') 
                ? $subtotal * ($promo['discount_value'] / 100) 
                : $promo['discount_value'];
            
            $_SESSION['applied_discount'] = $discount;
            $_SESSION['applied_promo_code'] = $promo_code;
            
            echo json_encode(['valid' => true, 'discount' => $discount]);
        } else {
            echo json_encode(['valid' => false, 'message' => 'Minimum purchase amount not met']);
        }
    } else {
        echo json_encode(['valid' => false, 'message' => 'Invalid or expired promo code']);
    }
} catch (PDOException $e) {
    echo json_encode(['valid' => false, 'message' => 'Error processing promo code']);
}