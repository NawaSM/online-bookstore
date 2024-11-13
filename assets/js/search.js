document.addEventListener('DOMContentLoaded', function() {
    
    const searchForm = document.getElementById('searchForm');
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const genreFilter = document.getElementById('genreFilter');
    const resultsContainer = document.getElementById('searchResults');
    
    // Advanced Search Elements
    const toggleAdvancedBtn = document.getElementById('toggleAdvanced');
    const advancedFilters = document.querySelector('.advanced-filters');
    const minPriceInput = document.getElementById('minPrice');
    const maxPriceInput = document.getElementById('maxPrice');
    const inStockCheckbox = document.getElementById('inStockOnly');
    const isbnInput = document.getElementById('isbnSearch');

    let searchTimeout;

    // Toggle Advanced Search
    toggleAdvancedBtn?.addEventListener('click', function() {
        advancedFilters.style.display = advancedFilters.style.display === 'none' ? 'block' : 'none';
        toggleAdvancedBtn.textContent = advancedFilters.style.display === 'none' ? 
            'Show Advanced Filters' : 'Hide Advanced Filters';
    });

    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        performSearch();
    });

    // Live search with debouncing
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(performSearch, 500);
    });

    // Event listeners for filters
    [categoryFilter, genreFilter].forEach(filter => {
        filter.addEventListener('change', performSearch);
    });

    // Advanced filter event listeners
    [minPriceInput, maxPriceInput, isbnInput].forEach(input => {
        input?.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(performSearch, 500);
        });
    });

    inStockCheckbox?.addEventListener('change', performSearch);

    // In search.js, modify the performSearch function
    function performSearch() {
        const searchTerm = searchInput.value;
        const category = categoryFilter.value;
        const genre = genreFilter.value;

        // Advanced search parameters
        const minPrice = minPriceInput?.value || '';
        const maxPrice = maxPriceInput?.value || '';
        const inStock = inStockCheckbox?.checked || false;
        const isbn = isbnInput?.value || '';

        const url = `/onlinebookstore/assets/api/search-books.php?q=${encodeURIComponent(searchTerm)}` +
            `&category=${encodeURIComponent(category)}` +
            `&genre=${encodeURIComponent(genre)}` +
            `&min_price=${encodeURIComponent(minPrice)}` +
            `&max_price=${encodeURIComponent(maxPrice)}` +
            `&in_stock=${inStock ? 1 : 0}` +
            `&isbn=${encodeURIComponent(isbn)}`;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                displayResults(data.books);
            })
            .catch(error => console.error('Error:', error));
    }

    function displayResults(books) {
        resultsContainer.innerHTML = '';
        
        if (!books || books.length === 0) {
            resultsContainer.innerHTML = '<p class="no-results">No books found.</p>';
            return;
        }

        books.forEach(book => {
            const bookCard = createBookCard(book);
            resultsContainer.appendChild(bookCard);
        });
    }

    function createBookCard(book) {
        const card = document.createElement('div');
        card.className = 'book-card';

        const bookLink = `/onlinebookstore/pages/books/details.php?id=${book.id}`;

        card.innerHTML = `
            ${book.status !== 'regular' ? `
                <span class="status-badge status-${book.status}">
                    ${book.status.replace('_', ' ').toUpperCase()}
                </span>
            ` : ''}
            <a href="${bookLink}" class="book-link">
                <img src="data:${book.image_type};base64,${book.image_data}" 
                     alt="${book.book_name}" 
                     onerror="this.src='/onlinebookstore/assets/images/book-placeholder.jpg'">
                <h3>${book.book_name}</h3>
            </a>
            <p class="author">By ${book.author}</p>
            <p class="price">RM${parseFloat(book.price).toFixed(2)}</p>
            <div class="genres">
                ${book.genres.map(genre => `
                    <span class="genre-tag">${genre}</span>
                `).join('')}
            </div>
            <div class="category">
                <span class="category-tag">${book.category}</span>
            </div>
            <div class="book-actions">
                <button onclick="addToCart(${book.id})" class="add-to-cart">
                    <i class="fas fa-shopping-cart"></i> Add to Cart
                </button>
                <button onclick="addToWishlist(${book.id})" class="add-to-wishlist">
                    <i class="fas fa-heart"></i>
                </button>
            </div>
        `;

        return card;
    }
});

// Add to Cart function
function addToCart(bookId) {
    const baseUrl = document.querySelector('base')?.href || window.location.origin + '/onlinebookstore/';
    
    fetch(`${baseUrl}api/add-to-cart.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ book_id: bookId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart count in header
            const cartCount = document.querySelector('.cart-count');
            if (cartCount) {
                cartCount.textContent = data.cart_count;
            }
            alert('Book added to cart!');
        } else {
            if (data.redirect) {
                window.location.href = `${baseUrl}pages/login.php`;
            } else {
                alert(data.message || 'Error adding book to cart');
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

// Add to Wishlist function
function addToWishlist(bookId) {
    const baseUrl = document.querySelector('base')?.href || window.location.origin + '/onlinebookstore/';
    
    fetch(`${baseUrl}api/add-to-wishlist.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ book_id: bookId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Book added to wishlist!');
        } else {
            if (data.redirect) {
                window.location.href = `${baseUrl}pages/login.php`;
            } else {
                alert(data.message || 'Error adding book to wishlist');
            }
        }
    })
    .catch(error => console.error('Error:', error));
}