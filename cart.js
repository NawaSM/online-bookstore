// cart.js

// Initialize cart and wishlist arrays
let cart = [];
let wishlist = [];

// Sample book data (in a real application, this would come from a database)
const books = [
    { id: 1, title: "The Great Gatsby", price: 15.99 },
    { id: 2, title: "To Kill a Mockingbird", price: 12.99 },
    { id: 3, title: "1984", price: 10.99 },
    // Add more books as needed
];

// Sample items added to cart (simulate items added from home or wishlist pages)
cart.push({ ...books[0], quantity: 2 });
cart.push({ ...books[1], quantity: 1 });

// Function to render cart items
function renderCart() {
    const cartElement = document.getElementById('cart-items');
    cartElement.innerHTML = '<h2>Cart Items</h2>';

    if (cart.length === 0) {
        cartElement.innerHTML += '<p>Your cart is empty.</p>';
        document.getElementById('cart-summary').style.display = 'none';
        return;
    } else {
        document.getElementById('cart-summary').style.display = 'block';
    }

    cart.forEach(item => {
        const itemElement = document.createElement('div');
        itemElement.className = 'cart-item';
        itemElement.innerHTML = `
            <span>${item.title} - $${item.price.toFixed(2)} x ${item.quantity}</span>
            <div>
                <button onclick="updateQuantity(${item.id}, ${item.quantity - 1})" class="navy-button">-</button>
                <span class="quantity">${item.quantity}</span>
                <button onclick="updateQuantity(${item.id}, ${item.quantity + 1})" class="navy-button">+</button>
                <button onclick="moveToWishlist(${item.id})" class="navy-button">Move to Wishlist</button>
                <button onclick="removeFromCart(${item.id})" class="navy-button">Remove</button>
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
    const index = cart.findIndex(item => item.id === id);
    if (index !== -1) {
        if (newQuantity > 0) {
            cart[index].quantity = newQuantity;
        } else {
            // Remove item if quantity is zero or less
            cart.splice(index, 1);
        }
        renderCart();
        updateCartCount();
    }
}

// Function to remove item from cart
function removeFromCart(id) {
    cart = cart.filter(item => item.id !== id);
    renderCart();
    updateCartCount();
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
    const subtotal = cart.reduce((total, item) => total + item.price * item.quantity, 0);
    const discountCode = document.getElementById('discount-code').value.trim().toUpperCase();
    let discount = 0;

    if (discountCode === 'DISC10') {
        discount = subtotal * 0.10;
    } else if (discountCode === 'DISC20') {
        discount = subtotal * 0.20;
    }

    let total = subtotal - discount;
    let shipping = 10; // Default shipping cost
    if (total > 150) { // Assuming RM150 is the threshold for free shipping
        shipping = 0;
    }

    total += shipping;

    summaryElement.innerHTML = `
        <p>Subtotal: $${subtotal.toFixed(2)}</p>
        <p>Discount (${discountCode}): -$${discount.toFixed(2)}</p>
        <p>Shipping: $${shipping.toFixed(2)}</p>
        <p><strong>Total: $${total.toFixed(2)}</strong></p>
    `;
}

// Function to apply discount code
function applyDiscount() {
    const discountCodeInput = document.getElementById('discount-code');
    const discountCode = discountCodeInput.value.trim().toUpperCase();

    if (discountCode === 'DISC10' || discountCode === 'DISC20') {
        alert(`Discount code ${discountCode} applied!`);
    } else if (discountCode !== '') {
        alert('Invalid discount code.');
    }

    updateSummary();
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
    const book = books.find(b => b.id === bookId);
    if (!book) {
        console.error('Book not found:', bookId);
        return;
    }

    const existingItem = cart.find(item => item.id === bookId);
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({ ...book, quantity: 1 });
    }

    updateCartCount();
    renderCart();

    if (source === 'wishlist') {
        // Remove from wishlist if added from wishlist
        wishlist = wishlist.filter(item => item.id !== bookId);
        renderWishlist();
    }

    alert(`${book.title} has been added to your cart.`);
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

function loadCart() {
    const savedCart = localStorage.getItem('cart');
    if (savedCart) {
        cart = JSON.parse(savedCart);
    }
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
    loadWishlist();
    renderCart();
    renderWishlist();
    updateCartCount();
    
    const accountLink = document.getElementById('accountLink');
    if (accountLink) {
        accountLink.addEventListener('click', checkLogin);
    }
});

