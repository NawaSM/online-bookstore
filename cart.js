// cart.js

// Initialize cart and wishlist arrays
let cart = [];
let wishlist = [];
let currentDiscount = 0;
let currentPromoCode = '';

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
            cart = data.items;
            currentDiscount = parseFloat(data.applied_discount) || 0;
            currentPromoCode = data.applied_promo_code || '';
            console.log('Cart after loading:', cart);
            renderCart();
            updateCartCount();
            updateSummary();
            if (currentPromoCode) {
                document.getElementById('discount-code').value = currentPromoCode;
            }
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
            <span>${item.book_name} - RM${parseFloat(item.price).toFixed(2)} x ${item.quantity}</span>
            <div>
                <button onclick="updateQuantity(${item.book_id}, ${parseInt(item.quantity) - 1})" class="navy-button">-</button>
                <span class="quantity">${item.quantity}</span>
                <button onclick="updateQuantity(${item.book_id}, ${parseInt(item.quantity) + 1})" class="navy-button">+</button>
                <button onclick="removeFromCart(${item.book_id})" class="navy-button">Remove</button>
            </div>
        `;
        cartElement.appendChild(itemElement);
    });

    updateSummary();
    updateCartCount();
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
    // Ensure newQuantity is a number and not less than 0
    newQuantity = Math.max(0, parseInt(newQuantity, 10));

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
            loadCart(); // Reload the entire cart to ensure consistency
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
function updateSummary() {
    const summaryElement = document.getElementById('summary-details');
    const subtotal = calculateSubtotal();
    let total = subtotal - currentDiscount;
    let shipping = total > 150 ? 0 : 10; // Free shipping over RM150
    total += shipping;

    summaryElement.innerHTML = `
        <p>Subtotal: RM${subtotal.toFixed(2)}</p>
        <p>Discount: -RM${currentDiscount.toFixed(2)}</p>
        <p>Shipping: RM${shipping.toFixed(2)}</p>
        <p><strong>Total: RM${total.toFixed(2)}</strong></p>
    `;
}

// Function to apply discount code
function applyPromoCode() {
    const promoCode = document.getElementById('discount-code').value;
    const subtotal = calculateSubtotal();

    console.log('Applying promo code:', promoCode, 'Subtotal:', subtotal);

    fetch('apply_promo.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `code=${encodeURIComponent(promoCode)}&subtotal=${subtotal}`
    })
    .then(response => response.json())
    .then(data => {
        console.log('Promo code response:', data);
        if (data.valid) {
            currentDiscount = parseFloat(data.discount);
            currentPromoCode = promoCode;
            alert(`Promo code applied! Discount: RM${currentDiscount.toFixed(2)}`);
            updateSummary();
        } else {
            alert(data.message || 'Invalid promo code');
        }
    })
    .catch(error => {
        console.error('Error applying promo code:', error);
        alert('An error occurred while applying the promo code. Please try again.');
    });
}

function calculateSubtotal() {
    return cart.reduce((total, item) => total + (parseFloat(item.price) * parseInt(item.quantity)), 0);
}

// Function to handle checkout
function handleCheckout() {
    const remarks = document.getElementById('special-remarks').value;
    const promoCode = document.getElementById('discount-code').value;

    fetch('process_checkout.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `remarks=${encodeURIComponent(remarks)}&promo_code=${encodeURIComponent(promoCode)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Order placed successfully!');
            // Redirect to order confirmation page or clear cart
            window.location.href = 'order_confirmation.php?order_id=' + data.order_id;
        } else {
            alert('Error placing order: ' + data.error);
        }
    })
    .catch(error => console.error('Error:', error));
}

// Function to update cart count in header
function updateCartCount() {
    const cartCountElement = document.querySelector('.icons a[aria-label="Shopping Cart"]');
    const itemCount = cart.reduce((count, item) => count + parseInt(item.quantity, 10), 0);

    // Check if a span for count exists; if not, create one
    let countSpan = cartCountElement.querySelector('.cart-count');
    if (!countSpan) {
        countSpan = document.createElement('span');
        countSpan.className = 'cart-count';
        cartCountElement.appendChild(countSpan);
    }

    countSpan.textContent = itemCount;
    countSpan.style.display = itemCount > 0 ? 'inline-block' : 'none';

    // Remove any existing inline styles that might be causing issues
    countSpan.style.removeProperty('margin-left');
    countSpan.style.removeProperty('background-color');
    countSpan.style.removeProperty('color');
    countSpan.style.removeProperty('border-radius');
    countSpan.style.removeProperty('padding');
    countSpan.style.removeProperty('font-size');
}

// Event listeners for discount and checkout buttons
document.addEventListener('DOMContentLoaded', () => {
    loadCart();
    
    const applyDiscountButton = document.getElementById('apply-discount');
    const checkoutButton = document.getElementById('pay-button');

    if (applyDiscountButton) {
        applyDiscountButton.addEventListener('click', applyPromoCode);
    }

    if (checkoutButton) {
        checkoutButton.addEventListener('click', handleCheckout);
    }
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

/*function checkLogin(e) {
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
}*/


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
        applyDiscountButton.addEventListener('click', applyPromoCode);
    }

    if (checkoutButton) {
        checkoutButton.addEventListener('click', handleCheckout);
    }

    /*if (accountLink) {
        accountLink.addEventListener('click', checkLogin);
    }*/
});

