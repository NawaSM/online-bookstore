let wishlist = []; // This should be populated with your product data

function displayWishlist() {
    const wishlistContainer = document.getElementById('wishlist');
    wishlistContainer.innerHTML = '';
    wishlist.forEach((item, index) => {
        const itemElement = document.createElement('div');
        itemElement.className = 'item';
        itemElement.innerHTML = `
            <input type="checkbox" id="item-${index}">
            <img src="${item.image}" alt="${item.name}">
            <h3>${item.name}</h3>
            <p>$${item.price}</p>
            <div class="item-details">
                <p>${item.details}</p>
            </div>
        `;
        itemElement.querySelector('img').onclick = () => {
            const details = itemElement.querySelector('.item-details');
            details.style.display = details.style.display === 'block' ? 'none' : 'block';
        };
        wishlistContainer.appendChild(itemElement);
    });
    updateWishlistCount();
}

function updateWishlistCount() {
    document.getElementById('item-count').textContent = wishlist.length;
}

function filterWishlist() {
    const query = document.getElementById('search').value.toLowerCase();
    const items = document.querySelectorAll('.item');
    items.forEach(item => {
        const name = item.querySelector('h3').textContent.toLowerCase();
        item.style.display = name.includes(query) ? 'block' : 'none';
    });
}

function toggleSortMenu() {
    const menu = document.getElementById('sort-menu');
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
}

function sortWishlist(criteria) {
    if (criteria === 'fiction') {
        wishlist.sort((a, b) => a.name.localeCompare(b.name)); // Example sorting
    } else if (criteria === 'nonFiction') {
        // Your sorting logic here
    } else if (criteria === 'recent') {
        // Sort by recently added logic
    } else if (criteria === 'oldest') {
        // Sort by oldest added logic
    }
    displayWishlist();
    toggleSortMenu();
}

function shareWishlist() {
    const shareLinks = [
        `https://www.facebook.com/sharer/sharer.php?u=${window.location.href}`,
        `https://wa.me/?text=Check out my wishlist: ${window.location.href}`,
        `mailto:?subject=My Wishlist&body=Check out my wishlist: ${window.location.href}`,
        `https://www.instagram.com/`
    ];
    const platform = prompt("Share via: 1. Facebook, 2. WhatsApp, 3. Email, 4. Instagram (Type 1, 2, 3, or 4)");
    if (platform >= 1 && platform <= shareLinks.length) {
        window.open(shareLinks[platform - 1], '_blank');
    }
}

function removeSelected() {
    const items = document.querySelectorAll('.item input[type="checkbox"]');
    items.forEach((item, index) => {
        if (item.checked) {
            wishlist.splice(index, 1);
        }
    });
    displayWishlist();
}

function addSelectedToCart() {
    // Implement add to cart logic here
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
const accountLink = document.getElementById('accountLink');
    if (accountLink) {
        accountLink.addEventListener('click', checkLogin);
    }

    // Initial display of the wishlist
    displayWishlist();
});

t.getElementById('share-email').addEventListener('click', () => shareWishlist('email'));