document.addEventListener('DOMContentLoaded', function() {
    // Initialize cart display
    updateCartDisplay();

    // Add to cart form submissions
    document.querySelectorAll('.add-to-cart-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const basePath = '/onlinebookstore';
            const bookId = this.querySelector('[name="book_id"]').value;
            const quantity = this.querySelector('[name="quantity"]').value;
            addToCart(bookId, quantity);
        });
    });

    // Quantity adjustment buttons
    document.querySelectorAll('.quantity-btn').forEach(button => {
        button.addEventListener('click', function() {
            const item = this.closest('.cart-item');
            const bookId = item.dataset.id;
            const input = item.querySelector('.quantity-input');
            const currentValue = parseInt(input.value);
            
            if (this.classList.contains('plus')) {
                updateQuantity(bookId, currentValue + 1);
            } else if (this.classList.contains('minus')) {
                updateQuantity(bookId, currentValue - 1);
            }
        });
    });

    // Quantity input direct changes
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', function() {
            const item = this.closest('.cart-item');
            const bookId = item.dataset.id;
            updateQuantity(bookId, this.value);
        });
    });

    // Remove buttons
    document.querySelectorAll('.remove-btn').forEach(button => {
        button.addEventListener('click', function() {
            const item = this.closest('.cart-item');
            const bookId = item.dataset.id;
            removeItem(bookId);
        });
    });

    // Clear cart button
    const clearCartBtn = document.getElementById('clear-cart');
    if (clearCartBtn) {
        clearCartBtn.addEventListener('click', clearCart);
    }

    // Save remarks
    const remarksTextarea = document.getElementById('cart-remarks');
    const saveRemarksBtn = document.querySelector('.save-remarks');
    if (saveRemarksBtn) {
        saveRemarksBtn.addEventListener('click', saveRemarks);
    }

    // Apply promo code
    const applyPromoBtn = document.querySelector('.apply-promo');
    if (applyPromoBtn) {
        applyPromoBtn.addEventListener('click', applyPromoCode);
    }
});

function updateQuantity(bookId, action, value) {
    let currentInput = document.querySelector(`.cart-item[data-id="${bookId}"] .quantity-input`);
    let currentValue = parseInt(currentInput.value);
    let maxValue = parseInt(currentInput.getAttribute('max'));
    let newValue;

    switch(action) {
        case 'increase':
            newValue = currentValue + 1;
            break;
        case 'decrease':
            newValue = currentValue - 1;
            break;
        case 'set':
            newValue = parseInt(value);
            break;
    }

    if (newValue < 1) newValue = 1;
    if (newValue > maxValue) {
        showNotification('Cannot exceed available stock', 'error');
        return;
    }

    fetch(`${basePath}/pages/cart/cart_actions.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update&book_id=${bookId}&quantity=${newValue}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartDisplay(data);
        } else {
            showNotification(data.error, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

function removeItem(bookId) {
    if (!confirm('Are you sure you want to remove this item?')) return;

    fetch(`${basePath}/pages/cart/cart_actions.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=remove&book_id=${bookId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartDisplay(data);
            showNotification('Item removed from cart', 'success');
        } else {
            showNotification(data.error, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

function clearCart() {
    if (!confirm('Are you sure you want to clear your cart?')) return;

    fetch(`${basePath}/pages/cart/cart_actions.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=clear'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartDisplay(data);
            showNotification('Cart cleared', 'success');
        } else {
            showNotification(data.error, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

function updateCartDisplay(data) {
    if (!data) {
        fetch(`${basePath}/pages/cart/cart_actions.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get'
        })
        .then(response => response.json())
        .then(data => {
            renderCart(data);
        })
        .catch(error => console.error('Error:', error));
        return;
    }
    renderCart(data);
}

function showNotification(message, type) {
    const event = new CustomEvent('showNotification', {
        detail: { message, type }
    });
    document.dispatchEvent(event);
}

function addToCart(bookId, quantity) {
    fetch(`${basePath}/pages/cart/cart_actions.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=add&book_id=${bookId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartDisplay(data);
            showNotification('Item added to cart', 'success');
        } else {
            showNotification(data.error, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

function renderCart(data) {
    const container = document.getElementById('cart-container');
    if (data.items.length === 0) {
        container.innerHTML = `
            <div class="empty-cart">
                <p>Your cart is empty</p>
                <a href="index.php" class="btn">Continue Shopping</a>
            </div>
        `;
        return;
    }

    let html = '<div class="cart-items">';
    data.items.forEach(item => {
        html += `
            <div class="cart-item" data-id="${item.book_id}">
                <div class="item-details">
                    <h3>${item.book_name}</h3>
                    <div class="price-info">
                        ${item.special_price ? 
                            `<span class="special-price">RM${parseFloat(item.special_price).toFixed(2)}</span>
                             <span class="original-price">RM${parseFloat(item.price).toFixed(2)}</span>` :
                            `<span class="price">RM${parseFloat(item.price).toFixed(2)}</span>`
                        }
                    </div>
                </div>
                
                <div class="quantity-controls">
                    <button class="quantity-btn minus" onclick="updateQuantity(${item.book_id}, 'decrease')">-</button>
                    <input type="number" class="quantity-input" 
                           value="${item.quantity}" 
                           min="1" 
                           max="${item.available_quantity}"
                           onchange="updateQuantity(${item.book_id}, 'set', this.value)">
                    <button class="quantity-btn plus" onclick="updateQuantity(${item.book_id}, 'increase')">+</button>
                    <button class="remove-btn" onclick="removeItem(${item.book_id})">Remove</button>
                </div>

                <div class="item-total">
                    RM${((item.special_price || item.price) * item.quantity).toFixed(2)}
                </div>
            </div>
        `;
    });
    html += '</div>';

    // Add Shipping Note
    html += `
        <div class="shipping-note">
            ${data.total < 150 ? 
                `<p>Add RM${(150 - data.total).toFixed(2)} more to your cart for free shipping!</p>` :
                '<p>Your order qualifies for free shipping!</p>'
            }
        </div>
    `;

    // Add Cart Extras (Remarks and Promo)
    html += `
        <div class="cart-extras">
            <div class="remarks-section">
                <h3>Special Instructions</h3>
                <textarea id="cart-remarks" placeholder="Add any special instructions for your order">${data.remarks || ''}</textarea>
                <button class="btn btn-secondary save-remarks">Save Instructions</button>
            </div>

            <div class="promo-section">
                <h3>Promo Code</h3>
                <div class="promo-input">
                    <input type="text" id="promo-code" placeholder="Enter promo code">
                    <button class="btn btn-secondary apply-promo">Apply</button>
                </div>
                <div id="promo-message"></div>
            </div>
        </div>
    `;

    // Calculate totals
    const shipping = data.total > 150 ? 0 : 10;
    const discount = data.applied_discount || 0;
    const finalTotal = data.total - discount + shipping;

    // Add Cart Summary
    html += `
        <div class="cart-summary">
            <div class="summary-details">
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>RM${data.total.toFixed(2)}</span>
                </div>
                
                ${data.applied_discount ? `
                    <div class="summary-row discount">
                        <span>Discount:</span>
                        <span>-RM${data.applied_discount.toFixed(2)}</span>
                    </div>
                ` : ''}
                
                <div class="summary-row shipping">
                    <span>Shipping:</span>
                    <span>RM${data.total >= 150 ? '0.00' : '10.00'}</span>
                </div>
                
                <div class="summary-row total">
                    <span>Total:</span>
                    <span>RM${(data.total - (data.applied_discount || 0) + (data.total >= 150 ? 0 : 10)).toFixed(2)}</span>
                </div>
            </div>
            
            <div class="cart-actions">
                <button id="clear-cart" class="btn btn-secondary">Clear Cart</button>
                <a href="${basePath}/pages/cart/checkout.php" class="btn btn-primary">Proceed to Checkout</a>
            </div>
        </div>
    `;

    container.innerHTML = html;

    // Reattach event listeners
    attachEventListeners();
}

function attachEventListeners() {
    // Save remarks button
    const saveRemarksBtn = document.querySelector('.save-remarks');
    if (saveRemarksBtn) {
        saveRemarksBtn.addEventListener('click', saveRemarks);
    }

    // Apply promo code button
    const applyPromoBtn = document.querySelector('.apply-promo');
    if (applyPromoBtn) {
        applyPromoBtn.addEventListener('click', applyPromoCode);
    }

    // Clear cart button
    const clearCartBtn = document.getElementById('clear-cart');
    if (clearCartBtn) {
        clearCartBtn.addEventListener('click', clearCart);
    }
}

function saveRemarks() {
    const remarks = document.getElementById('cart-remarks').value;
    
    fetch(`${basePath}/pages/cart/cart_actions.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=save_remarks&remarks=${encodeURIComponent(remarks)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Instructions saved successfully', 'success');
        } else {
            showNotification(data.error, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

function applyPromoCode() {
    const promoCode = document.getElementById('promo-code').value;
    if (!promoCode) {
        showNotification('Please enter a promo code', 'error');
        return;
    }

    const subtotalElement = document.querySelector('.summary-row:first-child span:last-child');
    const subtotal = parseFloat(subtotalElement.textContent.replace('RM', ''));
    
    fetch(`${basePath}/pages/cart/cart_actions.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=apply_promo&code=${encodeURIComponent(promoCode)}&subtotal=${subtotal}`
    })
    .then(response => response.text()) 
    .then(text => {
        console.log('Raw response:', text); // Debug log
        try {
            const data = JSON.parse(text);
            console.log('Parsed data:', data); // Debug log
            
            if (data.success) {
                showNotification('Promo code applied successfully', 'success');
                
                // Update cart display with new data
                renderCart({
                    items: data.items,
                    total: data.total,
                    applied_discount: data.discount
                });
                
                // Update promo message
                const promoMessage = document.getElementById('promo-message');
                if (promoMessage) {
                    promoMessage.textContent = `Discount applied: RM${data.discount.toFixed(2)}`;
                    promoMessage.className = 'success';
                }
            } else {
                showNotification(data.message || 'Error applying promo code', 'error');
                const promoMessage = document.getElementById('promo-message');
                if (promoMessage) {
                    promoMessage.textContent = data.message || 'Error applying promo code';
                    promoMessage.className = 'error';
                }
            }
        } catch (e) {
            console.error('JSON Parse Error:', e);
            console.error('Response Text:', text);
            showNotification('Error processing response', 'error');
        }
    })
    .catch(error => {
        console.error('Fetch Error:', error);
        showNotification('An error occurred while applying the promo code', 'error');
    });
}