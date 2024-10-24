<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Tracking - Online Bookstore</title>
    <link rel="stylesheet" href="order_tracking.css">
</head>
<body>
    <div class="container">
        <h1>Track Your Order</h1>
        <form id="orderForm">
            <label for="orderNumber">Enter your Order Number:</label>
            <input type="text" id="orderNumber" name="orderNumber" placeholder="Order Number">
            <button type="submit">Track Order</button>
        </form>
        <div id="orderStatus"></div>
    </div>
    <script src="order_tracking.js"></script>
</body>
</html>
