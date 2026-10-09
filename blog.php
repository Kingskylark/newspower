<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Get search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_slug = isset($_GET['category']) ? trim($_GET['category']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = get_setting('posts_per_page', 12);
$offset = ($page - 1) * $per_page;

// Get category info if filtering
$current_category = null;
if ($category_slug) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ?");
    $stmt->execute([$category_slug]);
    $current_category = $stmt->fetch();
}

// Build query for posts
$where_conditions = ["p.status = 'published'"];
$params = [];

if ($search) {
    $where_conditions[] = "(p.title LIKE ? OR p.content LIKE ? OR p.excerpt LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

if ($current_category) {
    $where_conditions[] = "p.category = ?";
    $params[] = $current_category['name'];
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count for pagination
$count_sql = "SELECT COUNT(*) FROM posts p WHERE $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_posts = $count_stmt->fetchColumn();
$total_pages = ceil($total_posts / $per_page);

// Get posts for current page
$posts_sql = "
    SELECT p.*, au.full_name as author_name 
    FROM posts p 
    LEFT JOIN admin_users au ON p.author_id = au.id 
    WHERE $where_clause 
    ORDER BY p.published_at DESC, p.created_at DESC 
    LIMIT ? OFFSET ?
";
$posts_params = array_merge($params, [$per_page, $offset]);
$posts_stmt = $pdo->prepare($posts_sql);
$posts_stmt->execute($posts_params);
$posts = $posts_stmt->fetchAll();

// Get all categories for sidebar
$categories = get_categories();

// Get popular posts for sidebar
$popular_posts = get_popular_posts(5);

// Page meta data
$page_title = 'News';
if ($current_category) {
    $page_title = $current_category['name'] . ' News';
}
if ($search) {
    $page_title = 'Search Results for "' . htmlspecialchars($search) . '"';
}

$meta_description = 'Latest news and updates';
if ($current_category && $current_category['description']) {
    $meta_description = $current_category['description'];
}

include 'includes/header.php';
?>

<!-- Page Header -->
<section class="bg-primary text-white py-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="mb-0">
                    <?php if ($search): ?>
                        <i class="fas fa-search me-2"></i>Search Results
                    <?php elseif ($current_category): ?>
                        <i class="fas fa-tag me-2"></i><?php echo htmlspecialchars($current_category['name']); ?>
                    <?php else: ?>
                        <i class="fas fa-newspaper me-2"></i>Latest News
                    <?php endif; ?>
                </h1>
                <?php if ($search): ?>
                    <p class="mb-0 opacity-75">Showing results for "<?php echo htmlspecialchars($search); ?>"</p>
                <?php elseif ($current_category && $current_category['description']): ?>
                    <p class="mb-0 opacity-75"><?php echo htmlspecialchars($current_category['description']); ?></p>
                <?php endif; ?>
            </div>
            <div class="col-md-6 text-md-end">
                <span class="badge bg-light text-dark fs-6">
                    <?php echo number_format($total_posts); ?> <?php echo $total_posts == 1 ? 'Article' : 'Articles'; ?>
                </span>
            </div>
        </div>
    </div>
</section>

<!-- Main Content -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Filter Bar -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="d-flex align-items-center gap-3">
                        <?php if ($current_category || $search): ?>
                            <a href="<?php echo SITE_URL; ?>/blog.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-times me-1"></i>Clear Filters
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($current_category): ?>
                            <span class="badge" style="background-color: <?php echo $current_category['color']; ?>;">
                                <?php echo htmlspecialchars($current_category['name']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-flex align-items-center gap-2">
                        <small class="text-muted">Page <?php echo $page; ?> of <?php echo $total_pages; ?></small>
                    </div>
                </div>

                <!-- Posts Grid -->
                <?php if (!empty($posts)): ?>
                    <div class="row">
                        <?php foreach ($posts as $post): ?>
                            <div class="col-md-6 mb-4">
                                <article class="card h-100">
                                    <?php if ($post['featured_image']): ?>
                                        <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $post['featured_image']; ?>" 
                                             class="card-img-top" alt="<?php echo htmlspecialchars($post['title']); ?>">
                                    <?php else: ?>
                                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                            <i class="fas fa-newspaper fa-2x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="card-body d-flex flex-column">
                                        <!-- Category Badge -->
                                        <?php if ($post['category']): ?>
                                            <div class="mb-2">
                                                <a href="<?php echo SITE_URL; ?>/blog.php?category=<?php echo urlencode(strtolower($post['category'])); ?>" 
                                                   class="category-badge">
                                                    <?php echo htmlspecialchars($post['category']); ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Title -->
                                        <h5 class="card-title">
                                            <a href="<?php echo SITE_URL; ?>/post.php?slug=<?php echo $post['slug']; ?>" 
                                               class="text-decoration-none text-dark">
                                                <?php echo htmlspecialchars($post['title']); ?>
                                            </a>
                                        </h5>
                                        
                                        <!-- Excerpt -->
                                        <p class="card-text flex-grow-1">
                                            <?php echo $post['excerpt'] ? htmlspecialchars($post['excerpt']) : truncate_text(strip_tags($post['content']), 120); ?>
                                        </p>
                                        
                                        <!-- Meta Info -->
                                        <div class="news-meta mt-auto">
                                            <small>
                                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($post['author_name'] ?? 'Admin'); ?>
                                                <i class="fas fa-calendar ms-3"></i> <?php echo format_date($post['published_at'] ?? $post['created_at']); ?>
                                                <i class="fas fa-eye ms-3"></i> <?php echo number_format($post['views_count']); ?>
                                            </small>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- No Results -->
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No articles found</h4>
                        <?php if ($search): ?>
                            <p class="text-muted">Try adjusting your search terms or browse by category.</p>
                            <a href="<?php echo SITE_URL; ?>/blog.php" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-2"></i>View All News
                            </a>
                        <?php else: ?>
                            <p class="text-muted">Check back later for new updates.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                            <!-- Popular Posts Widget -->
                <?php if (!empty($popular_posts)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-fire me-2"></i>Popular News
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($popular_posts as $index => $popular): ?>
                            <div class="d-flex mb-3 <?php echo $index < count($popular_posts) - 1 ? 'pb-3 border-bottom' : ''; ?>">
                                <div class="flex-shrink-0 me-3">
                                    <div class="popular-rank">
                                        <?php echo $index + 1; ?>
                                    </div>
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
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>