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

        init() {
            this.populateBooks();
            this.leftButton.addEventListener('click', () => this.scrollLeft());
            this.rightButton.addEventListener('click', () => this.scrollRight());
        },

        populateBooks() {
            const books = [
                { id: 1, title: "Book 1", price: "$19.99", rating: 4.5, image: "images/book2.jpg" },
                { id: 2, title: "Book 2", price: "$24.99", rating: 4.0, image: "images/book3.jpg" }
                // Add more books as needed
            ];

            books.forEach(book => {
                const bookElement = document.createElement('div');
                bookElement.className = 'book';
                bookElement.innerHTML = `
                    <img src="${book.image}" alt="${book.title}">
                    <h4>${book.title}</h4>
                    <p class="price">${book.price}</p>
                    <div class="rating">${'★'.repeat(Math.floor(book.rating))}${'☆'.repeat(5 - Math.floor(book.rating))}</div>
                    <button class="add-to-cart" data-book-id="${book.id}">Add to Cart</button>
                    <button class="add-to-wishlist" data-book-id="${book.id}">Add to Wishlist</button>
                `;
                this.container.appendChild(bookElement);
            });
        },

        scrollLeft() {
            this.scrollAmount = Math.max(this.scrollAmount - this.scrollStep, 0);
            this.container.scrollTo({ left: this.scrollAmount, behavior: 'smooth' });
        },

        scrollRight() {
            this.scrollAmount = Math.min(
                this.scrollAmount + this.scrollStep, 
                this.container.scrollWidth - this.container.clientWidth
            );
            this.container.scrollTo({ left: this.scrollAmount, behavior: 'smooth' });
        }
    };

    // Modal functionality
    const bookModal = {
    modal: document.getElementById('book-modal'),
    closeBtn: document.querySelector('.close'),
    addToCartBtn: document.getElementById('modal-add-to-cart'),
    addToWishlistBtn: document.getElementById('modal-add-to-wishlist'),

    init() {
        this.closeBtn.onclick = () => this.close();
        window.onclick = (event) => {
            if (event.target == this.modal) {
                this.close();
            }
        };

        document.querySelectorAll('.book').forEach(book => {
            book.addEventListener('click', () => this.open(book));
        });

        this.addToCartBtn.addEventListener('click', () => {
            const bookId = this.modal.getAttribute('data-book-id');
            bookActions.addToCart(bookId);
        });

        this.addToWishlistBtn.addEventListener('click', () => {
            const bookId = this.modal.getAttribute('data-book-id');
            bookActions.addToWishlist(bookId);
        });
    },

    open(book) {
        const bookId = book.getAttribute('data-book-id');
        const title = book.querySelector('h4').textContent;
        const img = book.querySelector('img').src;
        const price = book.querySelector('.price').textContent;
        const rating = book.querySelector('.rating').innerHTML;

        document.getElementById('modal-title').textContent = title;
        document.getElementById('modal-image').src = img;
        document.getElementById('modal-price').textContent = price;
        document.getElementById('modal-rating').innerHTML = rating;
        document.getElementById('modal-description').textContent = "TEST//PLACEHOLDER TEXT";

        this.modal.setAttribute('data-book-id', bookId);
        this.modal.style.display = 'block';
    },

    close() {
        this.modal.style.display = 'none';
    }
};

    // Wishlist and Cart functionality
    const bookActions = {
        init() {
            document.querySelectorAll('.add-to-wishlist').forEach(button => {
                button.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const bookId = e.currentTarget.getAttribute('data-book-id');
                    this.addToWishlist(bookId);
                });
            });

            document.querySelectorAll('.add-to-cart').forEach(button => {
                button.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const bookId = e.currentTarget.getAttribute('data-book-id');
                    this.addToCart(bookId);
                });
            });
        },

        addToWishlist(bookId) {
            let wishlist = JSON.parse(localStorage.getItem('wishlist')) || [];
            if (!wishlist.includes(bookId)) {
                wishlist.push(bookId);
                localStorage.setItem('wishlist', JSON.stringify(wishlist));
                const button = document.querySelector(`.add-to-wishlist[data-book-id="${bookId}"]`);
                button.style.color = '#4CAF50';
                alert('Book added to Wishlist!');
            } else {
                alert('Book is already in your Wishlist!');
            }
        },

        addToCart(bookId) {
            let cart = JSON.parse(localStorage.getItem('cart')) || [];
            if (!cart.includes(bookId)) {
                cart.push(bookId);
                localStorage.setItem('cart', JSON.stringify(cart));
                alert('Book added to Cart!');
            } else {
                alert('Book is already in your Cart!');
            }
        }
    };

    // Search functionality
    const search = {
        input: document.getElementById('searchInput'),
        clearButton: document.querySelector('.search-bar button'),

        init() {
            this.clearButton.addEventListener('click', () => this.clear());
        },

        clear() {
            this.input.value = '';
        }
    };
    
    // New login check functionality
    const loginCheck = {
        init() {
            const accountLink = document.querySelector('.user-actions a[href="profile1.php"]');
            
            if (accountLink) {
                accountLink.addEventListener('click', function(e) {
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
                });
            }
        }
    };

    // Initialize all components
    adCarousel.init();
    bookSlider.init();
    bookModal.init();
    bookActions.init();
    search.init();
    loginCheck.init();
});

function loadBanners() {
    fetch('get_banners.php')
        .then(response => response.json())
        .then(banners => {
            const container = document.querySelector('.ad-container');
            container.innerHTML = '';
            
            banners.forEach(banner => {
                const slide = document.createElement('div');
                slide.className = 'ad-slide';
                
                if (banner.type === 'image') {
                    slide.innerHTML = `
                        <img src="serve_image.php?type=banner&id=${banner.id}" 
                             alt="${banner.title}">
                    `;
                } else {
                    slide.innerHTML = `
                        <div class="promo-banner">
                            <h2>${banner.promo_heading}</h2>
                            <p>${banner.promo_text}</p>
                        </div>
                    `;
                }
                
                container.appendChild(slide);
            });
            
            initializeCarousel();
        });
}