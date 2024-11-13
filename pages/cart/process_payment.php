<?php
ob_start();
session_start();
require_once '../../config/database.php';  
require_once '../../includes/functions.php';
require_once '../../vendor/autoload.php';

$stripe_config = require_once '../../config/stripe-config.php';
\Stripe\Stripe::setApiKey($stripe_config['secret_key']);

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }

    $token = $_POST['stripeToken'];
    $amount = $_POST['amount']; // Amount in cents

    // Debug log
    error_log('Processing payment: Amount = ' . $amount . ', Token = ' . $token);


    // Create Stripe charge
    $charge = \Stripe\Charge::create([
        'amount' => $amount,
        'currency' => 'myr',
        'description' => 'Book purchase',
        'source' => $token,
    ]);

    error_log('Stripe charge response: ' . print_r($charge, true));

    // If charge successful, create order
    if ($charge->status === 'succeeded') {
        $conn->beginTransaction();

        // Generate order number
        $order_number = 'ORD-' . date('Y') . '-' . sprintf('%04d', rand(1, 9999));

        // Calculate original subtotal from cart items
        $stmt = $conn->prepare("
            SELECT ci.quantity, 
                   COALESCE(i.special_price, i.price) as effective_price
            FROM cart_items ci
            JOIN inventory i ON ci.book_id = i.id
            WHERE ci.user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $subtotal = 0;
        foreach ($cart_items as $item) {
            $subtotal += $item['effective_price'] * $item['quantity'];
        }

        // Calculate shipping and final total
        $shipping = $subtotal >= 150 ? 0 : 10;
        $discount = $_SESSION['applied_discount'] ?? 0;
        $final_total = $subtotal - $discount + $shipping;

        // Get user's shipping details
        $stmt = $conn->prepare("
            SELECT first_name, last_name, house_number, street_name, city, district, state, country 
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $user_details = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get the remarks from the cart (this goes before the order creation)
        $stmt = $conn->prepare("
            SELECT remarks 
            FROM cart_items 
            WHERE user_id = ? 
            LIMIT 1
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $cart_remarks = $stmt->fetchColumn();

        // Create order with shipping details
        $stmt = $conn->prepare("
            INSERT INTO orders (
                user_id, 
                order_number, 
                total_amount,
                original_subtotal, 
                shipping_fee,
                discount_amount,
                payment_method, 
                payment_status, 
                status,
                shipping_name,
                shipping_house_number,
                shipping_street_name,
                shipping_city,
                shipping_district,
                shipping_state,
                shipping_country,
                remarks
            ) 
            VALUES (?, ?, ?, ?, ?, ?, 'card', 'paid', 'pending', ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_SESSION['user_id'],
            $order_number,
            $final_total,
            $subtotal,
            $shipping,
            $discount,
            $user_details['first_name'] . ' ' . $user_details['last_name'],
            $user_details['house_number'],
            $user_details['street_name'],
            $user_details['city'],
            $user_details['district'],
            $user_details['state'],
            $user_details['country'],
            $cart_remarks 
        ]);
        
        $order_id = $conn->lastInsertId();

        // Move items from cart to order items
        $stmt = $conn->prepare("
            INSERT INTO order_items (order_id, inventory_id, quantity, price)
            SELECT ?, book_id, quantity, 
                COALESCE(
                    (SELECT special_price FROM inventory WHERE id = cart_items.book_id AND special_price IS NOT NULL),
                    (SELECT price FROM inventory WHERE id = cart_items.book_id)
                )
            FROM cart_items 
            WHERE user_id = ?
        ");
        $stmt->execute([$order_id, $_SESSION['user_id']]);

        // Clear cart and applied discounts
        $stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        unset($_SESSION['applied_discount']);
        unset($_SESSION['applied_promo_code']);

        $conn->commit();

        echo json_encode(['success' => true, 'order_id' => $order_id]);
    } else {
        throw new Exception('Payment failed');
    }
} catch (Exception $e) {
    error_log('Payment Error: ' . $e->getMessage());
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}