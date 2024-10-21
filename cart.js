// cart.js

// Initialize cart and wishlist arrays
let cart = [];
let wishlist = [];

function loadCart() {
    console.log('Loading cart...');
    fetch('cart_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get'
    })
    .then(response => response.json())
    .then(data => {
        console.log('Cart data received:', data);
        if (data.error) {
            console.error('Error:', data.error);
        } else {
            cart = data;
            console.log('Cart after loading:', cart);
            renderCart();
            updateCartCount();
        }
    })
    .catch(error => console.error('Error:', error));
}

// Function to render cart items
function renderCart() {
    console.log('Rendering cart:', cart);
    const cartElement = document.getElementById('cart-items');
    cartElement.innerHTML = '<h2>Cart Items</h2>';

    if (!Array.isArray(cart) || cart.length === 0) {
        console.log('Cart is empty or not an array');
        cartElement.innerHTML += '<p>Your cart is empty.</p>';
        document.getElementById('cart-summary').style.display = 'none';
        return;
    }

    document.getElementById('cart-summary').style.display = 'block';

    cart.forEach(item => {
        console.log('Rendering item:', item);
        const itemElement = document.createElement('div');
        itemElement.className = 'cart-item';
        itemElement.innerHTML = `
            <span>${item.book_name} - $${parseFloat(item.price).toFixed(2)} x ${item.quantity}</span>
            <div>
                <button onclick="updateQuantity(${item.book_id}, ${item.quantity - 1})" class="navy-button">-</button>
                <span class="quantity">${item.quantity}</span>
                <button onclick="updateQuantity(${item.book_id}, ${item.quantity + 1})" class="navy-button">+</button>
                <button onclick="removeFromCart(${item.book_id})" class="navy-button">Remove</button>
            </div>
        `;
        cartElement.appendChild(itemElement);
    });

    updateSummary();
}

// Function to render wishlist items
function renderWishlist() {
    const wishlistElement = document.getElementById('wishlist');
    wishlistElement.innerHTML = '<h2>Wishlist</h2>';

    if (wishlist.length === 0) {
        wishlistElement.innerHTML += '<p>Your wishlist is empty.</p>';
        return;
    }

    wishlist.forEach(item => {
        const itemElement = document.createElement('div');
        itemElement.className = 'wishlist-item';
        itemElement.innerHTML = `
            <span>${item.title} - $${item.price.toFixed(2)}</span>
            <button onclick="moveToCart(${item.id})" class="navy-button">Move to Cart</button>
        `;
        wishlistElement.appendChild(itemElement);
    });
}

// Function to update item quantity in cart
function updateQuantity(id, newQuantity) {
    fetch('cart_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update&book_id=${id}&quantity=${newQuantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadCart();
        } else {
            console.error('Error:', data.error);
        }
    })
    .catch(error => console.error('Error:', error));
}

// Function to remove item from cart
function removeFromCart(id) {
    fetch('cart_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=remove&book_id=${id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadCart();
        } else {
            console.error('Error:', data.error);
        }
    })
    .catch(error => console.error('Error:', error));
}

// Function to move item from cart to wishlist
function moveToWishlist(id) {
    const item = cart.find(item => item.id === id);
    if (item) {
        wishlist.push({ ...item, quantity: 1 });
        removeFromCart(id);
        renderWishlist();
    }
}

// Function to move item from wishlist to cart
function moveToCart(id) {
    const item = wishlist.find(item => item.id === id);
    if (item) {
        const existingCartItem = cart.find(ci => ci.id === id);
        if (existingCartItem) {
            existingCartItem.quantity += 1;
        } else {
            cart.push({ ...item, quantity: 1 });
        }
        wishlist = wishlist.filter(item => item.id !== id);
        renderCart();
        renderWishlist();
        updateCartCount();
    }
}

// Function to update cart summary (subtotal, discount, shipping, total)
function updateSummary(discount = 0) {
    const summaryElement = document.getElementById('summary-details');
    const subtotal = calculateSubtotal();
    let total = subtotal - discount;
    let shipping = total > 150 ? 0 : 10; // Free shipping over $150
    total += shipping;

    summaryElement.innerHTML = `
        <p>Subtotal: $${subtotal.toFixed(2)}</p>
        <p>Discount: -$${discount.toFixed(2)}</p>
        <p>Shipping: $${shipping.toFixed(2)}</p>
        <p><strong>Total: $${total.toFixed(2)}</strong></p>
    `;
}

// Function to apply discount code
function applyDiscount() {
    const discountCodeInput = document.getElementById('discount-code');
    const discountCode = discountCodeInput.value.trim().toUpperCase();

    fetch('validate_promo.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `code=${encodeURIComponent(discountCode)}&total=${encodeURIComponent(calculateSubtotal())}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.valid) {
            alert(`Discount code ${discountCode} applied! You save $${data.discount.toFixed(2)}`);
            updateSummary(data.discount);
        } else {
            alert(data.message || 'Invalid discount code.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while validating the promo code.');
    });
}

function calculateSubtotal() {
    return cart.reduce((total, item) => total + item.price * item.quantity, 0);
}

// Function to handle checkout
function handleCheckout() {
    if (cart.length === 0) {
        alert('Your cart is empty. Add items to cart before checking out.');
        return;
    }
    // Redirect to checkout page
    window.location.href = "checkout.html"; // Replace with your actual checkout page URL
}

// Function to update cart count in header
function updateCartCount() {
    const cartCountElement = document.querySelector('.icons a[aria-label="Shopping Cart"]');
    const itemCount = cart.reduce((count, item) => count + item.quantity, 0);

    // Check if a span for count exists; if not, create one
    let countSpan = cartCountElement.querySelector('.cart-count');
    if (!countSpan) {
        countSpan = document.createElement('span');
        countSpan.className = 'cart-count';
        countSpan.style.marginLeft = '5px';
        countSpan.style.backgroundColor = 'red';
        countSpan.style.color = 'white';
        countSpan.style.borderRadius = '50%';
        countSpan.style.padding = '2px 6px';
        countSpan.style.fontSize = '0.8em';
        cartCountElement.appendChild(countSpan);
    }

    countSpan.textContent = itemCount;
    // Optionally, hide the count if zero
    countSpan.style.display = itemCount > 0 ? 'inline-block' : 'none';
}

// Event listeners for discount and checkout buttons
document.addEventListener('DOMContentLoaded', () => {
    // Ensure buttons have the correct IDs in HTML
    const applyDiscountButton = document.getElementById('apply-discount');
    const checkoutButton = document.getElementById('pay-button');

    if (applyDiscountButton) {
        applyDiscountButton.addEventListener('click', applyDiscount);
    }

    if (checkoutButton) {
        checkoutButton.addEventListener('click', handleCheckout);
    }

    // Initial render
    renderCart();
    renderWishlist();
    updateCartCount();
});

// Function to add items to cart from other pages (e.g., home.html, wishlist.html)
// This function can be called from other scripts or via global scope
function addToCart(bookId, source = 'home') {
    fetch('cart_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=add&book_id=${bookId}&quantity=1`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadCart();
            if (source === 'wishlist') {
                // Implement wishlist removal here if needed
            }
            alert(`Book has been added to your cart.`);
        } else {
            console.error('Error:', data.error);
        }
    })
    .catch(error => console.error('Error:', error));
}

// Optional: Function to clear the cart (useful for testing or after checkout)
function clearCart() {
    cart = [];
    renderCart();
    updateCartCount();
}

// Optional: Function to save and load cart from localStorage (persists cart across sessions)
function saveCart() {
    localStorage.setItem('cart', JSON.stringify(cart));
}

function saveWishlist() {
    localStorage.setItem('wishlist', JSON.stringify(wishlist));
}

function loadWishlist() {
    const savedWishlist = localStorage.getItem('wishlist');
    if (savedWishlist) {
        wishlist = JSON.parse(savedWishlist);
    }
}

function checkLogin(e) {
    e.preventDefault();
    
    fetch('check_login.php')
        .then(response => response.json())
        .then(data => {
            if (data.loggedIn) {
                window.location.href = 'profile1.php';
            } else {
                window.location.href = 'login.php';
            }
        })
        .catch(error => console.error('Error:', error));
}


document.addEventListener('DOMContentLoaded', () => {
    loadCart();
//    loadWishlist();
//    renderCart();
//    renderWishlist();
//    updateCartCount();
    
    const applyDiscountButton = document.getElementById('apply-discount');
    const checkoutButton = document.getElementById('pay-button');
    const accountLink = document.getElementById('accountLink');

    if (applyDiscountButton) {
        applyDiscountButton.addEventListener('click', applyDiscount);
    }

    if (checkoutButton) {
        checkoutButton.addEventListener('click', handleCheckout);
    }

    if (accountLink) {
        accountLink.addEventListener('click', checkLogin);
    }
});

