// Main JavaScript for NewsPower Website

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all functionality
    initializeNavbar();
    initializeBackToTop();
    initializeSocialShare();
    initializeNewsletter();
    initializeViewTracking();
    initializeLazyLoading();
    initializeAnimations();
});

// Navbar functionality
function initializeNavbar() {
    const navbar = document.querySelector('.navbar');
    if (!navbar) return;
    
    // Add shadow on scroll
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            navbar.classList.add('shadow');
        } else {
            navbar.classList.remove('shadow');
        }
    });
    
    // Close mobile menu when clicking on links
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
    const navbarCollapse = document.querySelector('.navbar-collapse');
    
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (navbarCollapse.classList.contains('show')) {
                const bsCollapse = new bootstrap.Collapse(navbarCollapse);
                bsCollapse.hide();
            }
        });
    });
}

// Back to top button
function initializeBackToTop() {
    const backToTopBtn = document.getElementById('backToTop');
    if (!backToTopBtn) return;
    
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTopBtn.style.display = 'block';
            backToTopBtn.style.opacity = '1';
        } else {
            backToTopBtn.style.display = 'none';
            backToTopBtn.style.opacity = '0';
        }
    });
    
    backToTopBtn.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

// Social sharing functionality
function initializeSocialShare() {
    // Add click event listeners to social share buttons
    document.addEventListener('click', function(e) {
        if (e.target.closest('.social-share a')) {
            e.preventDefault();
            const button = e.target.closest('.social-share a');
            const platform = getPlatformFromClass(button.className);
            
            if (platform) {
                handleSocialShare(platform, button);
            }
        }
    });
}

function getPlatformFromClass(className) {
    if (className.includes('facebook')) return 'facebook';
    if (className.includes('twitter')) return 'twitter';
    if (className.includes('linkedin')) return 'linkedin';
    if (className.includes('whatsapp')) return 'whatsapp';
    if (className.includes('copy-link')) return 'copy';
    return null;
}

function handleSocialShare(platform, button) {
    const url = window.location.href;
    const title = document.title;
    const postId = getPostIdFromPage();
    
    // Track the share
    if (postId) {
        trackShare(postId, platform);
    }
    
    // Perform the share
    switch (platform) {
        case 'facebook':
            shareOnFacebook(url, title);
            break;
        case 'twitter':
            shareOnTwitter(url, title);
            break;
        case 'linkedin':
            shareOnLinkedIn(url, title);
            break;
        case 'whatsapp':
            shareOnWhatsApp(url, title);
            break;
        case 'copy':
            copyToClipboard(url);
            break;
    }
}

function shareOnFacebook(url, title) {
    const shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}&quote=${encodeURIComponent(title)}`;
    window.open(shareUrl, 'facebook-share-dialog', 'width=626,height=436');
}

function shareOnTwitter(url, title) {
    const shareUrl = `https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title)}`;
    window.open(shareUrl, 'twitter-share-dialog', 'width=626,height=436');
}

function shareOnLinkedIn(url, title) {
    const shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(url)}`;
    window.open(shareUrl, 'linkedin-share-dialog', 'width=626,height=436');
}

function shareOnWhatsApp(url, title) {
    const text = `${title} ${url}`;
    const shareUrl = `https://wa.me/?text=${encodeURIComponent(text)}`;
    window.open(shareUrl, '_blank');
}

function copyToClipboard(url) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
            showToast('Link copied to clipboard!', 'success');
        }).catch(() => {
            fallbackCopyToClipboard(url);
        });
    } else {
        fallbackCopyToClipboard(url);
    }
}

function fallbackCopyToClipboard(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        document.execCommand('copy');
        showToast('Link copied to clipboard!', 'success');
    } catch (err) {
        showToast('Failed to copy link', 'error');
    }
    
    document.body.removeChild(textArea);
}

function trackShare(postId, platform) {
    fetch('/api/share.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            post_id: postId,
            platform: platform
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.shares_count) {
            updateShareCount(data.shares_count);
        }
    })
    .catch(error => {
        console.log('Share tracking failed:', error);
    });
}

function updateShareCount(count) {
    const shareCountElements = document.querySelectorAll('.shares-count');
    shareCountElements.forEach(element => {
        element.textContent = count;
    });
}

function getPostIdFromPage() {
    const postElement = document.querySelector('[data-post-id]');
    if (postElement) {
        return postElement.dataset.postId;
    }
    
    // Try to get from URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('id') || null;
}

// Newsletter functionality
function initializeNewsletter() {
    const newsletterForms = document.querySelectorAll('.newsletter-form, form[action*="newsletter"]');
    
    newsletterForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            handleNewsletterSubmission(this);
        });
    });
}

function handleNewsletterSubmission(form) {
    const emailInput = form.querySelector('input[type="email"]');
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    
    if (!emailInput || !emailInput.value.trim()) {
        showToast('Please enter your email address', 'error');
        return;
    }
    
    if (!isValidEmail(emailInput.value)) {
        showToast('Please enter a valid email address', 'error');
        return;
    }
    
    // Show loading state
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Subscribing...';
    submitBtn.disabled = true;
    
    const formData = new FormData(form);
    
    fetch('/api/newsletter.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            form.reset();
        } else {
            showToast(data.error || 'Subscription failed', 'error');
        }
    })
    .catch(error => {
        console.error('Newsletter error:', error);
        showToast('An error occurred. Please try again.', 'error');
    })
    .finally(() => {
        submitBtn.innerHTML = originalBtnText;
        submitBtn.disabled = false;
    });
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// View tracking
function initializeViewTracking() {
    // Track page views for posts
    if (window.location.pathname.includes('post.php')) {
        const postId = getPostIdFromPage();
        if (postId) {
            trackPageView(postId);
        }
    }
}

function trackPageView(postId) {
    fetch('/api/view-counter.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            post_id: postId
        })
    })
    .catch(error => {
        console.log('View tracking failed:', error);
    });
}

// Lazy loading for images
function initializeLazyLoading() {
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }
}

// Animation on scroll
function initializeAnimations() {
    if ('IntersectionObserver' in window) {
        const animationObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in');
                }
            });
        }, {
            threshold: 0.1
        });
        
        document.querySelectorAll('.card, .section-title').forEach(el => {
            animationObserver.observe(el);
        });
    }
}

// Toast notifications
function showToast(message, type = 'info') {
    const toastContainer = getOrCreateToastContainer();
    const toast = createToastElement(message, type);
    
    toastContainer.appendChild(toast);
    
    // Trigger animation
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        removeToast(toast);
    }, 5000);
}

function getOrCreateToastContainer() {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 350px;
        `;
        document.body.appendChild(container);
    }
    return container;
}

function createToastElement(message, type) {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade`;
    toast.style.cssText = `
        margin-bottom: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border: none;
        transform: translateX(100%);
        transition: transform 0.3s ease;
    `;
    
    const icon = type === 'success' ? 'check-circle' : 
                 type === 'error' ? 'exclamation-triangle' : 'info-circle';
    
    toast.innerHTML = `
        <i class="fas fa-${icon} me-2"></i>
        ${message}
        <button type="button" class="btn-close" onclick="removeToast(this.parentElement)"></button>
    `;
    
    return toast;
}

function removeToast(toast) {
    toast.style.transform = 'translateX(100%)';
    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 300);
}

// Form validation helpers
function validateForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

// Search functionality
function initializeSearch() {
    const searchForms = document.querySelectorAll('form[action*="blog.php"]');
    
    searchForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const searchInput = this.querySelector('input[name="search"]');
            if (searchInput && !searchInput.value.trim()) {
                e.preventDefault();
                searchInput.focus();
                showToast('Please enter a search term', 'error');
            }
        });
    });
}

// Initialize search on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeSearch();
});

// Utility functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function throttle(func, delay) {
    let timeoutId;
    let lastExecTime = 0;
    return function (...args) {
        const currentTime = Date.now();
        
        if (currentTime - lastExecTime > delay) {
            func.apply(this, args);
            lastExecTime = currentTime;
        } else {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => {
                func.apply(this, args);
                lastExecTime = Date.now();
            }, delay - (currentTime - lastExecTime));
        }
    };
}

// Performance optimization
window.addEventListener('load', function() {
    // Remove loading states
    document.body.classList.add('loaded');
    
    // Prefetch important pages
    const importantLinks = document.querySelectorAll('a[href*="/blog.php"], a[href*="/about.php"], a[href*="/contact.php"]');
    if ('requestIdleCallback' in window) {
        requestIdleCallback(() => {
            importantLinks.forEach(link => {
                const prefetchLink = document.createElement('link');
                prefetchLink.rel = 'prefetch';
                prefetchLink.href = link.href;
                document.head.appendChild(prefetchLink);
            });
        });
    }
});