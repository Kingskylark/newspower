<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Get about page content from database
$about_page = get_page_by_slug('about');

// If no about page in database, show default content
$page_title = $about_page ? $about_page['title'] : 'About Us';
$meta_description = $about_page ? ($about_page['meta_description'] ?: truncate_text(strip_tags($about_page['content']), 160)) : 'Learn more about us and what we do';

// Get some stats for the about page
$total_posts = get_total_posts();
$total_views = get_total_views();
$total_categories = count(get_categories());

include 'includes/header.php';
?>

<!-- Page Header -->
<section class="bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="display-4 fw-bold mb-3">
                    <?php echo htmlspecialchars($page_title); ?>
                </h1>
                <p class="lead mb-0 opacity-75">
                    Learn more about <?php echo get_setting('site_name', 'Newspower'); ?> and our mission
                </p>
            </div>
        </div>
    </div>
</section>

<!-- About Content -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <?php if ($about_page): ?>
                    <!-- Dynamic content from database -->
                    <div class="about-content">
                        <?php echo $about_page['content']; ?>
                    </div>
                <?php else: ?>
                    <!-- Default content if no page in database -->
                    <div class="about-content">
                        <h2 class="text-primary mb-4">Welcome to <?php echo get_setting('site_name', 'Newspower'); ?></h2>

                        <p class="lead">
                            <?php echo get_setting('site_description', 'We are dedicated to bringing you the latest news and updates from around the world.'); ?>
                        </p>

                        <p>
                            At <?php echo get_setting('site_name', 'Newspower'); ?>, we believe in the power of information
                            to educate, inspire, and connect people. Our team is committed to delivering accurate, timely,
                            and engaging news content that matters to our readers.
                        </p>

                        <h3 class="text-primary mt-5 mb-3">Our Mission</h3>
                        <p>
                            Our mission is to provide reliable, comprehensive news coverage that keeps our community
                            informed and engaged. We strive to present balanced perspectives on current events while
                            maintaining the highest standards of journalistic integrity.
                        </p>

                        <h3 class="text-primary mt-5 mb-3">What We Cover</h3>
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="fas fa-check-circle text-secondary me-2"></i>Breaking News</li>
                            <li class="mb-2"><i class="fas fa-check-circle text-secondary me-2"></i>Politics & Government
                            </li>
                            <li class="mb-2"><i class="fas fa-check-circle text-secondary me-2"></i>Business & Economy</li>
                            <li class="mb-2"><i class="fas fa-check-circle text-secondary me-2"></i>Technology & Innovation
                            </li>
                            <li class="mb-2"><i class="fas fa-check-circle text-secondary me-2"></i>Sports & Entertainment
                            </li>
                            <li class="mb-2"><i class="fas fa-check-circle text-secondary me-2"></i>Health & Lifestyle</li>
                        </ul>

                        <h3 class="text-primary mt-5 mb-3">Our Values</h3>
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                                            style="width: 50px; height: 50px;">
                                            <i class="fas fa-bullseye"></i>
                                        </div>
                                    </div>
                                    <div class="ms-3">
                                        <h5>Accuracy</h5>
                                        <p class="text-muted mb-0">We verify our sources and fact-check our content to
                                            ensure accuracy.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center"
                                            style="width: 50px; height: 50px;">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                    </div>
                                    <div class="ms-3">
                                        <h5>Timeliness</h5>
                                        <p class="text-muted mb-0">We deliver news as it happens, keeping you updated in
                                            real-time.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                                            style="width: 50px; height: 50px;">
                                            <i class="fas fa-balance-scale"></i>
                                        </div>
                                    </div>
                                    <div class="ms-3">
                                        <h5>Objectivity</h5>
                                        <p class="text-muted mb-0">We present multiple perspectives and let readers form
                                            their own opinions.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center"
                                            style="width: 50px; height: 50px;">
                                            <i class="fas fa-users"></i>
                                        </div>
                                    </div>
                                    <div class="ms-3">
                                        <h5>Community</h5>
                                        <p class="text-muted mb-0">We serve our community and value reader engagement and
                                            feedback.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Stats Card -->
                <div class="card mb-4 bg-light">
                    <div class="card-body text-center">
                        <h5 class="text-primary mb-4">
                            <i class="fas fa-chart-line me-2"></i>Our Impact
                        </h5>

                        <div class="row">
                            <div class="col-4">
                                <div class="stat-item mb-3">
                                    <h3 class="text-secondary mb-0"><?php echo number_format($total_posts); ?></h3>
                                    <small class="text-muted">Articles</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-item mb-3">
                                    <h3 class="text-secondary mb-0"><?php echo number_format($total_views); ?></h3>
                                    <small class="text-muted">Total Views</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-item mb-3">
                                    <h3 class="text-secondary mb-0"><?php echo $total_categories; ?></h3>
                                    <small class="text-muted">Categories</small>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-center gap-3">
                            <?php
                            $social_links = [
                                'facebook' => ['icon' => 'fab fa-facebook-f', 'color' => '#1877f2'],
                                'twitter' => ['icon' => 'fab fa-twitter', 'color' => '#1da1f2'],
                                'linkedin' => ['icon' => 'fab fa-linkedin-in', 'color' => '#0077b5'],
                                'instagram' => ['icon' => 'fab fa-instagram', 'color' => '#e4405f']
                            ];

                            foreach ($social_links as $platform => $config):
                                $url = get_setting("social_$platform");
                                if ($url):
                                    ?>
                                    <a href="<?php echo htmlspecialchars($url); ?>" target="_blank"
                                        class="btn btn-outline-primary btn-sm">
                                        <i class="<?php echo $config['icon']; ?>"></i>
                                    </a>
                                    <?php
                                endif;
                            endforeach;
                            ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

</section>

<!-- CTA Section -->
<section class="bg-light py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <h2 class="text-primary mb-4">Ready to Stay Informed?</h2>
                <p class="lead mb-4">
                    Join thousands of readers who trust <?php echo get_setting('site_name', 'Newspower'); ?> for their
                    daily news.
                </p>
                <div class="d-flex flex-column flex-sm-row justify-content-center gap-3">
                    <a href="<?php echo SITE_URL; ?>/blog.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-newspaper me-2"></i>Read Latest News
                    </a>
                    <a href="<?php echo SITE_URL; ?>/contact.php" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-envelope me-2"></i>Contact Us
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>