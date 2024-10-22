<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

error_log('Session data: ' . print_r($_SESSION, true));
error_log('POST data: ' . print_r($_POST, true));

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
            addToCart($pdo, $user_id, $_POST['book_id'], $_POST['quantity']);
            break;
        case 'update':
            updateCartQuantity($pdo, $user_id, $_POST['book_id'], $_POST['quantity']);
            break;
        case 'remove':
            removeFromCart($pdo, $user_id, $_POST['book_id']);
            break;
        case 'get':
            getCart($pdo, $user_id);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}

function addToCart($pdo, $user_id, $book_id, $quantity) {
    try {
        $stmt = $pdo->prepare("INSERT INTO cart_items (user_id, book_id, quantity) VALUES (?, ?, ?) 
                               ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)");
        $stmt->execute([$user_id, $book_id, $quantity]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function updateCartQuantity($pdo, $user_id, $book_id, $quantity) {
    try {
        if ($quantity > 0) {
            $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE user_id = ? AND book_id = ?");
            $stmt->execute([$quantity, $user_id, $book_id]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ? AND book_id = ?");
            $stmt->execute([$user_id, $book_id]);
        }
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function removeFromCart($pdo, $user_id, $book_id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ? AND book_id = ?");
        $stmt->execute([$user_id, $book_id]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function getCart($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT c.book_id, c.quantity, b.book_name, b.price 
                               FROM cart_items c 
                               JOIN inventory b ON c.book_id = b.id 
                               WHERE c.user_id = ?");
        $stmt->execute([$user_id]);
        $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response = [
            'items' => $cart_items,
            'applied_discount' => $_SESSION['applied_discount'] ?? 0,
            'applied_promo_code' => $_SESSION['applied_promo_code'] ?? ''
        ];
        
        echo json_encode($response);
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}