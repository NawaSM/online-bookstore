document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('headerSearchInput');
    const searchResults = document.getElementById('searchResults');
    const headerSearchForm = document.getElementById('headerSearchForm');
    let searchTimeout;

    // Handle form submission
    headerSearchForm.addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent form submission
        const searchTerm = searchInput.value.trim();
        if (searchTerm) {
            performHeaderSearch(searchTerm);
        }
    });

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const searchTerm = this.value.trim();
        
        if (searchTerm.length > 0) {
            searchTimeout = setTimeout(() => performHeaderSearch(searchTerm), 300);
        } else {
            searchResults.style.display = 'none';
        }
    });

    // Close search results when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchResults.contains(e.target) && !searchInput.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });

   function performHeaderSearch(searchTerm) {
        const searchUrl = `/onlinebookstore/assets/api/search_books.php?q=${encodeURIComponent(searchTerm)}`;
        console.log('Searching:', searchUrl); // Debug log

        fetch(searchUrl)
            .then(response => {
                console.log('Response status:', response.status); // Debug log
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text(); // Change to text() first for debugging
            })
            .then(text => {
                console.log('Raw response:', text); // Debug log
                try {
                    const data = JSON.parse(text);
                    displayHeaderResults(data.books);
                } catch (e) {
                    console.error('JSON parsing error:', e);
                    throw new Error('Invalid JSON response');
                }
            })
            .catch(error => {
                console.error('Search error:', error);
                searchResults.innerHTML = '<div class="no-results">An error occurred while searching</div>';
                searchResults.style.display = 'block';
            });
    }

    function displayHeaderResults(books) {
        if (!books || books.length === 0) {
            searchResults.innerHTML = '<div class="no-results">No books found</div>';
        } else {
            searchResults.innerHTML = books.map(book => createHeaderBookCard(book)).join('');
        }
        searchResults.style.display = 'block';
    }

    function createHeaderBookCard(book) {
        const statusDisplay = book.status !== 'regular' ? 
            `<span class="book-status ${book.status}">${book.status.replace('_', ' ')}</span>` : '';

        const specialPrice = book.is_special && book.special_price 
            ? `<span class="original-price">RM${book.price}</span>
               <span class="special-price">RM${book.special_price}</span>`
            : `<span class="price">RM${book.price}</span>`;

        const imageUrl = book.image_data && book.image_type 
            ? `data:${book.image_type};base64,${book.image_data}`
            : '/onlinebookstore/assets/images/book-placeholder.jpg';

        return `
            <div class="search-book-card">
                <div class="search-book-image">
                    <img src="${imageUrl}" 
                         alt="${book.book_name}"
                         onerror="this.src='/onlinebookstore/assets/images/book-placeholder.jpg'">
                </div>
                <div class="search-book-info">
                    <a href="/onlinebookstore/pages/books/details.php?id=${book.id}" class="book-title">
                        ${book.book_name}
                    </a>
                    <p class="book-author">by ${book.author}</p>
                    ${statusDisplay}
                    <div class="book-price">${specialPrice}</div>
                </div>
            </div>
        `;
    }
});