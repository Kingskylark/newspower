<?php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Check if user is logged in
require_admin_login();
check_session_timeout();

// Handle form submissions
if ($_POST && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash_message('Invalid security token.', 'error');
    } else {
        $updated = false;

        // Update each setting
        foreach ($_POST as $key => $value) {
            if ($key !== 'csrf_token' && $key !== 'action') {
                if (update_setting($key, sanitize_input($value))) {
                    $updated = true;
                }
            }
        }

        if ($updated) {
            set_flash_message('Settings updated successfully!', 'success');
        } else {
            set_flash_message('No changes were made.', 'info');
        }

        header('Location: ' . ADMIN_URL . '/settings.php');
        exit;
    }
}

// Get all settings grouped by category
try {
    $stmt = $pdo->prepare("SELECT * FROM site_settings ORDER BY category, setting_name");
    $stmt->execute();
    $all_settings = $stmt->fetchAll();

    $settings = [];
    foreach ($all_settings as $setting) {
        $settings[$setting['category']][] = $setting;
    }
} catch (PDOException $e) {
    $settings = [];
}

$site_name = get_setting('site_name', 'Newspower');
$flash_message = get_flash_message();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - <?php echo htmlspecialchars($site_name); ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
 <link rel="apple-touch-icon" sizes="180x180" href="<?php echo SITE_URL; ?>/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="<?php echo SITE_URL; ?>/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="<?php echo SITE_URL; ?>/favicon-16x16.png">
<link rel="manifest" href="<?php echo SITE_URL; ?>/site.webmanifest">
<link rel="icon" href="<?php echo SITE_URL; ?>/favicon.ico" type="image/x-icon">

    <style>
        :root {
            --primary-color: rgb(32, 57, 97);
            --secondary-color: rgb(238, 32, 66);
            --primary-hover: rgb(22, 42, 72);
            --sidebar-width: 280px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-color) 0%, var(--primary-hover) 100%);
            z-index: 1000;
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-item {
            margin: 0.25rem 0;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1.5rem;
            border-radius: 0;
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
        }

        .nav-link:hover {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .nav-link.active {
            color: white;
            background-color: var(--secondary-color);
        }

        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }

        .top-navbar {
            background: white;
            border-bottom: 1px solid #e9ecef;
            padding: 1rem 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .content-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .settings-tabs {
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 2rem;
        }

        .settings-tabs .nav-link {
            border: none;
            color: #6c757d;
            padding: 1rem 1.5rem;
            background: none;
            border-radius: 0;
        }

        .settings-tabs .nav-link.active {
            color: var(--primary-color);
            background: none;
            border-bottom: 2px solid var(--primary-color);
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header text-white">
            <h4 class="mb-1"><i class="fas fa-newspaper me-2"></i><?php echo htmlspecialchars($site_name); ?></h4>
            <small class="opacity-75">Admin Dashboard</small>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-item">
                <a href="<?php echo ADMIN_URL; ?>" class="nav-link">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
            </div>
            <div class="nav-item">
                <a href="<?php echo ADMIN_URL; ?>/posts.php" class="nav-link">
                    <i class="fas fa-edit me-2"></i>Posts
                </a>
            </div>
            <div class="nav-item">
                <a href="<?php echo ADMIN_URL; ?>/categories.php" class="nav-link">
                    <i class="fas fa-tags me-2"></i>Categories
                </a>
            </div>
            <div class="nav-item">
                <a href="<?php echo ADMIN_URL; ?>/users.php" class="nav-link">
                    <i class="fas fa-users me-2"></i>Users
                </a>
            </div>
            <div class="nav-item">
                <a href="<?php echo ADMIN_URL; ?>/settings.php" class="nav-link active">
                    <i class="fas fa-cog me-2"></i>Settings
                </a>
            </div>
            <div class="nav-item">
                <a href="<?php echo ADMIN_URL; ?>/analytics.php" class="nav-link">
                    <i class="fas fa-chart-bar me-2"></i>Analytics
                </a>
            </div>
            <div class="nav-item">
                <a href="<?php echo ADMIN_URL; ?>/messages.php" class="nav-link">
                    <i class="fas fa-envelope me-2"></i>Messages
                </a>
            </div>
            <div class="nav-item mt-4">
                <a href="<?php echo SITE_URL; ?>" class="nav-link" target="_blank">
                    <i class="fas fa-external-link-alt me-2"></i>View Site
                </a>
            </div>
            <div class="nav-item">
                <a href="<?php echo ADMIN_URL; ?>/logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <div class="top-navbar d-flex justify-content-between align-items-center">
            <div>
                <button class="btn btn-outline-secondary d-md-none me-2" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h5 class="mb-0">Settings</h5>
            </div>
            <div class="d-flex align-items-center">
                <div class="user-avatar me-3">
                    <?php echo strtoupper(substr($_SESSION['admin_name'], 0, 1)); ?>
                </div>
                <div>
                    <small class="text-muted">Welcome back,</small>
                    <div class="fw-bold"><?php echo htmlspecialchars($_SESSION['admin_name']); ?></div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="container-fluid p-4">
            <?php if ($flash_message): ?>
                <div
                    class="alert alert-<?php echo $flash_message['type'] === 'error' ? 'danger' : $flash_message['type']; ?> alert-dismissible fade show">
                    <?php echo htmlspecialchars($flash_message['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php
            // Fetch About Page
            try {
                $stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = 'about' LIMIT 1");
                $stmt->execute();
                $about_page = $stmt->fetch();
            } catch (PDOException $e) {
                $about_page = null;
            }

            // Handle About Page Update
            if ($_POST && isset($_POST['update_about_page']) && verify_csrf_token($_POST['csrf_token'] ?? '')) {
                $title = sanitize_input($_POST['title']);
                $content = $_POST['content']; // allow HTML
                $meta_title = sanitize_input($_POST['meta_title']);
                $meta_description = sanitize_input($_POST['meta_description']);

                try {
                    $stmt = $pdo->prepare("UPDATE pages 
                               SET title = ?, content = ?, meta_title = ?, meta_description = ?, updated_at = NOW() 
                               WHERE slug = 'about'");
                    $stmt->execute([$title, $content, $meta_title, $meta_description]);
                    set_flash_message('About page updated successfully!', 'success');
                    header("Location: " . ADMIN_URL . "/settings.php");
                    exit;
                } catch (PDOException $e) {
                    set_flash_message('Error updating About page.', 'error');
                }
            }
            ?>

            <div class="content-card">
                <!-- Settings Tabs -->
                <ul class="nav nav-tabs settings-tabs" id="settingsTabs">
                    <?php $first = true; ?>
                    <?php foreach ($settings as $category => $category_settings): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $first ? 'active' : ''; ?>" data-bs-toggle="tab"
                                href="#<?php echo $category; ?>">
                                <?php echo ucfirst($category); ?>
                            </a>
                        </li>
                        <?php $first = false; ?>
                    <?php endforeach; ?>

                    <!-- Extra tab for About Page -->
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#aboutPage">About Page</a>
                    </li>
                </ul>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                    <div class="tab-content mt-4">
                        <?php $first = true; ?>
                        <?php foreach ($settings as $category => $category_settings): ?>
                            <div class="tab-pane fade <?php echo $first ? 'show active' : ''; ?>"
                                id="<?php echo $category; ?>">
                                <div class="row">
                                    <?php foreach ($category_settings as $setting): ?>
                                        <div class="col-lg-6 mb-3">
                                            <label for="<?php echo $setting['setting_name']; ?>" class="form-label">
                                                <?php echo ucwords(str_replace('_', ' ', $setting['setting_name'])); ?>
                                            </label>

                                            <?php if ($setting['setting_type'] === 'textarea'): ?>
                                                <textarea class="form-control" id="<?php echo $setting['setting_name']; ?>"
                                                    name="<?php echo $setting['setting_name']; ?>"
                                                    rows="3"><?php echo htmlspecialchars($setting['setting_value']); ?></textarea>
                                            <?php elseif ($setting['setting_type'] === 'number'): ?>
                                                <input type="number" class="form-control"
                                                    id="<?php echo $setting['setting_name']; ?>"
                                                    name="<?php echo $setting['setting_name']; ?>"
                                                    value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                            <?php elseif ($setting['setting_type'] === 'boolean'): ?>
                                                <select class="form-select" id="<?php echo $setting['setting_name']; ?>"
                                                    name="<?php echo $setting['setting_name']; ?>">
                                                    <option value="1" <?php echo $setting['setting_value'] == '1' ? 'selected' : ''; ?>>Yes</option>
                                                    <option value="0" <?php echo $setting['setting_value'] == '0' ? 'selected' : ''; ?>>No</option>
                                                </select>
                                            <?php else: ?>
                                                <input
                                                    type="<?php echo $setting['setting_type'] === 'text' ? 'text' : $setting['setting_type']; ?>"
                                                    class="form-control" id="<?php echo $setting['setting_name']; ?>"
                                                    name="<?php echo $setting['setting_name']; ?>"
                                                    value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                            <?php endif; ?>

                                            <?php if ($setting['description']): ?>
                                                <div class="form-text"><?php echo htmlspecialchars($setting['description']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php $first = false; ?>
                        <?php endforeach; ?>

                        <!-- About Page Editor -->
                        <div class="tab-pane fade" id="aboutPage">
                            <h5>Edit About Page</h5>
                            <input type="hidden" name="update_about_page" value="1">

                            <div class="mb-3">
                                <label for="title" class="form-label">Page Title</label>
                                <input type="text" class="form-control" id="title" name="title"
                                    value="<?php echo htmlspecialchars($about_page['title'] ?? ''); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="content" class="form-label">Page Content</label>
                                <textarea class="form-control" id="content" name="content"
                                    rows="10"><?php echo htmlspecialchars($about_page['content'] ?? ''); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="meta_title" class="form-label">Meta Title</label>
                                <input type="text" class="form-control" id="meta_title" name="meta_title"
                                    value="<?php echo htmlspecialchars($about_page['meta_title'] ?? ''); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="meta_description" class="form-label">Meta Description</label>
                                <textarea class="form-control" id="meta_description" name="meta_description"
                                    rows="3"><?php echo htmlspecialchars($about_page['meta_description'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Save Settings
                        </button>
                    </div>
                </form>
            </div>

            </form>
        </div>
    </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle for mobile
        document.getElementById('sidebarToggle').addEventListener('click', function () {
            document.querySelector('.sidebar').classList.toggle('show');
        });
    </script>
</body>

</html>