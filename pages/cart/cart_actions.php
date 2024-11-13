<?php
ob_start(); // Start output buffering

session_start();
require_once '../../config/database.php';  
require_once '../../includes/functions.php';

header('Content-Type: application/json');

// Helper functions
function getCartItems($conn, $user_id) {
    $stmt = $conn->prepare("
        SELECT ci.*, i.book_name, i.price, i.special_price, i.quantity as available_quantity, ci.remarks
        FROM cart_items ci
        JOIN inventory i ON ci.book_id = i.id
        WHERE ci.user_id = ?
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCartTotal($cart_items) {
    $total = 0;
    foreach ($cart_items as $item) {
        $price = $item['special_price'] ?? $item['price'];
        $total += $price * $item['quantity'];
    }
    return $total;
}

function getCartCount($conn, $user_id) {
    $stmt = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) as count FROM cart_items WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

// Verify request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Check login status
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'redirect' => true,
        'message' => 'Please login first'
    ]);
    exit;
}

$action = $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'];

try {
    switch ($action) {
        case 'add':
            $book_id = intval($_POST['book_id']);
            $quantity = intval($_POST['quantity'] ?? 1);

            // Validate book existence and stock
            $stmt = $conn->prepare("SELECT quantity FROM inventory WHERE id = ?");
            $stmt->execute([$book_id]);
            $available = $stmt->fetchColumn();

            if ($available === false) {
                throw new Exception("Book not found");
            }

            if ($available < $quantity) {
                throw new Exception("Not enough stock available");
            }

            // Check existing cart item
            $stmt = $conn->prepare("SELECT quantity FROM cart_items WHERE user_id = ? AND book_id = ?");
            $stmt->execute([$user_id, $book_id]);
            $current_quantity = $stmt->fetchColumn();

            if ($current_quantity !== false) {
                // Update existing cart item
                $new_quantity = $current_quantity + $quantity;
                if ($new_quantity > $available) {
                    throw new Exception("Cannot exceed available stock");
                }
                
                $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE user_id = ? AND book_id = ?");
                $stmt->execute([$new_quantity, $user_id, $book_id]);
            } else {
                // Add new cart item
                $stmt = $conn->prepare("INSERT INTO cart_items (user_id, book_id, quantity) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $book_id, $quantity]);
            }

            // Get updated cart info
            $cart_count = getCartCount($conn, $user_id);
            $cart_items = getCartItems($conn, $user_id);

            echo json_encode([
                'success' => true,
                'cart_count' => $cart_count,
                'items' => $cart_items,
                'total' => getCartTotal($cart_items),
                'message' => 'Item added to cart successfully'
            ]);
            break;

        case 'update':
            $book_id = intval($_POST['book_id']);
            $quantity = intval($_POST['quantity']);

            // Validate stock
            $stmt = $conn->prepare("SELECT quantity FROM inventory WHERE id = ?");
            $stmt->execute([$book_id]);
            $available = $stmt->fetchColumn();

            if ($quantity > $available) {
                throw new Exception("Not enough stock available");
            }

            if ($quantity > 0) {
                $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE user_id = ? AND book_id = ?");
                $stmt->execute([$quantity, $user_id, $book_id]);
            } else {
                $stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ? AND book_id = ?");
                $stmt->execute([$user_id, $book_id]);
            }

            $cart_count = getCartCount($conn, $user_id);
            $cart_items = getCartItems($conn, $user_id);

            echo json_encode([
                'success' => true,
                'cart_count' => $cart_count,
                'items' => $cart_items,
                'total' => getCartTotal($cart_items),
                'message' => 'Cart updated successfully'
            ]);
            break;

        case 'remove':
            $book_id = intval($_POST['book_id']);
            $stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ? AND book_id = ?");
            $stmt->execute([$user_id, $book_id]);

            $cart_count = getCartCount($conn, $user_id);
            $cart_items = getCartItems($conn, $user_id);

            echo json_encode([
                'success' => true,
                'cart_count' => $cart_count,
                'items' => $cart_items,
                'total' => getCartTotal($cart_items),
                'message' => 'Item removed from cart'
            ]);
            break;

        case 'clear':
            $stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            echo json_encode([
                'success' => true,
                'cart_count' => 0,
                'items' => [],
                'total' => 0,
                'message' => 'Cart cleared'
            ]);
            break;

        case 'save_remarks':
            $remarks = sanitize_input($_POST['remarks']);
            $stmt = $conn->prepare("UPDATE cart_items SET remarks = ? WHERE user_id = ?");
            $stmt->execute([$remarks, $user_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Remarks saved successfully'
            ]);
            break;

        case 'apply_promo':
            $code = sanitize_input($_POST['code']);
            $subtotal = floatval($_POST['subtotal']);
            
            try {
                $stmt = $conn->prepare("SELECT * FROM promo_codes WHERE code = ? AND is_active = 1");
                $stmt->execute([$code]);
                $promo = $stmt->fetch();
        
                if ($promo && $subtotal >= $promo['min_purchase']) {
                    $discount = ($promo['discount_type'] == 'percentage') 
                        ? $subtotal * ($promo['discount_value'] / 100) 
                        : $promo['discount_value'];
                    
                    $_SESSION['applied_discount'] = $discount;
                    $_SESSION['applied_promo_code'] = $code;
                    
                    // Get updated cart data
                    $cart_items = getCartItems($conn, $user_id);
                    
                    echo json_encode([
                        'success' => true,
                        'items' => $cart_items,
                        'total' => getCartTotal($cart_items),
                        'discount' => $discount,
                        'message' => 'Promo code applied successfully'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Invalid or expired promo code'
                    ]);
                }
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error processing promo code'
                ]);
            }
            break;

        case 'get':
            $cart_items = getCartItems($conn, $user_id);
            $cart_count = getCartCount($conn, $user_id);
            
            echo json_encode([
                'success' => true,
                'items' => $cart_items,
                'total' => getCartTotal($cart_items),
                'cart_count' => $cart_count
            ]);
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

ob_end_flush();