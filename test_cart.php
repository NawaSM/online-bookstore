<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Simulate user login for testing
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Use a test user ID
}

// Fetch some books from the inventory for testing
$stmt = $pdo->query("SELECT id, book_name, price FROM inventory LIMIT 5");
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Cart Functionality</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Test Cart Functionality</h1>
        
        <h2>Available Books</h2>
        <?php foreach ($books as $book): ?>
            <div class="book-item">
                <span><?php echo htmlspecialchars($book['book_name']); ?> - $<?php echo number_format($book['price'], 2); ?></span>
                <input type="number" id="quantity-<?php echo $book['id']; ?>" min="1" value="1">
                <button onclick="addToCart(<?php echo $book['id']; ?>)">Add to Cart</button>
            </div>
        <?php endforeach; ?>

        <h2>Cart Contents</h2>
        <div id="cart-contents"></div>
    </div>

    <script src="cart.js"></script>
    <script>
    function addToCart(bookId) {
        const quantity = document.getElementById(`quantity-${bookId}`).value;
        fetch('cart_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=add&book_id=${bookId}&quantity=${quantity}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Book added to cart successfully!');
                loadCartContents();
            } else {
                alert('Error adding book to cart: ' + data.error);
            }
        })
        .catch(error => console.error('Error:', error));
    }

    function loadCartContents() {
        fetch('cart_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get'
        })
        .then(response => response.json())
        .then(data => {
            const cartContents = document.getElementById('cart-contents');
            cartContents.innerHTML = '';
            data.forEach(item => {
                cartContents.innerHTML += `<p>${item.book_name} - Quantity: ${item.quantity} - $${(item.price * item.quantity).toFixed(2)}</p>`;
            });
        })
        .catch(error => console.error('Error:', error));
    }

    document.addEventListener('DOMContentLoaded', loadCartContents);
    </script>
</body>
</html>