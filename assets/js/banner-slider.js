/* js/banner-slider.js */
class BannerSlider {
    constructor() {
        this.currentSlide = 0;
        this.bannerContainer = document.querySelector('.banner-container');
        this.slides = document.querySelectorAll('.banner-slide');
        this.prevButton = document.querySelector('.banner-prev');
        this.nextButton = document.querySelector('.banner-next');
        this.interval = null;
        this.autoplayDelay = 5000; // 5 seconds

        if (this.slides.length > 0) {
            this.init();
        }
    }

    init() {
        // Show first slide
        this.showSlide(0);

        // Add event listeners
        this.prevButton?.addEventListener('click', () => this.prevSlide());
        this.nextButton?.addEventListener('click', () => this.nextSlide());
        
        // Touch events for mobile
        let touchStartX = 0;
        let touchEndX = 0;
        
        this.bannerContainer?.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        });

        this.bannerContainer?.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            this.handleSwipe(touchStartX, touchEndX);
        });

        // Pause on hover
        this.bannerContainer?.addEventListener('mouseenter', () => this.pauseAutoplay());
        this.bannerContainer?.addEventListener('mouseleave', () => this.startAutoplay());

        // Start autoplay
        this.startAutoplay();
    }

    showSlide(index) {
        this.slides.forEach(slide => {
            slide.classList.remove('active');
        });
        
        this.slides[index].classList.add('active');
        this.currentSlide = index;
    }

    nextSlide() {
        const next = (this.currentSlide + 1) % this.slides.length;
        this.showSlide(next);
    }

    prevSlide() {
        const prev = (this.currentSlide - 1 + this.slides.length) % this.slides.length;
        this.showSlide(prev);
    }

    handleSwipe(startX, endX) {
        const diff = startX - endX;
        if (Math.abs(diff) > 50) { // Minimum swipe distance
            if (diff > 0) {
                this.nextSlide();
            } else {
                this.prevSlide();
            }
        }
    }

    startAutoplay() {
        if (!this.interval) {
            this.interval = setInterval(() => this.nextSlide(), this.autoplayDelay);
        }
    }

    pauseAutoplay() {
        if (this.interval) {
            clearInterval(this.interval);
            this.interval = null;
        }
    }
}

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', () => {
    new BannerSlider();
});