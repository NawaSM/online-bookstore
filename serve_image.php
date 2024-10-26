<?php
session_start();
require_once '../includes/db_connect.php';

if (isset($_GET['type']) && isset($_GET['id'])) {
    $type = $_GET['type'];
    $id = intval($_GET['id']);
    
    try {
        if ($type === 'banner') {
            $stmt = $pdo->prepare("SELECT image_data, image_type FROM banner_ads WHERE id = ?");
        } else {
            die('Invalid type');
        }
        
        $stmt->execute([$id]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($image && $image['image_data']) {
            header("Content-Type: " . $image['image_type']);
            echo $image['image_data'];
            exit;
        }
    } catch (PDOException $e) {
        die('Database error');
    }
}

// Return a default image or 404
header("HTTP/1.0 404 Not Found");