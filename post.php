<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Get post slug from URL
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (!$slug) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit;
}

// Get post by slug
$post = get_post_by_slug($slug);

if (!$post) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit;
}

// Increment view count
increment_post_views($post['id']);

// Get related posts (same category)
$related_posts = [];
if ($post['category']) {
    $stmt = $pdo->prepare("
        SELECT p.*, au.full_name as author_name 
        FROM posts p 
        LEFT JOIN admin_users au ON p.author_id = au.id 
        WHERE p.category = ? AND p.slug != ? AND p.status = 'published' 
        ORDER BY p.published_at DESC 
        LIMIT 3
    ");
    $stmt->execute([$post['category'], $slug]);
    $related_posts = $stmt->fetchAll();
}

// Get popular posts for sidebar
$popular_posts = get_popular_posts(5);

// Get categories for sidebar
$categories = get_categories();


// Page meta data
$page_title = $post['meta_title'] ?: $post['title'];
$meta_description = $post['meta_description'] ?: ($post['excerpt'] ?: truncate_text(strip_tags($post['content']), 160));
$og_type = 'article';
$og_title = $post['title'];
$og_url = get_post_url($post['slug']);
$og_image = $post['featured_image'] ? SITE_URL . '/uploads/' . rawurlencode($post['featured_image']) : '';
$page_image = $og_image;
$page_keywords = $post['tags'] ?: $post['category'];

include 'includes/header.php';
?>

<!-- Article Header -->
<article class="single-post">
    <header class="post-header bg-primary text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <!-- Category Badge -->
                    <?php if ($post['category']): ?>
                        <div class="mb-3">
                            <a href="<?php echo SITE_URL; ?>/blog.php?category=<?php echo urlencode(strtolower($post['category'])); ?>" 
                               class="badge bg-light text-dark text-decoration-none px-3 py-2">
                                <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($post['category']); ?>
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Post Title -->
                    <h1 class="display-5 fw-bold mb-3"><?php echo htmlspecialchars($post['title']); ?></h1>

                    <!-- Post Meta -->
                    <div class="post-meta d-flex flex-wrap align-items-center gap-3 opacity-75">
                        <span>
                            <i class="fas fa-user me-1"></i>
                            By <?php echo htmlspecialchars($post['author_name'] ?? 'Admin'); ?>
                        </span>
                        <span>
                            <i class="fas fa-calendar me-1"></i>
                            <?php echo format_date($post['published_at'] ?? $post['created_at'], 'F j, Y'); ?>
                        </span>
                        <span>
                            <i class="fas fa-clock me-1"></i>
                            <?php echo ceil(str_word_count(strip_tags($post['content'])) / 200); ?> min read
                        </span>
                        <span>
                            <i class="fas fa-eye me-1"></i>
                            <?php echo number_format($post['views_count']); ?> views
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Featured Image -->
    <?php if ($post['featured_image']): ?>
        <div class="post-featured-image">
            <div class="container">
                <div class="row">
                    <div class="col-lg-10 mx-auto">
                        <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $post['featured_image']; ?>" 
                             alt="<?php echo htmlspecialchars($post['title']); ?>" 
                             class="img-fluid rounded shadow my-4">
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Post Content -->
    <div class="container py-4">
        <div class="row">
            <div class="col-lg-12">
                <!-- Post Excerpt -->
                <?php if ($post['excerpt']): ?>
                    <div class="post-excerpt bg-light p-4 rounded mb-4">
                        <p class="lead mb-0 text-muted">
                            <i class="fas fa-quote-left me-2"></i>
                            <?php echo htmlspecialchars($post['excerpt']); ?>
                        </p>
                    </div>
                <?php endif; ?>

              <!-- In your post display template -->
<div class="article-content">
    <?php echo $post['content']; ?>
</div>

<?php if (!empty($post['tags'])): ?>
    <div class="article-tags">
        <h6>Related Topics</h6>
        <?php 
        $tags = explode(',', $post['tags']);
        foreach ($tags as $tag): 
            $tag = trim($tag);
        ?>
            <a href="<?php echo SITE_URL; ?>/tag.php?tag=<?php echo urlencode($tag); ?>" class="tag-item">
                <?php echo htmlspecialchars($tag); ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

                <!-- Social Share -->
                <div class="social-share-section mb-5">
                    <h6 class="text-primary mb-3">
                        <i class="fas fa-share-alt me-2"></i>Share This Article
                    </h6>
                    <div class="social-share" data-post-id="<?php echo $post['id']; ?>">
                        <a href="#" onclick="shareOnFacebook('<?php echo get_post_url($post['slug']); ?>', '<?php echo addslashes($post['title']); ?>')" 
                           class="facebook" title="Share on Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" onclick="shareOnTwitter('<?php echo get_post_url($post['slug']); ?>', '<?php echo addslashes($post['title']); ?>')" 
                           class="twitter" title="Share on Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" onclick="shareOnLinkedIn('<?php echo get_post_url($post['slug']); ?>', '<?php echo addslashes($post['title']); ?>')" 
                           class="linkedin" title="Share on LinkedIn">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="#" onclick="shareOnWhatsApp('<?php echo get_post_url($post['slug']); ?>', '<?php echo addslashes($post['title']); ?>')" 
                           class="whatsapp" title="Share on WhatsApp">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                        <a href="#" onclick="copyToClipboard('<?php echo get_post_url($post['slug']); ?>')" 
                           class="copy-link bg-secondary" title="Copy Link">
                            <i class="fas fa-link"></i>
                        </a>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">
                            <i class="fas fa-share me-1"></i>
                            Shared <?php echo number_format($post['shares_count']); ?> times
                        </small>
                    </div>
                </div>

                <!-- Author Bio (if available) -->
                <?php if ($post['author_name']): ?>
                <div class="author-bio bg-light rounded p-4 mb-5">
                    <div class="d-flex align-items-center">
                        <div class="author-avatar me-3">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                 style="width: 60px; height: 60px;">
                                <i class="fas fa-user fa-2x"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="mb-1 text-primary"><?php echo htmlspecialchars($post['author_name']); ?></h6>
                            <p class="mb-0 text-muted small">Contributing Author</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Related Posts -->
                <?php if (!empty($related_posts)): ?>
                <div class="related-posts">
                    <h4 class="text-primary mb-4">
                        <i class="fas fa-newspaper me-2"></i>Related Articles
                    </h4>
                    <div class="row">
                        <?php foreach ($related_posts as $related): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card h-100">
                                    <?php if ($related['featured_image']): ?>
                                        <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $related['featured_image']; ?>" 
                                             class="card-img-top" alt="<?php echo htmlspecialchars($related['title']); ?>"
                                             style="height: 150px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 150px;">
                                            <i class="fas fa-newspaper fa-2x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="card-body d-flex flex-column">
                                        <h6 class="card-title">
                                            <a href="<?php echo SITE_URL; ?>/post.php?slug=<?php echo $related['slug']; ?>" 
                                               class="text-decoration-none text-dark">
                                                <?php echo htmlspecialchars(truncate_text($related['title'], 80)); ?>
                                            </a>
                                        </h6>
                                        <small class="text-muted mt-auto">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php echo format_date($related['published_at'] ?? $related['created_at']); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            
        </div>
    </div>
</article>

<?php include 'includes/footer.php'; ?>