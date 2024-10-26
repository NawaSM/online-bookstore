<?php
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function is_admin_logged_in() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_role']);
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function getCategoryName($pdo, $categoryId) {
    $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
    $stmt->execute([$categoryId]);
    return $stmt->fetchColumn();
}

function getGenreNames($pdo, $genreIds) {
    $ids = explode(',', $genreIds);
    $stmt = $pdo->prepare("SELECT GROUP_CONCAT(name SEPARATOR ', ') FROM genres WHERE id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")");
    $stmt->execute($ids);
    return $stmt->fetchColumn();
}

function handle_image_upload($pdo, $stmt) {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No image uploaded or upload error');
    }

    $file = $_FILES['image'];
    $allowedTypes = ['image/jpeg', 'image/png'];
    $maxSize = 2 * 1024 * 1024; // 2MB

    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Invalid file type. Only JPG and PNG are allowed.');
    }

    if ($file['size'] > $maxSize) {
        throw new Exception('File size too large. Maximum size is 2MB.');
    }

    // Read image data
    $imageData = file_get_contents($file['tmp_name']);
    $imageType = $file['type'];

    // Bind the image data and type to the prepared statement
    $stmt->bindParam(':image_data', $imageData, PDO::PARAM_LOB);
    $stmt->bindParam(':image_type', $imageType);
}