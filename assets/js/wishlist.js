class WishlistPage {
    constructor() {
        this.baseUrl = '/onlinebookstore';
        this.initializeEventListeners();
    }

    initializeEventListeners() {
        // Select all checkbox
        const selectAllCheckbox = document.getElementById('selectAllWishlist');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', () => this.toggleSelectAll());
        }

        // Individual checkboxes
        const checkboxes = document.querySelectorAll('.wishlist-item-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => this.updateBulkActionButtons());
        });

        // Remove buttons
        document.querySelectorAll('.remove-wishlist').forEach(button => {
            button.addEventListener('click', async (e) => {
                const wishlistItem = e.target.closest('.wishlist-item');
                const bookId = wishlistItem.dataset.bookId;
                await this.removeFromWishlist(bookId);
            });
        });

        // Bulk action buttons
        document.getElementById('addSelectedToCart')?.addEventListener('click', () => this.addSelectedToCart());
        document.getElementById('removeSelected')?.addEventListener('click', () => this.removeSelected());
    }

    async removeFromWishlist(bookId) {
        try {
            const response = await fetch(`${this.baseUrl}/assets/api/wishlist/remove.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ book_id: bookId })
            });

            // Add this debug code
            const responseText = await response.text();
            try {
                const data = JSON.parse(responseText);
                if (data.success) {
                    const item = document.querySelector(`.wishlist-item[data-book-id="${bookId}"]`);
                    if (item) {
                        item.remove();
                        this.updateWishlistCount();
                        this.showNotification('Item removed from wishlist', 'success');
                    }
                } else {
                    this.showNotification(data.message || 'Error removing item', 'error');
                }
            } catch (e) {
                console.error('Server response:', responseText);
                this.showNotification('Error parsing server response', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showNotification('Error removing item from wishlist', 'error');
        }
    }

    async removeSelected() {
        const selectedItems = this.getSelectedItems();
        if (selectedItems.length === 0) return;

        try {
            const promises = selectedItems.map(bookId => 
                fetch(`${this.baseUrl}/assets/api/wishlist/remove.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ book_id: bookId })
                })
            );

            const responses = await Promise.all(promises);
            const results = await Promise.all(responses.map(r => r.json()));

            if (results.every(r => r.success)) {
                selectedItems.forEach(bookId => {
                    const item = document.querySelector(`.wishlist-item[data-book-id="${bookId}"]`);
                    if (item) item.remove();
                });
                this.updateWishlistCount();
                this.showNotification('Selected items removed from wishlist', 'success');
            } else {
                this.showNotification('Error removing some items', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showNotification('Error removing items from wishlist', 'error');
        }
    }

    async addSelectedToCart() {
        const selectedItems = this.getSelectedItems();
        if (selectedItems.length === 0) return;

        try {
            const response = await fetch(`${this.baseUrl}/assets/api/wishlist/add-to-cart.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ book_ids: selectedItems })
            });

            // Add this debug code
            const responseText = await response.text();
            try {
                const data = JSON.parse(responseText);
                if (data.success) {
                    this.showNotification('Selected items added to cart', 'success');
                    window.location.reload();
                } else {
                    this.showNotification(data.message || 'Error adding items to cart', 'error');
                }
            } catch (e) {
                console.error('Server response:', responseText);
                this.showNotification('Error parsing server response', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showNotification('Error adding items to cart', 'error');
        }
    }

    getSelectedItems() {
        const checkboxes = document.querySelectorAll('.wishlist-item-checkbox:checked');
        return Array.from(checkboxes).map(checkbox => 
            checkbox.closest('.wishlist-item').dataset.bookId
        );
    }

    toggleSelectAll() {
        const selectAllCheckbox = document.getElementById('selectAllWishlist');
        const checkboxes = document.querySelectorAll('.wishlist-item-checkbox:not(:disabled)');
        checkboxes.forEach(checkbox => checkbox.checked = selectAllCheckbox.checked);
        this.updateBulkActionButtons();
    }

    updateBulkActionButtons() {
        const hasSelection = document.querySelectorAll('.wishlist-item-checkbox:checked').length > 0;
        const addToCartBtn = document.getElementById('addSelectedToCart');
        const removeSelectedBtn = document.getElementById('removeSelected');
        if (addToCartBtn) addToCartBtn.disabled = !hasSelection;
        if (removeSelectedBtn) removeSelectedBtn.disabled = !hasSelection;
    }

    updateWishlistCount() {
        const count = document.querySelectorAll('.wishlist-item').length;
        const countElement = document.querySelector('.wishlist-count');
        if (countElement) {
            countElement.textContent = count;
        }
        document.querySelector('.wishlist-header h1').textContent = `My Wishlist (${count} items)`;
    }

    showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => notification.classList.add('show'), 100);
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
}

// Initialize the wishlist page
document.addEventListener('DOMContentLoaded', () => {
    new WishlistPage();
});