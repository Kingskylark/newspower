<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top">
    <div class="container">
        <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
            <?php
            $site_logo = get_setting('site_logo');
            if ($site_logo):
                ?>
                <img src="<?php echo SITE_URL . '/' . $site_logo; ?>"
                    alt="<?php echo get_setting('site_name', 'Newspower'); ?>" height="40" class="me-2">

            <?php else: ?>
                <i class="fas fa-newspaper me-2"></i>
                <?php echo get_setting('site_name', 'Newspower'); ?>
            <?php endif; ?>
            
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>"
                        href="<?php echo SITE_URL; ?>">
                        <i class="fas fa-home me-1"></i>Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'blog.php' ? 'active' : ''; ?>"
                        href="<?php echo SITE_URL; ?>/blog.php">
                        <i class="fas fa-newspaper me-1"></i>News
                    </a>
                </li>

                <!-- Dynamic Categories -->
                <?php
                $categories = get_categories();
                if (!empty($categories)):
                    ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="categoriesDropdown" role="button"
                            data-bs-toggle="dropdown">
                            <i class="fas fa-list me-1"></i>Categories
                        </a>
                        <ul class="dropdown-menu">
                            <?php foreach ($categories as $category): ?>
                                <li>
                                    <a class="dropdown-item"
                                        href="<?php echo SITE_URL; ?>/blog.php?category=<?php echo urlencode($category['slug']); ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endif; ?>

                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : ''; ?>"
                        href="<?php echo SITE_URL; ?>/about.php">
                        <i class="fas fa-info-circle me-1"></i>About
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : ''; ?>"
                        href="<?php echo SITE_URL; ?>/contact.php">
                        <i class="fas fa-envelope me-1"></i>Contact
                    </a>
                </li>
            </ul>

            <!-- Search Form -->
            <form class="d-flex me-3" method="GET" action="<?php echo SITE_URL; ?>/blog.php">
                <div class="input-group">
                    <input class="form-control form-control-sm" type="search" name="search" placeholder="Search news..."
                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button class="btn btn-outline-primary btn-sm" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>

            <!-- Social Media Links -->
            <div class="d-flex gap-2">
                <?php if (get_setting('social_facebook')): ?>
                    <a href="<?php echo get_setting('social_facebook'); ?>" target="_blank" class="text-decoration-none">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                <?php endif; ?>

                <?php if (get_setting('social_twitter')): ?>
                    <a href="<?php echo get_setting('social_twitter'); ?>" target="_blank"
                        class="text-decoration-none ms-2">
                        <i class="fab fa-twitter"></i>
                    </a>
                <?php endif; ?>

                <?php if (get_setting('social_linkedin')): ?>
                    <a href="<?php echo get_setting('social_linkedin'); ?>" target="_blank"
                        class="text-decoration-none ms-2">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                <?php endif; ?>

                <?php if (get_setting('social_instagram')): ?>
                    <a href="<?php echo get_setting('social_instagram'); ?>" target="_blank"
                        class="text-decoration-none ms-2">
                        <i class="fab fa-instagram"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>