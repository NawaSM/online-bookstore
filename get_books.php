<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'includes/db_connect.php';

function get_books($filter = null) {
    global $pdo;
    
    try {
        $sql = "SELECT i.*, c.name AS category_name 
                FROM inventory i 
                LEFT JOIN categories c ON i.category = c.id";
                
        if ($filter === 'bestseller') {
            $sql .= " WHERE i.status = 'bestseller'";
        } else if ($filter === 'new') {
            $sql .= " WHERE i.status = 'new'";
        } else if ($filter === 'coming_soon') {
            $sql .= " WHERE i.status = 'coming_soon'";
        } else if ($filter === 'special') {
            $sql .= " WHERE i.is_special = 1";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format the data for frontend use
        foreach ($books as &$book) {
            // Ensure all values are properly encoded
            $book = array_map(function($value) {
                return is_string($value) ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : $value;
            }, $book);
            
            $book['genres'] = !empty($book['genres']) ? explode(',', $book['genres']) : [];
            $book['image_url'] = "serve_image.php?type=book&id=" . $book['id'];
            $book['price_formatted'] = "RM" . number_format($book['price'], 2);
            if ($book['is_special'] && $book['special_price']) {
                $book['special_price_formatted'] = "RM" . number_format($book['special_price'], 2);
            }
        }
        
        return [
            'success' => true,
            'data' => $books,
            'filter' => $filter,
            'count' => count($books)
        ];
        
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'General error: ' . $e->getMessage()];
    }
}

// Handle the API request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    
    $filter = isset($_GET['filter']) ? $_GET['filter'] : null;
    $result = get_books($filter);
    
    // Ensure proper JSON encoding
    if (json_encode($result) === false) {
        $result = [
            'success' => false,
            'error' => 'JSON encoding error: ' . json_last_error_msg()
        ];
    }
    
    echo json_encode($result);
    exit;
}
?>