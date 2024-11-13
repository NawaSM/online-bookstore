document.addEventListener('DOMContentLoaded', function() {
    const isAuthenticated = document.body.dataset.authenticated === 'true';
    initializeBookSliders();


    // Banner/Carousel functionality
    const bannerSlider = {
        currentSlide: 0,
        slides: document.querySelectorAll('.banner-slide'),
        totalSlides: null,
        intervalId: null,

        init: function() {
            this.totalSlides = this.slides.length;
            if (this.totalSlides > 0) {
                this.showSlide(0);
                this.startAutoSlide();
                this.setupControls();
            }
        },

        showSlide: function(index) {
            this.slides.forEach(slide => slide.style.display = 'none');
            this.currentSlide = (index + this.totalSlides) % this.totalSlides;
            this.slides[this.currentSlide].style.display = 'block';
        },

        nextSlide: function() {
            this.showSlide(this.currentSlide + 1);
        },

        previousSlide: function() {
            this.showSlide(this.currentSlide - 1);
        },

        startAutoSlide: function() {
            this.intervalId = setInterval(() => this.nextSlide(), 5000);
        },

        stopAutoSlide: function() {
            clearInterval(this.intervalId);
        },

        setupControls: function() {
            document.querySelector('.banner-prev').addEventListener('click', () => {
                this.stopAutoSlide();
                this.previousSlide();
                this.startAutoSlide();
            });

            document.querySelector('.banner-next').addEventListener('click', () => {
                this.stopAutoSlide();
                this.nextSlide();
                this.startAutoSlide();
            });
        }
    };

    // Cart functionality
    const cart = {
        addToCart: async function(bookId, quantity = 1) {
            if (!isAuthenticated) {
                this.redirectToLogin('add items to cart');
                return;
            }

            try {
                const response = await fetch('pages/cart/cart_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'add',
                        book_id: bookId,
                        quantity: quantity
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    this.updateCartCount(data.cart_count);
                    showNotification('Book added to cart successfully!', 'success');
                }
            } catch (error) {
                showNotification('Failed to add book to cart', 'error');
            }
        },

        updateCartCount: function(count) {
            const cartCount = document.querySelector('.cart-count');
            if (cartCount) {
                cartCount.textContent = count;
            }
        },

        redirectToLogin: function(action) {
            const message = `Please login to ${action}`;
            if (confirm(message)) {
                window.location.href = 'pages/login.php?required=true&redirect=' + encodeURIComponent(window.location.href);
            }
        }
    };

    // Wishlist functionality
    const wishlist = {
        toggleWishlist: async function(bookId) {
            if (!isAuthenticated) {
                cart.redirectToLogin('add items to wishlist');
                return;
            }

            try {
                const response = await fetch('/onlinebookstore/api/wishlist/toggle.php', {  // Updated path
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        book_id: bookId
                    })
                });

                const data = await response.json();
                if (data.success) {
                    const wishlistIcon = document.querySelector(`.wishlist-icon[data-book-id="${bookId}"]`);
                    if (wishlistIcon) {
                        wishlistIcon.classList.toggle('active');
                        const icon = wishlistIcon.querySelector('i');
                        if (icon) {
                            icon.className = data.action === 'added to' ? 'fas fa-heart' : 'far fa-heart';
                        }
                    }
                    showNotification(data.message, 'success');
                } else {
                    if (data.message === 'Please login first') {
                        cart.redirectToLogin('add items to wishlist');
                    } else {
                        showNotification(data.message, 'error');
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Failed to update wishlist', 'error');
            }
        },

        addToCart: async function(bookIds) {
            if (!isAuthenticated) {
                cart.redirectToLogin('add items to cart');
                return;
            }

            try {
                const response = await fetch('/onlinebookstore/api/wishlist/add-to-cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ book_ids: Array.isArray(bookIds) ? bookIds : [bookIds] })
                });

                const data = await response.json();
                if (data.success) {
                    cart.updateCartCount(data.cart_count);
                    showNotification(data.message, 'success');

                    // Remove items from wishlist UI if on wishlist page
                    if (window.location.pathname.includes('wishlist.php')) {
                        bookIds.forEach(id => {
                            const item = document.querySelector(`.wishlist-item[data-book-id="${id}"]`);
                            if (item) item.remove();
                        });

                        // Check if wishlist is empty
                        const wishlistGrid = document.querySelector('.wishlist-grid');
                        if (wishlistGrid && !wishlistGrid.children.length) {
                            location.reload();
                        }
                    }
                } else {
                    showNotification(data.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Failed to add items to cart', 'error');
            }
        }
    };
    
    // Authentication helper functions
    const auth = {
        checkAuthentication: function(action) {
            if (!isAuthenticated) {
                return this.handleUnauthenticated(action);
            }
            return true;
        },

        handleUnauthenticated: function(action) {
            const message = `Please login to ${action}`;
            if (confirm(message)) {
                window.location.href = 'pages/login.php?required=true&redirect=' + encodeURIComponent(window.location.href);
            }
            return false;
        }
    };

    // Search functionality
    const search = {
        init: function() {
            const searchInput = document.querySelector('.search-bar input');
            let timeoutId;

            searchInput.addEventListener('input', (e) => {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => {
                    this.performSearch(e.target.value);
                }, 500);
            });
        },

        performSearch: async function(query) {
            if (query.length < 2) return;

            try {
                const response = await fetch(`includes/search.php?q=${encodeURIComponent(query)}`);
                const data = await response.json();
                this.displayResults(data);
            } catch (error) {
                console.error('Search failed:', error);
            }
        },

        displayResults: function(results) {
            const resultsContainer = document.querySelector('.search-results');
            if (!resultsContainer) return;

            resultsContainer.innerHTML = '';
            results.forEach(result => {
                const resultItem = document.createElement('div');
                resultItem.className = 'search-result-item';
                resultItem.innerHTML = `
                    <img src="assets/images/books/${result.image}" alt="${result.title}">
                    <div class="result-details">
                        <h4>${result.title}</h4>
                        <p>${result.author}</p>
                        <p class="price">$${result.price}</p>
                    </div>
                `;
                resultsContainer.appendChild(resultItem);
            });
        }
    };

    // Notification system
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 3000);
        }, 100);
    }
    

    // Initialize components
    bannerSlider.init();
    search.init();
    
    // Export necessary functions to global scope
    window.cart = cart;
    window.wishlist = wishlist;
    window.auth = auth;
});

// Global helper functions for use in HTML onclick attributes
function addToCart(bookId, quantity = 1) {
    window.cart.addToCart(bookId, quantity);
}

function toggleWishlist(bookId) {
    window.wishlist.toggleWishlist(bookId);
}

function initializeBookSliders() {
    const bookSections = document.querySelectorAll('.book-section');
    
    bookSections.forEach(section => {
        const grid = section.querySelector('.book-grid');
        const books = grid.children;
        const booksPerView = Math.floor(grid.offsetWidth / books[0].offsetWidth);
        let currentIndex = 0;

        // Create slider controls if needed
        if (books.length > booksPerView) {
            const prevButton = document.createElement('button');
            const nextButton = document.createElement('button');
            
            prevButton.className = 'slider-control prev';
            nextButton.className = 'slider-control next';
            prevButton.innerHTML = '<i class="fas fa-chevron-left"></i>';
            nextButton.innerHTML = '<i class="fas fa-chevron-right"></i>';

            section.appendChild(prevButton);
            section.appendChild(nextButton);

            // Add click handlers
            prevButton.addEventListener('click', () => {
                currentIndex = Math.max(currentIndex - booksPerView, 0);
                updateSlidePosition();
            });

            nextButton.addEventListener('click', () => {
                currentIndex = Math.min(currentIndex + booksPerView, books.length - booksPerView);
                updateSlidePosition();
            });

            function updateSlidePosition() {
                const offset = books[0].offsetWidth * currentIndex;
                grid.style.transform = `translateX(-${offset}px)`;
            }
        }
    });
}