<?php
session_start();
require_once '../config/database.php';
require_once '../config/currency.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: checkout.php');
    exit;
}

try {
    $conn->beginTransaction();

    // Get cart items with price conversion
    $stmt = $conn->prepare("
        SELECT 
            c.quantity,
            i.id as book_id,
            i.price,
            i.is_special,
            i.special_price,
            i.quantity as stock
        FROM cart_items c
        JOIN inventory i ON c.book_id = i.id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $subtotal = 0;
    $shipping_fee = 10.00; // Direct MYR amount

    foreach ($cartItems as $item) {
        $price = $item['is_special'] && $item['special_price'] 
            ? $item['special_price'] 
            : $item['price'];
        $subtotal += $price * $item['quantity'];
    }

    $discountAmount = isset($_SESSION['promo_discount']) ? $_SESSION['promo_discount'] : 0;
    $totalAmount = $subtotal + $shipping_fee - $discountAmount;

    // Check if this is a card payment
    if (isset($_POST['payment_method']) && $_POST['payment_method'] === 'credit_card') {
        // Store order details in session for Stripe processing
        $_SESSION['order_details'] = [
            'cart_items' => $cartItems,
            'total_amount' => $totalAmount,
            'discount_amount' => $discountAmount,
            'shipping_fee' => $shipping_fee,
            'currency' => CURRENCY_CODE,
            'shipping_details' => [
                'name' => $_POST['shipping_name'],
                'email' => $_POST['shipping_email'],
                'phone' => $_POST['shipping_phone'],
                'address' => $_POST['shipping_address'],
                'city' => $_POST['shipping_city'],
                'state' => $_POST['shipping_state'],
                'country' => $_POST['shipping_country'],
                'zip' => $_POST['shipping_zip']
            ]
        ];
        header('Location: process_payment.php');
        exit;
    }

    // For non-card payments (e.g., PayPal), process the order directly
    // Create order
    $stmt = $conn->prepare("
        INSERT INTO orders (
            user_id,
            total_amount,
            discount_amount,
            status,
            created_at
        ) VALUES (?, ?, ?, 'pending', CURRENT_TIMESTAMP)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $totalAmount,
        $discountAmount
    ]);
    $orderId = $conn->lastInsertId();

    // Create order items and update inventory
    foreach ($cartItems as $item) {
        // Check stock availability
        if ($item['quantity'] > $item['stock']) {
            throw new Exception("Not enough stock for one or more items");
        }

        // Calculate price in MYR
        $price = $item['is_special'] && $item['special_price'] 
            ? convertToMYR($item['special_price']) 
            : convertToMYR($item['price']);

        // Insert order item
        $stmt = $conn->prepare("
            INSERT INTO order_items (
                order_id,
                inventory_id,
                quantity,
                price
            ) VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $orderId,
            $item['book_id'],
            $item['quantity'],
            $price
        ]);

        // Update inventory
        $stmt = $conn->prepare("
            UPDATE inventory 
            SET quantity = quantity - ? 
            WHERE id = ?
        ");
        $stmt->execute([
            $item['quantity'],
            $item['book_id']
        ]);

        // Check if stock is low after update
        $stmt = $conn->prepare("
            SELECT quantity 
            FROM inventory 
            WHERE id = ? AND quantity <= 5
        ");
        $stmt->execute([$item['book_id']]);
        if ($stmt->fetch()) {
            // Create low stock notification
            $stmt = $conn->prepare("
                INSERT INTO notifications (
                    message,
                    type,
                    created_at
                ) VALUES (
                    CONCAT('Low stock alert for book ID: ', ?),
                    'low_stock',
                    CURRENT_TIMESTAMP
                )
            ");
            $stmt->execute([$item['book_id']]);
        }
    }

    // Store shipping and payment information
    $stmt = $conn->prepare("
        INSERT INTO order_remarks (
            order_id,
            remarks
        ) VALUES (?, ?)
    ");
    $remarks = json_encode([
        'shipping_name' => $_POST['shipping_name'],
        'shipping_email' => $_POST['shipping_email'],
        'shipping_phone' => $_POST['shipping_phone'],
        'shipping_address' => $_POST['shipping_address'],
        'shipping_city' => $_POST['shipping_city'],
        'shipping_state' => $_POST['shipping_state'],
        'shipping_country' => $_POST['shipping_country'],
        'shipping_zip' => $_POST['shipping_zip'],
        'payment_method' => $_POST['payment_method'],
        'currency' => CURRENCY_CODE,
        'shipping_fee' => $shipping_fee
    ]);
    $stmt->execute([$orderId, $remarks]);

    // Clear cart
    $stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);

    // Clear session variables
    unset($_SESSION['cart_count']);
    unset($_SESSION['promo_discount']);

    // Create order notification
    $stmt = $conn->prepare("
        INSERT INTO notifications (
            message,
            type,
            created_at
        ) VALUES (
            CONCAT('New order received: Order ID ', ?),
            'new_order',
            CURRENT_TIMESTAMP
        )
    ");
    $stmt->execute([$orderId]);

    // Commit transaction
    $conn->commit();

    // Store order ID in session for confirmation page
    $_SESSION['last_order_id'] = $orderId;
    $_SESSION['order_success'] = true;

    // Redirect to order confirmation
    header('Location: order_confirmation.php');
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollBack();
    
    // Store error message in session
    $_SESSION['checkout_error'] = $e->getMessage();
    
    // Redirect back to checkout
    header('Location: checkout.php');
    exit;
}
?>