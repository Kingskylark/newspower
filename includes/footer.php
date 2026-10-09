<!-- Footer -->
<footer class="bg-dark text-white mt-5">
    <div class="container py-5">
        <div class="row">
            <!-- About Section -->
            <div class="col-lg-4 col-md-6 mb-4">
                <h5 class="text-primary mb-3">
                    <i class="fas fa-newspaper me-2"></i>
                    <?php echo get_setting('site_name', 'Newspower'); ?>
                </h5>
                <p class="mb-3">
                    <?php echo get_setting('site_description', 'Newspower is a leading online news platform dedicated to delivering accurate, timely, and insightful news coverage to our readers.'); ?>
                </p>
                
                <!-- Social Media Links -->
                <div class="d-flex gap-3">
                    <?php if (get_setting('social_facebook')): ?>
                    <a href="<?php echo get_setting('social_facebook'); ?>" target="_blank" class="text-white">
                        <i class="fab fa-facebook-f fa-lg"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (get_setting('social_twitter')): ?>
                    <a href="<?php echo get_setting('social_twitter'); ?>" target="_blank" class="text-white">
                        <i class="fab fa-twitter fa-lg"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (get_setting('social_linkedin')): ?>
                    <a href="<?php echo get_setting('social_linkedin'); ?>" target="_blank" class="text-white">
                        <i class="fab fa-linkedin-in fa-lg"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (get_setting('social_instagram')): ?>
                    <a href="<?php echo get_setting('social_instagram'); ?>" target="_blank" class="text-white">
                        <i class="fab fa-instagram fa-lg"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6 mb-4">
                <h5 class="text-secondary mb-3">Quick Links</h5>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <a href="<?php echo SITE_URL; ?>" class="text-white text-decoration-none">
                            <i class="fas fa-home me-2"></i>Home
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="<?php echo SITE_URL; ?>/blog.php" class="text-white text-decoration-none">
                            <i class="fas fa-newspaper me-2"></i>News
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="<?php echo SITE_URL; ?>/about.php" class="text-white text-decoration-none">
                            <i class="fas fa-info-circle me-2"></i>About Us
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="<?php echo SITE_URL; ?>/contact.php" class="text-white text-decoration-none">
                            <i class="fas fa-envelope me-2"></i>Contact
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Categories -->
            <div class="col-lg-3 col-md-6 mb-4">
                <h5 class="text-secondary mb-3">Categories</h5>
                <ul class="list-unstyled">
                    <?php 
                    $footer_categories = get_categories();
                    foreach (array_slice($footer_categories, 0, 6) as $category): 
                    ?>
                    <li class="mb-2">
                        <a href="<?php echo SITE_URL; ?>/blog.php?category=<?php echo urlencode($category['slug']); ?>" class="text-white text-decoration-none">
                              <?php echo htmlspecialchars($category['name']); ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <!-- Contact Info -->
            <div class="col-lg-3 col-md-6 mb-4">
                <h5 class="text-secondary mb-3">Contact Info</h5>
                
                <?php if (get_setting('contact_email')): ?>
                <div class="mb-3">
                    <i class="fas fa-envelope me-2 text-primary"></i>
                    <a href="mailto:<?php echo get_setting('contact_email'); ?>" class="text-white text-decoration-none">
                        <?php echo get_setting('contact_email'); ?>
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if (get_setting('contact_phone')): ?>
                <div class="mb-3">
                    <i class="fas fa-phone me-2 text-primary"></i>
                    <a href="tel:<?php echo get_setting('contact_phone'); ?>" class="text-white text-decoration-none">
                        <?php echo get_setting('contact_phone'); ?>
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if (get_setting('contact_address')): ?>
                <div class="mb-3">
                    <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                    <span><?php echo nl2br(htmlspecialchars(get_setting('contact_address'))); ?></span>
                </div>
                <?php endif; ?>
                
                <!-- Newsletter Signup -->
                <div class="mt-4">
                    <h6 class="text-secondary mb-2">Stay Updated</h6>
                    <form action="<?php echo SITE_URL; ?>/api/newsletter.php" method="POST" class="newsletter-form">
                        <div class="input-group input-group-sm">
                            <input type="email" class="form-control" placeholder="Your email" name="email" required>
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <hr class="my-4">
        
        <!-- Bottom Footer -->
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="mb-0">
                    &copy; <?php echo date('Y'); ?> <?php echo get_setting('site_name', 'Newspower'); ?>. All rights reserved.
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="mb-0">
                    <a href="<?php echo SITE_URL; ?>/privacy.php" class="text-white text-decoration-none me-3">Privacy Policy</a>
                    <a href="<?php echo SITE_URL; ?>/terms.php" class="text-white text-decoration-none">Terms of Service</a>
                </p>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JavaScript -->
<script>
// Social Share Functions
function shareOnFacebook(url, title) {
    const shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}&quote=${encodeURIComponent(title)}`;
    window.open(shareUrl, 'facebook-share-dialog', 'width=626,height=436');
    
    // Track share
    fetch('<?php echo SITE_URL; ?>/api/share.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ post_id: getPostId(), platform: 'facebook' })
    });
}

function shareOnTwitter(url, title) {
    const shareUrl = `https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title)}`;
    window.open(shareUrl, 'twitter-share-dialog', 'width=626,height=436');
    
    // Track share
    fetch('<?php echo SITE_URL; ?>/api/share.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ post_id: getPostId(), platform: 'twitter' })
    });
}

function shareOnLinkedIn(url, title) {
    const shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(url)}`;
    window.open(shareUrl, 'linkedin-share-dialog', 'width=626,height=436');
    
    // Track share
    fetch('<?php echo SITE_URL; ?>/api/share.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ post_id: getPostId(), platform: 'linkedin' })
    });
}

function shareOnWhatsApp(url, title) {
    const text = `${title} ${url}`;
    const shareUrl = `https://wa.me/?text=${encodeURIComponent(text)}`;
    window.open(shareUrl, '_blank');
    
    // Track share
    fetch('<?php echo SITE_URL; ?>/api/share.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ post_id: getPostId(), platform: 'whatsapp' })
    });
}

function copyToClipboard(url) {
    navigator.clipboard.writeText(url).then(() => {
        alert('Link copied to clipboard!');
        
        // Track share
        fetch('<?php echo SITE_URL; ?>/api/share.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ post_id: getPostId(), platform: 'copy' })
        });
    });
}

function getPostId() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('id') || document.querySelector('[data-post-id]')?.dataset.postId || null;
}

// Newsletter form handling
document.addEventListener('DOMContentLoaded', function() {
    const newsletterForm = document.querySelector('.newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const email = formData.get('email');
            
            if (email) {
                // Here you can add newsletter subscription logic
                alert('Thank you for subscribing to our newsletter!');
                this.reset();
            }
        });
    }
    
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
});

// Back to top button
window.addEventListener('scroll', function() {
    const backToTop = document.getElementById('backToTop');
    if (backToTop) {
        if (window.pageYOffset > 300) {
            backToTop.style.display = 'block';
        } else {
            backToTop.style.display = 'none';
        }
    }
});
</script>

<!-- Back to Top Button -->
<button id="backToTop" onclick="window.scrollTo({top: 0, behavior: 'smooth'})" 
        style="display: none; position: fixed; bottom: 20px; right: 20px; z-index: 1000; 
               background-color: var(--primary-color); color: white; border: none; 
               border-radius: 50%; width: 50px; height: 50px; cursor: pointer; 
               box-shadow: 0 2px 10px rgba(0,0,0,0.3);">
    <i class="fas fa-arrow-up"></i>
</button>

<!-- Google Analytics (if configured) -->
<?php if (get_setting('google_analytics')): ?>
    <?php echo get_setting('google_analytics'); ?>
<?php endif; ?>

</body>
</html>