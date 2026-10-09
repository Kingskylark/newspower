<?php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Check if user is logged in
require_admin_login();
check_session_timeout();

// Get dashboard statistics
$total_posts = get_total_posts();
$total_views = get_total_views();
$total_messages = get_total_messages();
$recent_views = get_recent_views(7);
$popular_posts = get_popular_posts(5);

// Get recent posts with author names
try {
    $stmt = $pdo->prepare("
        SELECT p.*, au.full_name as author_name 
        FROM posts p 
        LEFT JOIN admin_users au ON p.author_id = au.id 
        WHERE p.status = 'published'
        ORDER BY p.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $recent_posts = $stmt->fetchAll();
} catch(PDOException $e) {
    $recent_posts = [];
}

$site_name = get_setting('site_name', 'Newspower');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo htmlspecialchars($site_name); ?></title>
    
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

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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
        
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid var(--primary-color);
            transition: transform 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
        }
        
        .stats-card.secondary {
            border-left-color: var(--secondary-color);
        }
        
        .stats-card.success {
            border-left-color: #28a745;
        }
        
        .stats-card.warning {
            border-left-color: #ffc107;
        }
        
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: 400px; /* Fixed height */
        }
        
        .chart-wrapper {
            position: relative;
            height: 300px; /* Fixed chart height */
            width: 100%;
        }
        
        .recent-activity {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .activity-item {
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .activity-item:last-child {
            border-bottom: none;
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
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header text-white">
            <h4 class="mb-1"><i class="fas fa-newspaper me-2"></i><?php echo htmlspecialchars($site_name); ?></h4>
            <small class="opacity-75">Admin Dashboard</small>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-item">
                <a href="<?php echo ADMIN_URL; ?>" class="nav-link active">
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
                <h5 class="mb-0">Dashboard</h5>
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

        <!-- Dashboard Content -->
        <div class="container-fluid p-4">
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-1"><?php echo number_format($total_posts); ?></h3>
                                <p class="text-muted mb-0">Published Posts</p>
                            </div>
                            <div class="text-primary">
                                <i class="fas fa-file-alt fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card secondary">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-1"><?php echo number_format($total_views); ?></h3>
                                <p class="text-muted mb-0">Total Views</p>
                            </div>
                            <div style="color: var(--secondary-color);">
                                <i class="fas fa-eye fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card success">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-1"><?php echo number_format($total_messages); ?></h3>
                                <p class="text-muted mb-0">Contact Messages</p>
                            </div>
                            <div class="text-success">
                                <i class="fas fa-envelope fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card warning">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-1"><?php echo count($recent_views); ?></h3>
                                <p class="text-muted mb-0">Active Days</p>
                            </div>
                            <div class="text-warning">
                                <i class="fas fa-calendar-day fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Views Chart -->
                <div class="col-lg-8 mb-4">
                    <div class="chart-container">
                        <h5 class="mb-3">Views Over Time (Last 7 Days)</h5>
                        <div class="chart-wrapper">
                            <canvas id="viewsChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Popular Posts -->
                <div class="col-lg-4 mb-4">
                    <div class="recent-activity">
                        <h5 class="mb-3">Popular Posts</h5>
                        <?php if (empty($popular_posts)): ?>
                            <p class="text-muted">No posts available</p>
                        <?php else: ?>
                            <?php foreach ($popular_posts as $post): ?>
                                <div class="activity-item">
                                    <div class="d-flex justify-content-between">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?php echo htmlspecialchars(truncate_text($post['title'], 40)); ?></h6>
                                            <small class="text-muted">
                                                <i class="fas fa-eye me-1"></i><?php echo number_format($post['views_count']); ?> views
                                                <i class="fas fa-share ms-2 me-1"></i><?php echo number_format($post['shares_count']); ?> shares
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Posts -->
            <div class="row">
                <div class="col-12">
                    <div class="recent-activity">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Recent Posts</h5>
                            <a href="<?php echo ADMIN_URL; ?>/posts.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-1"></i>New Post
                            </a>
                        </div>
                        
                        <?php if (empty($recent_posts)): ?>
                            <p class="text-muted">No posts available</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Author</th>
                                            <th>Published</th>
                                            <th>Views</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_posts as $post): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($post['title']); ?></strong>
                                                    <?php if ($post['is_featured']): ?>
                                                        <span class="badge bg-warning text-dark ms-1">Featured</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($post['author_name'] ?? 'Unknown'); ?></td>
                                                <td><?php echo format_date($post['published_at'] ?? $post['created_at']); ?></td>
                                                <td><?php echo number_format($post['views_count']); ?></td>
                                                <td>
                                                    <a href="<?php echo get_post_url($post['slug']); ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle for mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
        });

        // Views Chart with fixed height
        const ctx = document.getElementById('viewsChart').getContext('2d');
        const viewsData = <?php echo json_encode(array_reverse($recent_views)); ?>;
        
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: viewsData.length > 0 ? viewsData.map(item => {
                    const date = new Date(item.date);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                }) : ['No Data'],
                datasets: [{
                    label: 'Page Views',
                    data: viewsData.length > 0 ? viewsData.map(item => item.views) : [0],
                    borderColor: 'rgb(32, 57, 97)',
                    backgroundColor: 'rgba(32, 57, 97, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>