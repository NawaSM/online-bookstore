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