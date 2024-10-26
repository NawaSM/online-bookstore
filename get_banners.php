<?php
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT * FROM banner_ads 
                         WHERE is_active = 1 
                         ORDER BY display_order ASC");
    $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($banners);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}