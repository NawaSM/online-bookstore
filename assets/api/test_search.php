<?php
require_once('../../config/database.php');

try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM inventory");
    $result = $stmt->fetch();
    echo "Number of books in inventory: " . $result['count'];
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}