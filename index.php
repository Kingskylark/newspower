<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Page variables
$page_title = 'Home';
$meta_description = get_setting('site_description', 'Newspower - Your trusted source for latest news and updates');

// Get featured posts
$featured_posts = get_posts(3, 0, null, true);

// Get latest posts (excluding featured ones)
$latest_posts = get_posts(6, 0);

// Get categories for sidebar
$categories = get_categories();

// Get popular posts
$popular_posts = get_popular_posts(5);

include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="hero-title">
                    <?php echo get_setting('home_hero_title', 'Welcome to Newspower'); ?>
                </h1>
                <p class="hero-subtitle">
                    <?php echo get_setting('home_hero_subtitle', 'Your trusted source for the latest news, insights, and updates from around the world.'); ?>
                </p>
                <div class="mt-4">
                    <a href="<?php echo SITE_URL; ?>/blog.php" class="btn btn-light btn-lg me-3 mb-2">
                        <i class="fas fa-newspaper me-2"></i>Read Latest News
                    </a>
                    <a href="<?php echo SITE_URL; ?>/about.php" class="btn btn-outline-light btn-lg mb-2">
                        <i class="fas fa-info-circle me-2"></i>Learn More
                    </a>
                </div>
            </div>
            <div class="col-lg-6">
                <?php if (get_setting('home_hero_image')): ?>
                <img src="<?php echo SITE_URL; ?>/uploads/<?php echo get_setting('home_hero_image'); ?>" 
                     class="img-fluid rounded shadow" alt="Hero Image">
                <?php else: ?>
                <div class="text-center">
                    <i class="fas fa-newspaper" style="font-size: 8rem; opacity: 0.3;"></i>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Featured News Section -->
<?php if (!empty($featured_posts)): ?>
<section class="py-5">
    <div class="container">
        <h2 class="section-title">Featured News</h2>
        
        <div class="row">
            <?php foreach ($featured_posts as $index => $post): ?>
            <div class="col-lg-<?php echo $index === 0 ? '8' : '4'; ?> col-md-6 mb-4">
                <div class="card featured-card h-100">
                    <?php if ($post['featured_image']): ?>
                    <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $post['featured_image']; ?>" 
                         class="card-img-top" alt="<?php echo htmlspecialchars($post['title']); ?>">
                    <?php else: ?>
                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center">
                        <i class="fas fa-newspaper fa-3x text-muted"></i>
                    </div>
                    <?php endif; ?>
                    
                    <div class="card-body d-flex flex-column">
                        <div class="mb-2">
                            <?php if ($post['category']): ?>
                            <a href="<?php echo SITE_URL; ?>/blog.php?category=<?php echo urlencode(strtolower($post['category'])); ?>" 
                               class="category-badge">
                                <?php echo htmlspecialchars($post['category']); ?>
                            </a>
                            <?php endif; ?>
                        </div>
                        
                        <h5 class="card-title">
                            <a href="<?php echo SITE_URL; ?>/post.php?slug=<?php echo $post['slug']; ?>" 
                               class="text-decoration-none text-dark">
                                <?php echo htmlspecialchars($post['title']); ?>
                            </a>
                        </h5>
                        
                        <p class="card-text flex-grow-1">
                            <?php echo $post['excerpt'] ? htmlspecialchars($post['excerpt']) : truncate_text(strip_tags($post['content']), 120); ?>
                        </p>
                        
                        <div class="news-meta mt-auto">
                            <small>
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($post['author_name'] ?? 'Admin'); ?>
                                <i class="fas fa-calendar ms-3"></i> <?php echo format_date($post['published_at'] ?? $post['created_at']); ?>
                                <i class="fas fa-eye ms-3"></i> <?php echo number_format($post['views_count']); ?> views
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($index === 0): ?>
            </div>
            <div class="row">
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Latest News Section -->
<section class="py-5 bg-light-custom">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <h2 class="section-title">Latest News</h2>
                
                <?php if (!empty($latest_posts)): ?>
                <div class="row">
                    <?php foreach ($latest_posts as $post): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <?php if ($post['featured_image']): ?>
                            <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $post['featured_image']; ?>" 
                                 class="card-img-top" alt="<?php echo htmlspecialchars($post['title']); ?>">
                            <?php else: ?>
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                <i class="fas fa-newspaper fa-2x text-muted"></i>
                            </div>
                            <?php endif; ?>
                            
                            <div class="card-body d-flex flex-column">
                                <div class="mb-2">
                                    <?php if ($post['category']): ?>
                                    <a href="<?php echo SITE_URL; ?>/blog.php?category=<?php echo urlencode(strtolower($post['category'])); ?>" 
                                       class="category-badge">
                                        <?php echo htmlspecialchars($post['category']); ?>
                                    </a>
                                    <?php endif; ?>
                                </div>
                                
                                <h5 class="card-title">
                                    <a href="<?php echo SITE_URL; ?>/post.php?slug=<?php echo $post['slug']; ?>" 
                                       class="text-decoration-none text-dark">
                                        <?php echo htmlspecialchars($post['title']); ?>
                                    </a>
                                </h5>
                                
                                <p class="card-text flex-grow-1">
                                    <?php echo $post['excerpt'] ? htmlspecialchars($post['excerpt']) : truncate_text(strip_tags($post['content']), 100); ?>
                                </p>
                                
                                <div class="news-meta mt-auto">
                                    <small>
                                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($post['author_name'] ?? 'Admin'); ?>
                                        <i class="fas fa-calendar ms-3"></i> <?php echo format_date($post['published_at'] ?? $post['created_at']); ?>
                                        <i class="fas fa-eye ms-3"></i> <?php echo number_format($post['views_count']); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="text-center mt-4 mb-3">
                    <a href="<?php echo SITE_URL; ?>/blog.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-newspaper me-2"></i>View All News
                    </a>
                </div>
                
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-newspaper fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No news articles found</h4>
                    <p class="text-muted">Check back later for the latest updates.</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="row">
                <!-- Popular Posts -->
                <?php if (!empty($popular_posts)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-fire me-2"></i>Popular News
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($popular_posts as $index => $popular): ?>
                        <div class="d-flex mb-3 <?php echo $index < count($popular_posts) - 1 ? 'pb-3 border-bottom' : ''; ?>">
                            <div class="flex-shrink-0 me-3">
                                <span class="badge bg-secondary"><?php echo $index + 1; ?></span>
                            </div>
                            <div>
                                <h6 class="mb-1">
                                    <a href="<?php echo SITE_URL; ?>/post.php?slug=<?php echo $popular['slug']; ?>" 
                                       class="text-decoration-none text-dark">
                                        <?php echo htmlspecialchars(truncate_text($popular['title'], 60)); ?>
                                    </a>
                                </h6>
                                <small class="text-muted">
                                    <i class="fas fa-eye me-1"></i><?php echo number_format($popular['views_count']); ?> views
                                    <i class="fas fa-share ms-2 me-1"></i><?php echo number_format($popular['shares_count']); ?> shares
                                </small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Categories -->
                <?php if (!empty($categories)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Categories
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($categories as $category): ?>
                        <a href="<?php echo SITE_URL; ?>/blog.php?category=<?php echo urlencode($category['slug']); ?>" 
                           class="btn btn-outline-secondary btn-sm me-2 mb-2">
                             <?php echo htmlspecialchars($category['name']); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                </div>
                </div>
        </div>
    </div>
</section>


<!-- Newsletter -->
 <section>

<div class="container mb-5 mt-3">
    <div class="card mb-4 bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-envelope fa-2x mb-3"></i>
                        <h5>Stay Updated</h5>
                        <p>Subscribe to our newsletter for the latest news and updates.</p>
                        <form action="<?php echo SITE_URL; ?>/api/newsletter.php" method="POST">
                            <div class="mb-3">
                                <input type="email" class="form-control" name="email" placeholder="Your email address" required>
                            </div>
                            <button type="submit" class="btn btn-light">
                                <i class="fas fa-paper-plane me-2"></i>Subscribe
                            </button>
                        </form>
                    </div>
                </div>
</div>
 </section>
                
            
<?php include 'includes/footer.php'; ?>