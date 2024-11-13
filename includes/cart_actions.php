<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$userId = $_SESSION['user_id'];

switch ($action) {
    case 'add':
        addToCart($conn, $userId, $data['book_id'], $data['quantity'] ?? 1);
        break;
        
    case 'update':
        updateCart($conn, $userId, $data['book_id'], $data['quantity']);
        break;
        
    case 'remove':
        removeFromCart($conn, $userId, $data['book_id']);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
}



function addToCart($conn, $userId, $bookId, $quantity) {
    try {
        // Check if book exists and has enough stock
        $stmt = $conn->prepare("SELECT quantity, price FROM inventory WHERE id = ?");
        $stmt->execute([$bookId]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$book) {
            echo json_encode(['success' => false, 'message' => 'Book not found']);
            return;
        }
        
        if ($book['quantity'] < $quantity) {
            echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
            return;
        }
        
        // Check if item already exists in cart
        $stmt = $conn->prepare("SELECT id, quantity FROM cart_items WHERE user_id = ? AND book_id = ?");
        $stmt->execute([$userId, $bookId]);
        $cartItem = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cartItem) {
            // Update existing cart item
            $newQuantity = $cartItem['quantity'] + $quantity;
            if ($newQuantity > $book['quantity']) {
                echo json_encode(['success' => false, 'message' => 'Cannot add more items than available']);
                return;
            }
            
            $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
            $stmt->execute([$newQuantity, $cartItem['id']]);
        } else {
            // Add new cart item
            $stmt = $conn->prepare("INSERT INTO cart_items (user_id, book_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $bookId, $quantity]);
        }
        
        // Get updated cart count
        $stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart_items WHERE user_id = ?");
        $stmt->execute([$userId]);
        $cartCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        
        $_SESSION['cart_count'] = $cartCount;
        
        echo json_encode([
            'success' => true,
            'message' => 'Item added to cart successfully',
            'cart_count' => $cartCount
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function updateCart($conn, $userId, $bookId, $quantity) {
    try {
        // Check if book exists and has enough stock
        $stmt = $conn->prepare("SELECT quantity FROM inventory WHERE id = ?");
        $stmt->execute([$bookId]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$book) {
            echo json_encode(['success' => false, 'message' => 'Book not found']);
            return;
        }
        
        if ($book['quantity'] < $quantity) {
            echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
            return;
        }
        
        // Update cart item
        $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE user_id = ? AND book_id = ?");
        $stmt->execute([$quantity, $userId, $bookId]);
        
        // Get updated cart total
        $stmt = $conn->prepare("
            SELECT SUM(c.quantity * i.price) as total 
            FROM cart_items c 
            JOIN inventory i ON c.book_id = i.id 
            WHERE c.user_id = ?
        ");
        $stmt->execute([$userId]);
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        // Update cart count in session
        $stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart_items WHERE user_id = ?");
        $stmt->execute([$userId]);
        $cartCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        $_SESSION['cart_count'] = $cartCount;
        
        echo json_encode([
            'success' => true,
            'message' => 'Cart updated successfully',
            'total' => number_format($total, 2),
            'cart_count' => $cartCount
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function removeFromCart($conn, $userId, $bookId) {
    try {
        $stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ? AND book_id = ?");
        $stmt->execute([$userId, $bookId]);
        
        // Get updated cart total
        $stmt = $conn->prepare("
            SELECT SUM(c.quantity * i.price) as total 
            FROM cart_items c 
            JOIN inventory i ON c.book_id = i.id 
            WHERE c.user_id = ?
        ");
        $stmt->execute([$userId]);
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        // Update cart count in session
        $stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart_items WHERE user_id = ?");
        $stmt->execute([$userId]);
        $cartCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        $_SESSION['cart_count'] = $cartCount;
        
        echo json_encode([
            'success' => true,
            'message' => 'Item removed from cart',
            'total' => number_format($total, 2),
            'cart_count' => $cartCount
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
?>