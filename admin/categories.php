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
        $action = $_POST['action'] ?? '';
        
        if ($action === 'create' || $action === 'update') {
            $name = sanitize_input($_POST['name'] ?? '');
            $description = sanitize_input($_POST['description'] ?? '');
            $color = sanitize_input($_POST['color'] ?? '#007bff');
            
            if (empty($name)) {
                set_flash_message('Category name is required.', 'error');
            } else {
                if ($action === 'create') {
                    if (create_category($name, $description, $color)) {
                        set_flash_message('Category created successfully!', 'success');
                    } else {
                        set_flash_message('Failed to create category. Name might already exist.', 'error');
                    }
                } else {
                    $category_id = (int)($_POST['category_id'] ?? 0);
                    if (update_category($category_id, $name, $description, $color)) {
                        set_flash_message('Category updated successfully!', 'success');
                    } else {
                        set_flash_message('Failed to update category.', 'error');
                    }
                }
            }
        } elseif ($action === 'delete') {
            $category_id = (int)($_POST['category_id'] ?? 0);
            if (delete_category($category_id)) {
                set_flash_message('Category deleted successfully!', 'success');
            } else {
                set_flash_message('Failed to delete category. It may be in use by posts.', 'error');
            }
        }
    }
    header('Location: ' . ADMIN_URL . '/categories.php');
    exit;
}

// Get categories
$categories = get_categories();

// Handle edit mode
$editing_category = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$_GET['edit']]);
        $editing_category = $stmt->fetch();
    } catch(PDOException $e) {
        $editing_category = null;
    }
}

$site_name = get_setting('site_name', 'Newspower');
$flash_message = get_flash_message();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories Management - <?php echo htmlspecialchars($site_name); ?></title>
    
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
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-nav {
            padding: 1rem 0;
        }
        
        .nav-item {
            margin: 0.25rem 0;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.75rem 1.5rem;
            border-radius: 0;
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
        }
        
        .nav-link:hover {
            color: white;
            background-color: rgba(255,255,255,0.1);
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
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .content-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
        
        .category-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            display: inline-block;
        }
        
        .color-picker {
            width: 50px;
            height: 40px;
            border: 1px solid #ced4da;
            border-radius: 4px;
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
                <a href="<?php echo ADMIN_URL; ?>/categories.php" class="nav-link active">
                    <i class="fas fa-tags me-2"></i>Categories
                </a>
            </div>
            <div class="nav-item">
                <a href="<?php echo ADMIN_URL; ?>/users.php" class="nav-link">
                    <i class="fas fa-users me-2"></i>Users
                </a>
            </div>
            <div class="nav-item">
                <a href="<?php echo ADMIN_URL; ?>/settings.php" class="nav-link">
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
                <h5 class="mb-0">Categories Management</h5>
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
                <div class="alert alert-<?php echo $flash_message['type'] === 'error' ? 'danger' : $flash_message['type']; ?> alert-dismissible fade show">
                    <?php echo htmlspecialchars($flash_message['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Category Form -->
            <div class="content-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        <?php echo $editing_category ? 'Edit Category' : 'Create New Category'; ?>
                    </h5>
                    <?php if ($editing_category): ?>
                        <a href="<?php echo ADMIN_URL; ?>/categories.php" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i>Cancel Edit
                        </a>
                    <?php endif; ?>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="action" value="<?php echo $editing_category ? 'update' : 'create'; ?>">
                    <?php if ($editing_category): ?>
                        <input type="hidden" name="category_id" value="<?php echo $editing_category['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="name" class="form-label">Category Name</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo $editing_category ? htmlspecialchars($editing_category['name']) : ''; ?>" 
                                       required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <input type="text" class="form-control" id="description" name="description" 
                                       value="<?php echo $editing_category ? htmlspecialchars($editing_category['description']) : ''; ?>" 
                                       placeholder="Optional description">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-3">
                                <label for="color" class="form-label">Color</label>
                                <input type="color" class="form-control color-picker" id="color" name="color" 
                                       value="<?php echo $editing_category ? htmlspecialchars($editing_category['color']) : '#007bff'; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>
                        <?php echo $editing_category ? 'Update Category' : 'Create Category'; ?>
                    </button>
                </form>
            </div>

            <!-- Categories List -->
            <div class="content-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">All Categories (<?php echo count($categories); ?>)</h5>
                </div>
                
                <?php if (empty($categories)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No categories found. Create your first category above!</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Color</th>
                                    <th>Posts Count</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($category['description'] ?? ''); ?></td>
                                        <td>
                                            <span class="category-color" style="background-color: <?php echo htmlspecialchars($category['color']); ?>"></span>
                                            <?php echo htmlspecialchars($category['color']); ?>
                                        </td>
                                        <td><?php echo get_category_posts_count($category['slug']); ?></td>
                                        <td><?php echo format_date($category['created_at']); ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="<?php echo ADMIN_URL; ?>/categories.php?edit=<?php echo $category['id']; ?>" 
                                                   class="btn btn-sm btn-outline-secondary" title="Edit Category">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form method="POST" class="d-inline" 
                                                      onsubmit="return confirm('Are you sure you want to delete this category?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Category">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle for mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
        });
    </script>
</body>
</html>