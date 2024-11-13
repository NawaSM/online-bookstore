// wishlist-handler.js
class WishlistHandler {
    constructor() {
        this.baseUrl = '/onlinebookstore';
        this.initializeWishlistButtons();
    }

    async toggleWishlist(bookId, button) {
        const url = `${this.baseUrl}/assets/api/wishlist/toggle.php`;
        console.log('Toggling wishlist for book:', bookId);

        if (!this.isAuthenticated()) {
            window.location.href = `${this.baseUrl}/pages/login.php`;
            return;
        }

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ book_id: bookId }),
                credentials: 'same-origin'
            });

            // Log raw response
            const rawResponse = await response.text();
            console.log('Raw server response:', rawResponse);

            let data;
            try {
                data = JSON.parse(rawResponse);
            } catch (e) {
                console.error('Error parsing JSON:', e);
                return;
            }

            console.log('Parsed response data:', data);

            if (data.success) {
                // Get wishlist count before update
                const wishlistCount = document.querySelector('.wishlist-count');
                const oldCount = parseInt(wishlistCount?.textContent || '0');
                console.log('Current wishlist count:', oldCount);

                const icon = button.querySelector('i');
                const isCurrentlyInWishlist = icon.classList.contains('fas');
                console.log('Is currently in wishlist:', isCurrentlyInWishlist);

                if (data.action === 'added to') {
                    icon.className = 'fas fa-heart';
                    button.classList.add('active');
                    this.updateWishlistCount(oldCount + 1);
                    console.log('Added to wishlist, new count:', oldCount + 1);
                } else {
                    icon.className = 'far fa-heart';
                    button.classList.remove('active');
                    this.updateWishlistCount(Math.max(0, oldCount - 1));
                    console.log('Removed from wishlist, new count:', Math.max(0, oldCount - 1));
                }

                this.showNotification(data.message, 'success');
            } else {
                console.error('Error response from server:', data.message);
                this.showNotification(data.message, 'error');
            }
        } catch (error) {
            console.error('Fetch error:', error);
            this.showNotification('Error updating wishlist', 'error');
        }
    }


    initializeWishlistButtons() {
        document.addEventListener('click', (e) => {
            const wishlistBtn = e.target.closest('.wishlist-icon');
            if (!wishlistBtn) return;

            e.preventDefault();
            const bookId = wishlistBtn.dataset.bookId;
            this.toggleWishlist(bookId, wishlistBtn);
        });
    }

    updateWishlistCount(count) {
        const wishlistCount = document.querySelector('.wishlist-count');
        if (wishlistCount) {
            wishlistCount.textContent = count;
            wishlistCount.classList.add('animate');
            setTimeout(() => wishlistCount.classList.remove('animate'), 300);
        }
    }

    isAuthenticated() {
        return document.body.dataset.authenticated === 'true';
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

// Initialize the wishlist handler
document.addEventListener('DOMContentLoaded', () => {
    new WishlistHandler();
});