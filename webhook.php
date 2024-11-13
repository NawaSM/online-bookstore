<?php
require_once 'config/database.php';
require_once 'config/stripe.php';
require_once 'lib/stripe-php/init.php';

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$endpoint_secret = STRIPE_WEBHOOK_SECRET;

try {
    $event = \Stripe\Webhook::constructEvent(
        $payload, $sig_header, $endpoint_secret
    );
} catch(\UnexpectedValueException $e) {
    http_response_code(400);
    exit();
} catch(\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400);
    exit();
}

switch ($event->type) {
    case 'payment_intent.succeeded':
        $paymentIntent = $event->data->object;
        handlePaymentSuccess($paymentIntent);
        break;
    case 'payment_intent.payment_failed':
        $paymentIntent = $event->data->object;
        handlePaymentFailure($paymentIntent);
        break;
}

http_response_code(200);

function handlePaymentSuccess($paymentIntent) {
    global $conn;
    
    $orderId = $paymentIntent->metadata->order_id;
    
    $conn->beginTransaction();
    
    try {
        // Update order status
        $stmt = $conn->prepare("
            UPDATE orders 
            SET status = 'paid', 
                payment_intent_id = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$paymentIntent->id, $orderId]);
        
        // Update inventory
        $stmt = $conn->prepare("
            UPDATE inventory i
            JOIN order_items oi ON i.id = oi.inventory_id
            SET i.quantity = i.quantity - oi.quantity
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$orderId]);
        
        // Clear cart
        $stmt = $conn->prepare("
            DELETE FROM cart_items 
            WHERE user_id = (SELECT user_id FROM orders WHERE id = ?)
        ");
        $stmt->execute([$orderId]);
        
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollBack();
        error_log($e->getMessage());
    }
}

function handlePaymentFailure($paymentIntent) {
    global $conn;
    
    $orderId = $paymentIntent->metadata->order_id;
    
    try {
        $stmt = $conn->prepare("
            UPDATE orders 
            SET status = 'failed',
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$orderId]);
    } catch (Exception $e) {
        error_log($e->getMessage());
    }
}