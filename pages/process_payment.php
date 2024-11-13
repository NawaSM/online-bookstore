<?php
session_start();
require_once '../config/database.php';
require_once '../config/stripe.php';
require_once '../lib/stripe-php/init.php';
require_once '../config/currency.php';

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

// Handle AJAX request for payment intent
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && !empty(file_get_contents('php://input'))) {
    header('Content-Type: application/json');
    
    try {
        $jsonStr = file_get_contents('php://input');
        $jsonObj = json_decode($jsonStr);

        // Fetch cart items and calculate total in MYR (reusing your existing code)
        $stmt = $conn->prepare("
            SELECT 
                c.quantity,
                i.id as book_id,
                i.price,
                i.is_special,
                i.special_price
            FROM cart_items c
            JOIN inventory i ON c.book_id = i.id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate total amount in MYR (your existing calculation)
        $subtotal = 0;
        $shipping_fee = 10.00;

        foreach ($cartItems as $item) {
            $price = $item['is_special'] && $item['special_price'] 
                ? $item['special_price']
                : $item['price'];
            $subtotal += $price * $item['quantity'];
        }

        $discount = isset($_SESSION['promo_discount']) ? $_SESSION['promo_discount'] : 0;
        $total_amount = $subtotal + $shipping_fee - $discount;

        // Start transaction
        $conn->beginTransaction();

        try {
            // Create order record (using your existing structure)
            $stmt = $conn->prepare("
                INSERT INTO orders (
                    user_id, 
                    total_amount, 
                    discount_amount,
                    status,
                    shipping_name,
                    shipping_address,
                    shipping_city,
                    shipping_state,
                    shipping_country,
                    shipping_zip,
                    created_at
                ) VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $total_amount,
                $discount,
                $jsonObj->shipping->name,
                $jsonObj->shipping->address->line1,
                $jsonObj->shipping->address->city,
                $jsonObj->shipping->address->state,
                $jsonObj->shipping->address->country,
                $jsonObj->shipping->address->postal_code
            ]);
            
            $orderId = $conn->lastInsertId();

            // Add order items (your existing code)
            $stmt = $conn->prepare("
                INSERT INTO order_items (
                    order_id, 
                    inventory_id, 
                    quantity, 
                    price
                ) VALUES (?, ?, ?, ?)
            ");

            foreach ($cartItems as $item) {
                $price = $item['is_special'] && $item['special_price'] 
                    ? convertToMYR($item['special_price']) 
                    : convertToMYR($item['price']);
                    
                $stmt->execute([
                    $orderId,
                    $item['book_id'],
                    $item['quantity'],
                    $price
                ]);
            }

            // Store payment and shipping details (your existing code)
            $stmt = $conn->prepare("
                INSERT INTO order_remarks (
                    order_id, 
                    remarks
                ) VALUES (?, ?)
            ");
            
            $remarks = json_encode([
                'payment_method' => 'stripe',
                'currency' => CURRENCY_CODE,
                'shipping_fee' => $shipping_fee,
                'shipping_details' => [
                    'name' => $jsonObj->shipping->name,
                    'address' => $jsonObj->shipping->address->line1,
                    'city' => $jsonObj->shipping->address->city,
                    'state' => $jsonObj->shipping->address->state,
                    'country' => $jsonObj->shipping->address->country,
                    'zip' => $jsonObj->shipping->address->postal_code
                ]
            ]);
            
            $stmt->execute([$orderId, $remarks]);

            // Create the payment intent
            $payment_intent = \Stripe\PaymentIntent::create([
                'amount' => round($total_amount * 100),
                'currency' => 'myr',
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
                'metadata' => [
                    'order_id' => $orderId,
                    'user_id' => $_SESSION['user_id'],
                    'shipping_fee' => $shipping_fee,
                    'discount' => $discount
                ],
                'shipping' => $jsonObj->shipping,
                'description' => 'Book purchase from NawLexKen Books'
            ]);

            $conn->commit();

            echo json_encode([
                'clientSecret' => $payment_intent->client_secret,
                'orderId' => $orderId
            ]);

        } catch (Exception $e) {
            $conn->rollBack();
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    } catch (Error $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

// Handle regular form submissions (your existing PayPal code)
try {
    if (isset($_POST['payment_method']) && $_POST['payment_method'] === 'paypal') {
        // Your existing PayPal processing code
    } else {
        throw new Exception('Invalid payment method');
    }

} catch(\Stripe\Exception\CardException $e) {
    $_SESSION['payment_error'] = $e->getMessage();
    header('Location: checkout.php');
    exit;
} catch (Exception $e) {
    $_SESSION['payment_error'] = 'An error occurred during payment processing.';
    header('Location: checkout.php');
    exit;
}
?>