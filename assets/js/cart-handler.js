class CartHandler {
    constructor() {
        this.baseUrl = '/onlinebookstore';
        this.initialize();
        console.log('CartHandler initialized'); // Debug log
    }

    initialize() {
        document.addEventListener('click', e => {
            const addToCartBtn = e.target.closest('.add-to-cart');
            if (addToCartBtn) {
                e.preventDefault();
                console.log('Add to cart button clicked');
                
                if (addToCartBtn.hasAttribute('data-redirect')) {
                    window.location.href = `${this.baseUrl}/pages/login.php`;
                    return;
                }

                const bookId = addToCartBtn.dataset.bookId;
                console.log('Book ID:', bookId);
                
                if (bookId) {
                    addToCartBtn.disabled = true;
                    const originalText = addToCartBtn.textContent;
                    addToCartBtn.textContent = 'Adding...';

                    this.addToCart(bookId)
                        .finally(() => {
                            addToCartBtn.disabled = false;
                            addToCartBtn.textContent = originalText;
                        });
                }
            }
        });
    }



     async addToCart(bookId, quantity = 1) {
        try {
            console.log('Sending request to add to cart:', bookId, quantity);
            const response = await fetch(`${this.baseUrl}/pages/cart/cart_actions.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=add&book_id=${bookId}&quantity=${quantity}`
            });

            const text = await response.text();
            console.log('Raw response:', text);

            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Error parsing JSON:', text);
                throw new Error('Invalid server response');
            }

            console.log('Parsed response:', data);

            if (data.success) {
                this.updateCartCount(data.cart_count);
                showNotification('Item added to cart successfully', 'success');
            } else {
                showNotification(data.message || 'Error adding to cart', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Error adding item to cart', 'error');
        }
    }

    updateCartCount(count) {
        const cartCount = document.querySelector('.cart-count');
        if (cartCount) {
            cartCount.textContent = count;
            cartCount.classList.add('animate');
            setTimeout(() => cartCount.classList.remove('animate'), 300);
        }
    }
}

// Initialize cart handler
const cart = new CartHandler();