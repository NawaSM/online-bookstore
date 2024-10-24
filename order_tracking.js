document.getElementById('orderForm').addEventListener('submit', function (event) {
    event.preventDefault();
    
    const orderNumber = document.getElementById('orderNumber').value;
    const statusDiv = document.getElementById('orderStatus');

    if (orderNumber === '') {
        statusDiv.textContent = 'Please enter a valid order number.';
        return;
    }

    // Send AJAX request to the PHP file
    fetch(`track_order.php?orderNumber=${orderNumber}`)
        .then(response => response.json())
        .then(data => {
            statusDiv.textContent = `Order Status: ${data.status}`;
        })
        .catch(error => {
            statusDiv.textContent = 'Error tracking order.';
        });
});
