<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_admin_logged_in()) {
    echo json_encode(['error' => 'Not authorized']);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'No ID provided']);
    exit;
}

$id = intval($_GET['id']);

try {
    $stmt = $pdo->prepare("SELECT * FROM promo_codes WHERE id = ?");
    $stmt->execute([$id]);
    $promo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($promo) {
        echo json_encode($promo);
    } else {
        echo json_encode(['error' => 'Promo code not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}