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
            $title = sanitize_input($_POST['title'] ?? '');
            $content = clean_html($_POST['content'] ?? '');
            $excerpt = sanitize_input($_POST['excerpt'] ?? '');
            $category = sanitize_input($_POST['category'] ?? '');
            $tags = sanitize_input($_POST['tags'] ?? '');
            $status = in_array($_POST['status'] ?? '', ['draft', 'published']) ? $_POST['status'] : 'draft';
            $is_featured = isset($_POST['is_featured']) ? true : false;
            
            // Handle author - can be text or selected ID
            $author_name = sanitize_input($_POST['author_name'] ?? '');
            if (empty($author_name)) {
                $author_name = $_SESSION['admin_name']; // Default to current admin
            }
            
            // Handle publish date
            $publish_date = null;
            if (!empty($_POST['publish_date']) && $status === 'published') {
                $publish_date = date('Y-m-d H:i:s', strtotime($_POST['publish_date'] . ' ' . ($_POST['publish_time'] ?? '12:00')));
            } elseif ($status === 'published') {
                $publish_date = date('Y-m-d H:i:s'); // Current time if published without date
            }
            
            // Handle image upload
            $featured_image = null;
            if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
                // Ensure upload directory exists
                $upload_dir = dirname(__DIR__) . '/uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Validate image
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $file_type = $_FILES['featured_image']['type'];
                
                if (in_array($file_type, $allowed_types) && $_FILES['featured_image']['size'] <= 5 * 1024 * 1024) {
                    $extension = pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION);
                    $filename = uniqid() . '_' . time() . '.' . strtolower($extension);
                    $upload_path = $upload_dir . $filename;
                    
                    if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $upload_path)) {
                        $featured_image = $filename;
                    } else {
                        set_flash_message('Failed to upload image. Check folder permissions.', 'error');
                    }
                } else {
                    set_flash_message('Invalid image file. Please use JPG, PNG, GIF, or WebP under 5MB.', 'error');
                }
            }
            
            if ($action === 'create') {
                // Create new post with custom author handling
                try {
                    $slug = generate_unique_slug($title, 'posts');
                    $stmt = $pdo->prepare("
                        INSERT INTO posts (title, slug, content, excerpt, category, tags, author_id, author_name, 
                                         status, is_featured, featured_image, published_at, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    
                    if ($stmt->execute([
                        $title, $slug, $content, $excerpt, $category, $tags, 
                        $_SESSION['admin_id'], $author_name, $status, $is_featured ? 1 : 0, 
                        $featured_image, $publish_date
                    ])) {
                        set_flash_message('Post created successfully!', 'success');
                    } else {
                        set_flash_message('Failed to create post.', 'error');
                    }
                } catch(PDOException $e) {
                    error_log('Post creation error: ' . $e->getMessage());
                    set_flash_message('Failed to create post. Please try again.', 'error');
                }
            } else {
                // Update existing post
                $post_id = (int)($_POST['post_id'] ?? 0);
                try {
                    $sql = "UPDATE posts SET title = ?, content = ?, excerpt = ?, category = ?, tags = ?, 
                           author_name = ?, status = ?, is_featured = ?, updated_at = NOW()";
                    $params = [$title, $content, $excerpt, $category, $tags, $author_name, $status, $is_featured ? 1 : 0];
                    
                    if ($featured_image !== null) {
                        $sql .= ", featured_image = ?";
                        $params[] = $featured_image;
                    }
                    
                    if ($publish_date !== null) {
                        $sql .= ", published_at = ?";
                        $params[] = $publish_date;
                    }
                    
                    $sql .= " WHERE id = ?";
                    $params[] = $post_id;
                    
                    $stmt = $pdo->prepare($sql);
                    if ($stmt->execute($params)) {
                        set_flash_message('Post updated successfully!', 'success');
                    } else {
                        set_flash_message('Failed to update post.', 'error');
                    }
                } catch(PDOException $e) {
                    error_log('Post update error: ' . $e->getMessage());
                    set_flash_message('Failed to update post. Please try again.', 'error');
                }
            }
        } elseif ($action === 'delete') {
            $post_id = (int)($_POST['post_id'] ?? 0);
            if (delete_post($post_id)) {
                set_flash_message('Post deleted successfully!', 'success');
            } else {
                set_flash_message('Failed to delete post.', 'error');
            }
        }
    }
    header('Location: ' . ADMIN_URL . '/posts.php');
    exit;
}

// Get posts
$page = (int)($_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;

// Get all posts for admin (including drafts)
try {
    // Update the query to use author_name instead of joining with admin_users
    $stmt = $pdo->prepare("
        SELECT p.*
        FROM posts p 
        ORDER BY p.created_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$limit, $offset]);
    $posts = $stmt->fetchAll();
    
    // Get total count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts");
    $stmt->execute();
    $total_posts = $stmt->fetchColumn();
    $total_pages = ceil($total_posts / $limit);
} catch(PDOException $e) {
    $posts = [];
    $total_posts = 0;
    $total_pages = 0;
}

// Get categories
$categories = get_categories();

// Handle edit mode
$editing_post = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
        $stmt->execute([$_GET['edit']]);
        $editing_post = $stmt->fetch();
    } catch(PDOException $e) {
        $editing_post = null;
    }
}

$page_title = 'Posts Management';
$site_name = get_setting('site_name', 'Newspower');
$flash_message = get_flash_message();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Posts Management - <?php echo htmlspecialchars($site_name); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Quill Editor -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
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
        
        .editor-container {
            height: 300px;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-published {
            background-color: #d1e7dd;
            color: #0a3622;
        }
        
        .status-draft {
            background-color: #fff3cd;
            color: #664d03;
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
        
        .post-thumbnail {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
        }

        /* Enhanced Image Styling in Editor */
        .ql-editor img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .ql-editor img:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
        }

        /* Image alignment classes */
        .ql-editor img.img-left {
            float: left;
            margin: 10px 20px 15px 0;
            max-width: 45%;
        }

        .ql-editor img.img-center {
            display: block;
            margin: 20px auto;
            text-align: center;
        }

        .ql-editor img.img-right {
            float: right;
            margin: 10px 0 15px 20px;
            max-width: 45%;
        }

        /* Image size classes */
        .ql-editor img.img-small { max-width: 250px !important; }
        .ql-editor img.img-medium { max-width: 450px !important; }
        .ql-editor img.img-large { max-width: 700px !important; }
        .ql-editor img.img-full { max-width: 100% !important; }

        /* Image Modal Styling */
        .image-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .image-modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 450px;
            width: 90%;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .image-modal h5 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-weight: 600;
        }

        .image-option-group {
            margin-bottom: 20px;
        }

        .image-option-group label {
            font-weight: 600;
            margin-bottom: 10px;
            display: block;
            color: #333;
        }

        /* Enhanced Tag Input Styling */
        .tag-input-container {
            position: relative;
        }

        .tag-preview {
            min-height: 40px;
            border: 1px dashed #dee2e6;
            border-radius: 8px;
            padding: 12px;
            margin-top: 10px;
            background: #f8f9fa;
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }

        .tag-item {
            display: inline-block;
            background: linear-gradient(45deg, var(--primary-color), var(--primary-hover));
            color: white;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .tag-item:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(0,0,0,0.2);
            color: white;
        }

        .tag-suggestions {
            margin-top: 10px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }

        .tag-suggestions small {
            color: #6c757d;
            font-weight: 500;
        }

        .quick-tag {
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .quick-tag:hover {
            background-color: var(--primary-color) !important;
            color: white !important;
            transform: scale(1.05);
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

            .ql-editor img.img-left,
            .ql-editor img.img-right {
                float: none;
                display: block;
                margin: 15px auto;
                max-width: 90% !important;
            }

            .image-modal-content {
                margin: 20px;
                padding: 20px;
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
                <a href="<?php echo ADMIN_URL; ?>/posts.php" class="nav-link active">
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
                <h5 class="mb-0">Posts Management</h5>
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

        <div class="container-fluid p-4">
            <!-- Flash Message -->
            <?php if ($flash_message): ?>
                <div class="alert alert-<?php echo $flash_message['type']; ?> alert-dismissible fade show">
                    <i class="fas fa-<?php echo $flash_message['type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                    <?php echo htmlspecialchars($flash_message['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Posts Content -->
            <div class="row">
                <div class="col-12">
                    <!-- Post Form -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <?php echo $editing_post ? 'Edit Post' : 'Create New Post'; ?>
                            </h5>
                            <?php if ($editing_post): ?>
                                <a href="<?php echo ADMIN_URL; ?>/posts.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i>Cancel Edit
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <input type="hidden" name="action" value="<?php echo $editing_post ? 'update' : 'create'; ?>">
                                <?php if ($editing_post): ?>
                                    <input type="hidden" name="post_id" value="<?php echo $editing_post['id']; ?>">
                                <?php endif; ?>
                                
                                <div class="row">
                                    <div class="col-lg-8">
                                        <div class="mb-3">
                                            <label for="title" class="form-label">Post Title *</label>
                                            <input type="text" class="form-control" id="title" name="title" 
                                                   value="<?php echo $editing_post ? htmlspecialchars($editing_post['title']) : ''; ?>" 
                                                   required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="excerpt" class="form-label">Excerpt</label>
                                            <textarea class="form-control" id="excerpt" name="excerpt" rows="2" 
                                                      placeholder="Brief description of the post..."><?php echo $editing_post ? htmlspecialchars($editing_post['excerpt']) : ''; ?></textarea>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="content" class="form-label">Content *</label>
                                            <div class="position-relative">
                                                <div id="editor" style="height: 400px;"><?php echo $editing_post ? $editing_post['content'] : ''; ?></div>
                                                <textarea name="content" id="hidden-content" style="display: none;"></textarea>
                                            </div>
                                            <small class="text-muted mt-2 d-block">
                                                <i class="fas fa-lightbulb me-1"></i>
                                                <strong>Image Tips:</strong> After inserting an image, click on it to adjust size and alignment. Use headings, quotes, and lists to make your content more engaging.
                                            </small>
                                        </div>
                                    </div>
                                    
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label for="author_name" class="form-label">Author Name *</label>
                                            <input type="text" class="form-control" id="author_name" name="author_name" 
                                                   value="<?php echo $editing_post ? htmlspecialchars($editing_post['author_name'] ?? '') : htmlspecialchars($_SESSION['admin_name']); ?>" 
                                                   placeholder="Enter author name" required>
                                            <small class="text-muted">Can be any name, not limited to registered users</small>
                                        </div>
                                    
                                        <div class="mb-3">
                                            <label for="status" class="form-label">Status</label>
                                            <select class="form-select" id="status" name="status">
                                                <option value="draft" <?php echo ($editing_post && $editing_post['status'] === 'draft') ? 'selected' : ''; ?>>Draft</option>
                                                <option value="published" <?php echo ($editing_post && $editing_post['status'] === 'published') ? 'selected' : ''; ?>>Published</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="publish_date" class="form-label">Publish Date</label>
                                            <input type="date" class="form-control" id="publish_date" name="publish_date" 
                                                   value="<?php 
                                                   if ($editing_post && $editing_post['published_at']) {
                                                       echo date('Y-m-d', strtotime($editing_post['published_at']));
                                                   } else {
                                                       echo date('Y-m-d');
                                                   }
                                                   ?>">
                                            <small class="text-muted">Leave as today or set custom date for backdating</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="publish_time" class="form-label">Publish Time</label>
                                            <input type="time" class="form-control" id="publish_time" name="publish_time" 
                                                   value="<?php 
                                                   if ($editing_post && $editing_post['published_at']) {
                                                       echo date('H:i', strtotime($editing_post['published_at']));
                                                   } else {
                                                       echo date('H:i');
                                                   }
                                                   ?>">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="category" class="form-label">Category *</label>
                                            <select class="form-select" id="category" name="category" required>
                                                <option value="">Select Category</option>
                                                <?php if (!empty($categories)): ?>
                                                    <?php foreach ($categories as $category): ?>
                                                        <option value="<?php echo htmlspecialchars($category['name']); ?>" 
                                                                <?php echo ($editing_post && $editing_post['category'] === $category['name']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($category['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <option value="General">General</option>
                                                    <option value="News">News</option>
                                                    <option value="Sports">Sports</option>
                                                    <option value="Technology">Technology</option>
                                                    <option value="Entertainment">Entertainment</option>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="tag-input-container">
                                                <label for="tags" class="form-label">Tags</label>
                                                <input type="text" class="form-control" id="tags" name="tags" 
                                                       value="<?php echo $editing_post ? htmlspecialchars($editing_post['tags']) : ''; ?>" 
                                                       placeholder="technology, news, innovation">
                                                <small class="text-muted">Separate tags with commas. Use 5-8 relevant tags for better SEO.</small>
                                                
                                                <div id="tag-preview" class="tag-preview" style="display: none;"></div>
                                                
                                                <div class="tag-suggestions">
                                                    <small class="text-muted">Popular tags: </small>
                                                    <span class="badge bg-light text-dark me-1 quick-tag" onclick="addQuickTag('Breaking News')">Breaking News</span>
                                                    <span class="badge bg-light text-dark me-1 quick-tag" onclick="addQuickTag('Politics')">Politics</span>
                                                    <span class="badge bg-light text-dark me-1 quick-tag" onclick="addQuickTag('Sports')">Sports</span>
                                                    <span class="badge bg-light text-dark me-1 quick-tag" onclick="addQuickTag('Technology')">Technology</span>
                                                    <span class="badge bg-light text-dark me-1 quick-tag" onclick="addQuickTag('Business')">Business</span>
                                                    <span class="badge bg-light text-dark me-1 quick-tag" onclick="addQuickTag('Entertainment')">Entertainment</span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="featured_image" class="form-label">Featured Image</label>
                                            <input type="file" class="form-control" id="featured_image" name="featured_image" accept="image/*">
                                            <?php if ($editing_post && $editing_post['featured_image']): ?>
                                                <div class="mt-2">
                                                    <img src="<?php echo SITE_URL; ?>/uploads/<?php echo htmlspecialchars($editing_post['featured_image']); ?>" 
                                                         alt="Current image" style="max-width: 100px; height: auto;" class="rounded">
                                                    <small class="d-block text-muted">Current: <?php echo htmlspecialchars($editing_post['featured_image']); ?></small>
                                                </div>
                                            <?php endif; ?>
                                            <small class="text-muted">Max 5MB. JPG, PNG, GIF, WebP allowed.</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" 
                                                       <?php echo ($editing_post && $editing_post['is_featured']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="is_featured">
                                                    Featured Post
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-save me-1"></i>
                                            <?php echo $editing_post ? 'Update Post' : 'Create Post'; ?>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Posts List -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">All Posts (<?php echo number_format($total_posts); ?>)</h5>
                        </div>
                        
                        <div class="card-body">
                            <?php if (empty($posts)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No posts found. Create your first post above!</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Post</th>
                                                <th>Author</th>
                                                <th>Status</th>
                                                <th>Published</th>
                                                <th>Views</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($posts as $post): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <?php if ($post['featured_image']): ?>
                                                                <img src="<?php echo SITE_URL; ?>/uploads/<?php echo htmlspecialchars($post['featured_image']); ?>" 
                                                                     alt="Thumbnail" class="me-3 rounded" 
                                                                     style="width: 50px; height: 50px; object-fit: cover;">
                                                            <?php else: ?>
                                                                <div class="me-3 bg-light rounded d-flex align-items-center justify-content-center" 
                                                                     style="width: 50px; height: 50px;">
                                                                    <i class="fas fa-image text-muted"></i>
                                                                </div>
                                                            <?php endif; ?>
                                                            <div>
                                                                <h6 class="mb-1"><?php echo htmlspecialchars(truncate_text($post['title'], 50)); ?></h6>
                                                                <?php if ($post['is_featured']): ?>
                                                                    <span class="badge bg-warning text-dark">Featured</span>
                                                                <?php endif; ?>
                                                                <small class="text-muted d-block">
                                                                    <?php echo htmlspecialchars($post['category']); ?>
                                                                </small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($post['author_name'] ?? 'Unknown'); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $post['status'] == 'published' ? 'success' : 'warning'; ?>">
                                                            <?php echo ucfirst($post['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($post['published_at']): ?>
                                                            <?php echo format_date($post['published_at']); ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">Not published</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo number_format($post['views_count']); ?></td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <?php if ($post['status'] === 'published'): ?>
                                                                <a href="<?php echo SITE_URL; ?>/post.php?slug=<?php echo $post['slug']; ?>" 
                                                                   class="btn btn-outline-primary" target="_blank" title="View Post">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                            <a href="<?php echo ADMIN_URL; ?>/posts.php?edit=<?php echo $post['id']; ?>" 
                                                               class="btn btn-outline-secondary" title="Edit Post">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <form method="POST" class="d-inline" 
                                                                  onsubmit="return confirm('Are you sure you want to delete this post?');">
                                                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                                <input type="hidden" name="action" value="delete">
                                                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                                                <button type="submit" class="btn btn-outline-danger" title="Delete Post">
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

                                <!-- Pagination -->
                                <?php if ($total_pages > 1): ?>
                                    <nav aria-label="Posts pagination" class="mt-4">
                                        <ul class="pagination justify-content-center">
                                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                                            </li>
                                            
                                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                                </li>
                                            <?php endfor; ?>
                                            
                                            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                                            </li>
                                        </ul>
                                    </nav>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Quill Editor -->
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

    <script>
        // Initialize Quill editor with enhanced configuration
        var quill = new Quill('#editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    ['blockquote', 'code-block'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'script': 'sub'}, { 'script': 'super' }],
                    [{ 'indent': '-1'}, { 'indent': '+1' }],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'align': [] }],
                    ['link', 'image'],
                    ['clean']
                ]
            }
        });

        // Enhanced image handler
        quill.getModule('toolbar').addHandler('image', function() {
            const input = document.createElement('input');
            input.setAttribute('type', 'file');
            input.setAttribute('accept', 'image/*');
            input.click();
            
            input.addEventListener('change', function() {
                const file = input.files[0];
                if (file) {
                    // Validate file size (5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        alert('Image must be under 5MB. Please choose a smaller image.');
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const range = quill.getSelection(true);
                        
                        // Insert image
                        quill.insertEmbed(range.index, 'image', e.target.result);
                        
                        // Move cursor after image
                        quill.setSelection(range.index + 1);
                        
                        // Add click handler to newly inserted image
                        setTimeout(() => {
                            const images = quill.container.querySelectorAll('img');
                            const lastImage = images[images.length - 1];
                            
                            if (lastImage && !lastImage.hasAttribute('data-click-handler')) {
                                lastImage.setAttribute('data-click-handler', 'true');
                                lastImage.classList.add('img-center', 'img-medium'); // Default settings
                                lastImage.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    showImageOptions(this);
                                });
                            }
                        }, 100);
                    };
                    reader.readAsDataURL(file);
                }
            });
        });

        // Add click handlers to existing images when editing
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                const images = quill.container.querySelectorAll('img');
                images.forEach(img => {
                    if (!img.hasAttribute('data-click-handler')) {
                        img.setAttribute('data-click-handler', 'true');
                        img.addEventListener('click', function(e) {
                            e.preventDefault();
                            showImageOptions(this);
                        });
                    }
                });
            }, 500);
        });

        // Image options modal
        function showImageOptions(img) {
            // Remove existing modal if any
            const existingModal = document.querySelector('.image-modal');
            if (existingModal) {
                existingModal.remove();
            }

            const modal = document.createElement('div');
            modal.className = 'image-modal';
            modal.innerHTML = `
                <div class="image-modal-content">
                    <h5><i class="fas fa-image me-2"></i>Image Settings</h5>
                    
                    <div class="image-option-group">
                        <label>Alignment:</label>
                        <div class="btn-group w-100" role="group">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="setImageAlignment('img-left')">
                                <i class="fas fa-align-left me-1"></i>Left
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="setImageAlignment('img-center')">
                                <i class="fas fa-align-center me-1"></i>Center
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="setImageAlignment('img-right')">
                                <i class="fas fa-align-right me-1"></i>Right
                            </button>
                        </div>
                    </div>
                    
                    <div class="image-option-group">
                        <label>Size:</label>
                        <div class="btn-group w-100" role="group">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setImageSize('img-small')">Small</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setImageSize('img-medium')">Medium</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setImageSize('img-large')">Large</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setImageSize('img-full')">Full</button>
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <button type="button" class="btn btn-primary btn-sm" onclick="closeImageModal()">
                            <i class="fas fa-check me-1"></i>Done
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Store reference to current image
            window.currentEditingImage = img;
            
            // Highlight current settings
            updateModalButtons(img);
        }

        function updateModalButtons(img) {
            // Reset all buttons
            document.querySelectorAll('.image-modal .btn').forEach(btn => {
                btn.classList.remove('active');
            });

            // Highlight current alignment
            if (img.classList.contains('img-left')) {
                document.querySelector('button[onclick*="img-left"]').classList.add('active');
            } else if (img.classList.contains('img-right')) {
                document.querySelector('button[onclick*="img-right"]').classList.add('active');
            } else {
                document.querySelector('button[onclick*="img-center"]').classList.add('active');
            }

            // Highlight current size
            if (img.classList.contains('img-small')) {
                document.querySelector('button[onclick*="img-small"]').classList.add('active');
            } else if (img.classList.contains('img-large')) {
                document.querySelector('button[onclick*="img-large"]').classList.add('active');
            } else if (img.classList.contains('img-full')) {
                document.querySelector('button[onclick*="img-full"]').classList.add('active');
            } else {
                document.querySelector('button[onclick*="img-medium"]').classList.add('active');
            }
        }

        function setImageAlignment(alignment) {
            if (window.currentEditingImage) {
                window.currentEditingImage.classList.remove('img-left', 'img-center', 'img-right');
                window.currentEditingImage.classList.add(alignment);
                
                // Update button states
                document.querySelectorAll('.image-modal button[onclick*="setImageAlignment"]').forEach(btn => {
                    btn.classList.remove('active');
                });
                document.querySelector(`button[onclick*="${alignment}"]`).classList.add('active');
            }
        }

        function setImageSize(size) {
            if (window.currentEditingImage) {
                window.currentEditingImage.classList.remove('img-small', 'img-medium', 'img-large', 'img-full');
                window.currentEditingImage.classList.add(size);
                
                // Update button states
                document.querySelectorAll('.image-modal button[onclick*="setImageSize"]').forEach(btn => {
                    btn.classList.remove('active');
                });
                document.querySelector(`button[onclick*="${size}"]`).classList.add('active');
            }
        }

        function closeImageModal() {
            const modal = document.querySelector('.image-modal');
            if (modal) {
                modal.remove();
            }
            window.currentEditingImage = null;
        }

        // Close modal on outside click
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('image-modal')) {
                closeImageModal();
            }
        });

        // Enhanced tag input functionality
        const tagsInput = document.getElementById('tags');
        const tagPreview = document.getElementById('tag-preview');

        tagsInput.addEventListener('input', function() {
            updateTagPreview();
        });

        function updateTagPreview() {
            const value = tagsInput.value.trim();
            if (!value) {
                tagPreview.style.display = 'none';
                return;
            }

            const tags = value.split(',').map(tag => tag.trim()).filter(tag => tag.length > 0);
            tagPreview.style.display = 'flex';
            tagPreview.innerHTML = '';
            
            if (tags.length === 0) {
                tagPreview.style.display = 'none';
                return;
            }

            tags.forEach(tag => {
                const tagElement = document.createElement('span');
                tagElement.className = 'tag-item';
                tagElement.textContent = tag;
                tagPreview.appendChild(tagElement);
            });

            // Show tag count
            if (tags.length > 8) {
                const warning = document.createElement('small');
                warning.className = 'text-warning';
                warning.innerHTML = `<i class="fas fa-exclamation-triangle me-1"></i>Consider using fewer tags (${tags.length}/8 max recommended)`;
                tagPreview.appendChild(warning);
            }
        }

        function addQuickTag(tag) {
            const currentTags = tagsInput.value.trim();
            const tagsArray = currentTags ? currentTags.split(',').map(t => t.trim()) : [];
            
            // Don't add if tag already exists
            if (tagsArray.includes(tag)) {
                return;
            }
            
            if (currentTags) {
                tagsInput.value = currentTags + ', ' + tag;
            } else {
                tagsInput.value = tag;
            }
            
            updateTagPreview();
            tagsInput.focus();
        }

        // Form submission
        document.querySelector('form').addEventListener('submit', function(e) {
            // Get content from Quill
            let content = quill.root.innerHTML;
            
            // Clean up content
            content = content.replace(/<p><br><\/p>/g, '<p>&nbsp;</p>');
            content = content.replace(/<p>\s*<\/p>/g, '<p>&nbsp;</p>');
            
            document.getElementById('hidden-content').value = content;
            
            // Validate required fields
            const title = document.getElementById('title').value.trim();
            const category = document.getElementById('category').value;
            const authorName = document.getElementById('author_name').value.trim();
            
            if (!title || !category || !authorName) {
                e.preventDefault();
                alert('Please fill in all required fields (Title, Category, Author Name).');
                return;
            }
            
            // Check content length
            const textContent = quill.getText().trim();
            if (textContent.length < 50) {
                e.preventDefault();
                alert('Please write at least 50 characters of content.');
                return;
            }
        });

        // Show/hide date fields based on status
        document.getElementById('status').addEventListener('change', function() {
            const publishDateContainer = document.getElementById('publish_date').closest('.mb-3');
            const publishTimeContainer = document.getElementById('publish_time').closest('.mb-3');
            
            if (this.value === 'published') {
                publishDateContainer.style.display = 'block';
                publishTimeContainer.style.display = 'block';
            } else {
                publishDateContainer.style.display = 'none';
                publishTimeContainer.style.display = 'none';
            }
        });

        // Initial status check
        document.getElementById('status').dispatchEvent(new Event('change'));

        // Initial tag preview
        if (tagsInput.value.trim()) {
            updateTagPreview();
        }

        // Sidebar toggle for mobile
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
        });

        // Auto-save draft functionality (optional)
        let autoSaveTimeout;
        function autoSave() {
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(() => {
                const title = document.getElementById('title').value.trim();
                if (title && quill.getText().trim().length > 10) {
                    // You can implement auto-save to drafts here
                    console.log('Auto-saving draft...');
                }
            }, 30000); // Auto-save every 30 seconds
        }

        quill.on('text-change', autoSave);
        document.getElementById('title').addEventListener('input', autoSave);
    </script>

</body>
</html>