document.addEventListener('DOMContentLoaded', function() {
    // Add animation when orders load
    const orderCards = document.querySelectorAll('.order-card');
    orderCards.forEach((card, index) => {
        card.style.animation = `fadeIn 0.3s ease forwards ${index * 0.1}s`;
        card.style.opacity = '0';
    });
});

