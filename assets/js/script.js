// Optimized slideshow functionality
function slideshow(btn, n) {
  const container = btn.closest('.slideshow-container');
  const slides = container.querySelectorAll('.slide');
  let current = Array.from(slides).findIndex(s => s.style.display !== 'none');
  current = (current + n + slides.length) % slides.length;
  
  slides.forEach(s => s.style.display = 'none');
  slides[current].style.display = 'block';
  container.querySelector('.slide-num').textContent = current + 1;
}

// Hero slideshow functionality
class HeroSlideshow {
  constructor() {
    this.container = document.getElementById('home-hero');
    if (!this.container || !this.container.classList.contains('hero-slideshow')) return;
    
    this.slides = this.container.querySelectorAll('.hero-slide');
    this.indicators = this.container.querySelectorAll('.indicator');
    this.currentSlide = 0;
    this.autoPlayInterval = null;
    
    this.init();
  }
  
  init() {
    // Show first slide
    this.showSlide(0);
    
    // Add click listeners to indicators
    this.indicators.forEach((indicator, index) => {
      indicator.addEventListener('click', () => {
        clearInterval(this.autoPlayInterval);
        this.showSlide(index);
        this.startAutoPlay();
      });
    });
    
    // Start auto-play
    this.startAutoPlay();
  }
  
  showSlide(n) {
    this.slides.forEach(slide => slide.classList.remove('active'));
    this.indicators.forEach(indicator => indicator.classList.remove('active'));
    
    this.currentSlide = (n + this.slides.length) % this.slides.length;
    this.slides[this.currentSlide].classList.add('active');
    this.indicators[this.currentSlide].classList.add('active');
  }
  
  nextSlide() {
    this.showSlide(this.currentSlide + 1);
  }
  
  startAutoPlay() {
    this.autoPlayInterval = setInterval(() => this.nextSlide(), 5000);
  }
}

// Initialize AOS (Animate On Scroll) - runs once
document.addEventListener('DOMContentLoaded', () => {
  // Initialize hero slideshow
  new HeroSlideshow();
  
  AOS.init({
    duration: 800,
    easing: 'ease-in-out-quad',
    once: true,
    mirror: false,
    offset: 100,
    disable: false
  });
  
  // Initialize keyboard support for slideshows
  document.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
      const focused = document.activeElement.closest('.slideshow-container');
      if (focused) {
        const direction = e.key === 'ArrowLeft' ? -1 : 1;
        slideshow(focused.querySelector('.slide-btn'), direction);
      }
    }
  });
});

// Optimized scroll indicator handling
const scrollIndicator = document.querySelector('.scroll-indicator');
const scrollButton = document.querySelector(".go-top");
let scrollTimeout;

window.addEventListener('scroll', () => {
  clearTimeout(scrollTimeout);
  
  if (scrollIndicator && window.scrollY <= 100) {
    scrollIndicator.style.opacity = '0.7';
    scrollIndicator.style.pointerEvents = 'auto';
  } else if (scrollIndicator) {
    scrollIndicator.style.opacity = '0';
    scrollIndicator.style.pointerEvents = 'none';
  }
  
  // Show/hide scroll-to-top button
  scrollButton.style.display = window.scrollY > 100 ? 'flex' : 'none';
}, { passive: true });

// Scroll to top functionality
if (scrollButton) {
  scrollButton.addEventListener("click", () => {
    window.scrollTo({ top: 0, behavior: "smooth" });
  });
}

// Optimized smooth scroll for anchor links - delegated
document.addEventListener('click', (e) => {
  const anchor = e.target.closest('a[href^="#"]');
  if (!anchor) return;
  
  e.preventDefault();
  const targetId = anchor.getAttribute('href');
  if (targetId === '#') return;
  
  const targetElement = document.querySelector(targetId);
  if (targetElement) {
    const headerHeight = document.querySelector('nav')?.offsetHeight || 0;
    const targetPosition = targetElement.offsetTop - headerHeight;
    
    window.scrollTo({ top: targetPosition, behavior: 'smooth' });
    setTimeout(() => AOS.refresh(), 500);
  }
}, { passive: true });

// Optimize AOS refresh on resize with debounce
let resizeTimeout;
window.addEventListener('resize', () => {
  clearTimeout(resizeTimeout);
  resizeTimeout = setTimeout(() => AOS.refresh(), 250);
}, { passive: true });

