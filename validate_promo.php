<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['valid' => false, 'message' => 'Invalid request method.']);
    exit;
}

$code = sanitize_input($_POST['code']);
$total = floatval($_POST['total']);

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['valid' => false, 'message' => 'User must be logged in to use promo codes.']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Fetch promo code details
    $stmt = $pdo->prepare("SELECT * FROM promo_codes WHERE code = ? AND is_active = 1 AND start_date <= CURDATE() AND end_date >= CURDATE()");
    $stmt->execute([$code]);
    $promo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$promo) {
        echo json_encode(['valid' => false, 'message' => 'Invalid or expired promo code.']);
        exit;
    }

    // Check minimum purchase requirement
    if ($promo['min_purchase'] > $total) {
        echo json_encode(['valid' => false, 'message' => "Minimum purchase of $" . number_format($promo['min_purchase'], 2) . " required."]);
        exit;
    }

    // Check total usage limit
    if ($promo['usage_limit'] !== null && $promo['times_used'] >= $promo['usage_limit']) {
        echo json_encode(['valid' => false, 'message' => 'Promo code total usage limit reached.']);
        exit;
    }

    // Check per-customer usage limit
    $stmt = $pdo->prepare("SELECT times_used FROM promo_code_usage WHERE promo_code_id = ? AND user_id = ?");
    $stmt->execute([$promo['id'], $user_id]);
    $user_usage = $stmt->fetchColumn();

    if ($promo['per_customer_limit'] !== null) {
        if ($user_usage && $user_usage >= $promo['per_customer_limit']) {
            echo json_encode(['valid' => false, 'message' => 'You have reached the usage limit for this promo code.']);
            exit;
        }
    }

    // Calculate discount
    $discount = $promo['discount_type'] == 'percentage' 
        ? $total * ($promo['discount_value'] / 100) 
        : min($promo['discount_value'], $total); // Ensure fixed discount doesn't exceed total

    // Update usage counts
    $pdo->beginTransaction();
    try {
        // Update total usage
        $stmt = $pdo->prepare("UPDATE promo_codes SET times_used = times_used + 1 WHERE id = ?");
        $stmt->execute([$promo['id']]);

        // Update or insert per-customer usage
        $stmt = $pdo->prepare("INSERT INTO promo_code_usage (promo_code_id, user_id, times_used) 
                               VALUES (?, ?, 1) 
                               ON DUPLICATE KEY UPDATE times_used = times_used + 1");
        $stmt->execute([$promo['id'], $user_id]);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['valid' => false, 'message' => 'An error occurred while processing the promo code.']);
        exit;
    }

    echo json_encode(['valid' => true, 'discount' => $discount, 'message' => 'Promo code applied successfully.']);

} catch (PDOException $e) {
    echo json_encode(['valid' => false, 'message' => 'An error occurred while validating the promo code.']);
}