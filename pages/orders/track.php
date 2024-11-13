<?php
session_start();
require_once('../../config/database.php');

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Unauthorized');
}

try {
    $orderId = $_GET['id'];
    $userId = $_SESSION['user_id'];

    $stmt = $conn->prepare("
        SELECT status, created_at, 
               (SELECT created_at FROM orders WHERE id = :orderId AND status = 'processing') as processing_date,
               (SELECT created_at FROM orders WHERE id = :orderId AND status = 'shipped') as shipped_date,
               (SELECT created_at FROM orders WHERE id = :orderId AND status = 'delivered') as delivered_date
        FROM orders 
        WHERE id = :orderId AND user_id = :userId
    ");
    
    $stmt->execute([
        'orderId' => $orderId,
        'userId' => $userId
    ]);
    
    $tracking = $stmt->fetch();

    if (!$tracking) {
        throw new Exception('Order not found');
    }

    $timeline = [
        [
            'status' => 'Order Placed',
            'date' => $tracking['created_at'],
            'completed' => true
        ],
        [
            'status' => 'Processing',
            'date' => $tracking['processing_date'],
            'completed' => !empty($tracking['processing_date'])
        ],
        [
            'status' => 'Shipped',
            'date' => $tracking['shipped_date'],
            'completed' => !empty($tracking['shipped_date'])
        ],
        [
            'status' => 'Delivered',
            'date' => $tracking['delivered_date'],
            'completed' => !empty($tracking['delivered_date'])
        ]
    ];

    echo json_encode(['timeline' => $timeline]);

} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => $e->getMessage()]);
}
?>