document.addEventListener('DOMContentLoaded', () => {
    // Ad Carousel Section
    const adCarousel = {
        slides: [],
        container: document.querySelector('.ad-container'),
        prevButton: document.querySelector('.nav-button.prev'),
        nextButton: document.querySelector('.nav-button.next'),
        currentIndex: 0,
        intervalId: null,

        init() {
            this.loadBanners();
            this.prevButton.addEventListener('click', () => this.prevSlide());
            this.nextButton.addEventListener('click', () => this.nextSlide());

            const carouselElement = document.querySelector('.ad-carousel');
            carouselElement.addEventListener('mouseenter', () => this.stopAutoScroll());
            carouselElement.addEventListener('mouseleave', () => this.startAutoScroll());
        },

        loadBanners() {
            fetch('get_banners.php')
                .then(response => response.json())
                .then(banners => {
                    this.container.innerHTML = '';
                    banners.forEach((banner, index) => {
                        const slide = document.createElement('div');
                        slide.className = 'ad-slide' + (index === 0 ? ' active' : '');
                        
                        if (banner.type === 'image') {
                            slide.innerHTML = `
                                <img src="serve_image.php?type=banner&id=${banner.id}" 
                                    alt="${banner.title}">
                            `;
                        } else { // promo type
                            slide.innerHTML = `
                                <div class="promo-banner">
                                    <h2>${banner.promo_heading}</h2>
                                    <p>${banner.promo_text}</p>
                                    <span class="free-delivery">FREE DELIVERY</span>
                                    <span class="rm1-rebate">RM1 REBATE</span>
                                </div>
                            `;
                        }
                        
                        this.container.appendChild(slide);
                    });
                    
                    this.slides = document.querySelectorAll('.ad-slide');
                    if (this.slides.length > 0) {
                        this.showSlide(0);
                        this.startAutoScroll();
                    }
                })
                .catch(error => console.error('Error loading banners:', error));
        },

        showSlide(index) {
            this.slides.forEach((slide, i) => {
                slide.classList.toggle('active', i === index);
            });
        },

        nextSlide() {
            this.currentIndex = (this.currentIndex + 1) % this.slides.length;
            this.showSlide(this.currentIndex);
        },

        prevSlide() {
            this.currentIndex = (this.currentIndex - 1 + this.slides.length) % this.slides.length;
            this.showSlide(this.currentIndex);
        },

        startAutoScroll() {
            if (this.slides.length > 1) {
                this.intervalId = setInterval(() => this.nextSlide(), 5000);
            }
        },

        stopAutoScroll() {
            clearInterval(this.intervalId);
        }
    };

    // Book Slider Section
    const bookSlider = {
        container: document.getElementById('bookSlider'),
        leftButton: document.querySelector('.slider-button.left'),
        rightButton: document.querySelector('.slider-button.right'),
        scrollAmount: 0,
        scrollStep: 200,
        bookSections: document.querySelectorAll('.book-slider'),

        init() {
            console.log('Initializing book slider');
            this.loadAllSections();
            this.initializeSliderControls();
            this.initializeLoginCheck();
        },

        loadAllSections() {
            // First load the bestseller section
            if (this.container) {
                console.log('Loading bestseller section');
                this.loadBooks(this.container, 'bestseller');
            }

            // Then load other sections
            this.bookSections.forEach(section => {
                if (section.id !== 'bookSlider') {  // Skip the bestseller section as it's already loaded
                    const category = section.getAttribute('data-category');
                    if (category) {
                        console.log(`Loading section for category: ${category}`);
                        this.loadBooks(section, category);
                    }
                }
            });
        },

        loadBooks(container, category) {
            console.log(`Loading books for category: ${category}`);
            container.innerHTML = '<div class="loading-spinner">Loading books...</div>';

            fetch(`get_books.php?filter=${category}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    console.log(`Response status for ${category}:`, response.status);
                    return response.text(); // First get the raw text
                })
                .then(text => {
                    console.log('Raw response:', text); // Log the raw response
                    try {
                        return JSON.parse(text); // Then try to parse it
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        throw new Error('Invalid JSON response');
                    }
                })
                .then(result => {
                    console.log(`Books data for ${category}:`, result);

                    if (result.success && result.data && Array.isArray(result.data)) {
                        if (result.data.length > 0) {
                            container.innerHTML = '';
                            result.data.forEach(book => {
                                const bookElement = this.createBookElement(book);
                                container.appendChild(bookElement);
                            });
                        } else {
                            container.innerHTML = `<p class="no-books-message">No ${category} books available</p>`;
                        }
                    } else {
                        console.error('Invalid data structure:', result);
                        container.innerHTML = `<p class="error-message">Error loading books</p>`;
                    }
                })
                .catch(error => {
                    console.error(`Error loading ${category} books:`, error);
                    container.innerHTML = `<p class="error-message">Error loading books: ${error.message}</p>`;
                });
        },

        createBookElement(book) {
            const bookElement = document.createElement('div');
            bookElement.className = 'book';
            bookElement.setAttribute('data-book-id', book.id);

            const price = book.is_special ? book.special_price_formatted : book.price_formatted;

            bookElement.innerHTML = `
                <div class="book-image">
                    <img src="${book.image_url}" 
                        alt="${book.book_name}" 
                        onerror="this.onerror=null; this.src='images/default-book-cover.jpg';"
                        loading="lazy">
                </div>
                <div class="book-info">
                    <h4 class="book-title">${book.book_name}</h4>
                    <p class="price">${price}</p>
                    <div class="book-buttons">
                        <button class="add-to-cart" data-book-id="${book.id}">Add to Cart</button>
                        <button class="add-to-wishlist" data-book-id="${book.id}">Add to Wishlist</button>
                    </div>
                </div>
            `;

            // Add click event for showing details modal
            bookElement.addEventListener('click', (e) => {
                if (!e.target.classList.contains('add-to-cart') && 
                    !e.target.classList.contains('add-to-wishlist')) {
                    this.showBookDetails(book);
                }
            });

            // Add button click events
            const cartBtn = bookElement.querySelector('.add-to-cart');
            const wishlistBtn = bookElement.querySelector('.add-to-wishlist');

            cartBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.handleCartAction(book.id);
            });

            wishlistBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.handleWishlistAction(book.id);
            });

            return bookElement;
        },

        showBookDetails(book) {
            const modal = document.getElementById('book-modal');
            const modalContent = modal.querySelector('.modal-content');

            modalContent.innerHTML = `
                <span class="close">&times;</span>
                <div class="modal-book-details">
                    <div class="book-image">
                        <img src="${book.image_url}" alt="${book.book_name}">
                    </div>
                    <div class="book-info">
                        <h2>${book.book_name}</h2>
                        <p class="author">By ${book.author}</p>
                        <p class="isbn">ISBN: ${book.isbn}</p>
                        <p class="price">Price: ${book.price_formatted}</p>
                        ${book.is_special ? `<p class="special-price">Special Price: ${book.special_price_formatted}</p>` : ''}
                        <p class="category">Category: ${book.category_name}</p>
                        <p class="genres">Genres: ${book.genres.join(', ')}</p>
                        <p class="release-year">Release Year: ${book.release_year}</p>
                        <p class="status">Status: ${book.status}</p>
                        <div class="modal-buttons">
                            <button class="add-to-cart" data-book-id="${book.id}">Add to Cart</button>
                            <button class="add-to-wishlist" data-book-id="${book.id}">Add to Wishlist</button>
                        </div>
                    </div>
                </div>
            `;

            // Add event listeners for modal buttons
            const modalCartBtn = modalContent.querySelector('.add-to-cart');
            const modalWishlistBtn = modalContent.querySelector('.add-to-wishlist');

            modalCartBtn.addEventListener('click', () => this.handleCartAction(book.id));
            modalWishlistBtn.addEventListener('click', () => this.handleWishlistAction(book.id));

            // Show modal and setup close functionality
            modal.style.display = 'block';
            
            const closeBtn = modalContent.querySelector('.close');
            closeBtn.onclick = () => modal.style.display = 'none';
            
            window.onclick = (event) => {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            };
        },

        initializeSliderControls() {
            this.bookSections.forEach(section => {
                const leftBtn = section.parentElement.querySelector('.slider-button.left');
                const rightBtn = section.parentElement.querySelector('.slider-button.right');

                if (leftBtn) {
                    leftBtn.addEventListener('click', () => this.scrollLeft(section));
                }
                if (rightBtn) {
                    rightBtn.addEventListener('click', () => this.scrollRight(section));
                }
            });
        },

        scrollLeft(section) {
            section.scrollBy({
                left: -this.scrollStep,
                behavior: 'smooth'
            });
        },

        scrollRight(section) {
            section.scrollBy({
                left: this.scrollStep,
                behavior: 'smooth'
            });
        },

        async handleCartAction(bookId) {
            const isLoggedIn = await this.checkLogin();
            if (!isLoggedIn) {
                window.location.href = 'login.php';
                return;
            }

            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ book_id: bookId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Book added to cart successfully!');
                } else {
                    alert(data.message || 'Error adding book to cart');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding book to cart');
            });
        },

        async handleWishlistAction(bookId) {
            const isLoggedIn = await this.checkLogin();
            if (!isLoggedIn) {
                window.location.href = 'login.php';
                return;
            }

            fetch('add_to_wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ book_id: bookId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Book added to wishlist successfully!');
                } else {
                    alert(data.message || 'Error adding book to wishlist');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding book to wishlist');
            });
        },

        checkLogin() {
            return fetch('check_login.php')
                .then(response => response.json())
                .then(data => data.loggedIn)
                .catch(() => false);
        },

        initializeLoginCheck() {
            document.querySelectorAll('.add-to-cart, .add-to-wishlist').forEach(button => {
                button.addEventListener('click', async (e) => {
                    e.preventDefault();
                    const isLoggedIn = await this.checkLogin();
                    if (!isLoggedIn) {
                        window.location.href = 'login.php';
                    }
                });
            });
        }
    };

    // Search functionality
    const search = {
        input: document.getElementById('searchInput'),
        clearButton: document.querySelector('.search-bar button'),

        init() {
            this.input.addEventListener('input', (e) => this.handleSearch(e.target.value));
            this.clearButton.addEventListener('click', () => this.clear());
        },

        handleSearch(query) {
            if (query.length >= 3) {
                fetch(`search_books.php?query=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(result => {
                        console.log('Search results:', result);
                    })
                    .catch(error => console.error('Error searching:', error));
            }
        },

        clear() {
            this.input.value = '';
        }
    };

    // Initialize all components
    adCarousel.init();
    bookSlider.init();
    search.init();
});