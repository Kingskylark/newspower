<?php
session_start();
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

require_admin_login();

$page_title = 'Analytics Dashboard';

// Get analytics data
$analytics = [
    'total_posts' => get_total_posts(),
    'total_views' => get_total_views(),
    'total_messages' => get_total_messages(),
    'recent_views' => get_recent_views(30),
    'popular_posts' => get_popular_posts(10)
];

// Get additional analytics data
try {
    // Views by day (last 30 days)
    $stmt = $pdo->prepare("
        SELECT DATE(viewed_at) as date, COUNT(*) as views 
        FROM page_views 
        WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
        GROUP BY DATE(viewed_at) 
        ORDER BY date ASC
    ");
    $stmt->execute();
    $daily_views = $stmt->fetchAll();

    // Top categories by views
    $stmt = $pdo->prepare("
        SELECT c.name, SUM(p.views_count) as total_views, COUNT(p.id) as post_count
        FROM posts p 
        LEFT JOIN categories c ON p.category = c.id 
        WHERE p.status = 'published' 
        GROUP BY c.name 
        ORDER BY total_views DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $top_categories = $stmt->fetchAll();

    // Recent activity (posts, messages)
    $stmt = $pdo->prepare("
        (SELECT 'post' as type, title as content, created_at FROM posts WHERE status = 'published' ORDER BY created_at DESC LIMIT 5)
        UNION ALL
        (SELECT 'message' as type, CONCAT(name, ': ', SUBSTRING(message, 1, 50)) as content, created_at FROM contact_messages ORDER BY created_at DESC LIMIT 5)
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recent_activity = $stmt->fetchAll();

    // Browser/device stats (if you track this)
    $browser_stats = []; // Placeholder - would need user agent tracking

} catch (PDOException $e) {
    $daily_views = [];
    $top_categories = [];
    $recent_activity = [];
    $browser_stats = [];
}

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
                <a href="<?php echo ADMIN_URL; ?>/settings.php" class="nav-link">
                    <i class="fas fa-cog me-2"></i>Settings
                </a>
            </div>
            <div class="nav-item">
                <a href="<?php echo ADMIN_URL; ?>/analytics.php" class="nav-link active">
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
          <!-- Top Navbar -->
       <div class="top-navbar d-flex justify-content-between align-items-center">
            <div>
                <button class="btn btn-outline-secondary d-md-none me-2" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h5 class="mb-0">Analytics Dashboard</h5>
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

        <!-- Overview Stats -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total Page Views
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo number_format($analytics['total_views']); ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-eye fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Published Posts
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo number_format($analytics['total_posts']); ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-newspaper fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Contact Messages
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo number_format($analytics['total_messages']); ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-envelope fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Avg. Views/Post
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php
                                    $avg_views = $analytics['total_posts'] > 0 ?
                                        round($analytics['total_views'] / $analytics['total_posts']) : 0;
                                    echo number_format($avg_views);
                                    ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-chart-bar fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row">
            <!-- Views Chart -->
            <div class="col-xl-8 col-lg-7">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Page Views Overview (Last 30 Days)</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-area">
                            <canvas id="viewsChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Categories -->
            <div class="col-xl-4 col-lg-5">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Top Categories</h6>
                    </div>
                    <div class="card-body">
                        <?php if ($top_categories): ?>
                            <?php foreach ($top_categories as $index => $category): ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span
                                            class="font-weight-bold"><?php echo htmlspecialchars($category['name'] ?: 'Uncategorized'); ?></span>
                                        <span class="text-muted"><?php echo number_format($category['total_views']); ?>
                                            views</span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <?php
                                        $max_views = $top_categories[0]['total_views'];
                                        $percentage = $max_views > 0 ? ($category['total_views'] / $max_views) * 100 : 0;
                                        ?>
                                        <div class="progress-bar bg-primary" role="progressbar"
                                            style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                    <small class="text-muted"><?php echo $category['post_count']; ?> posts</small>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-chart-pie fa-2x text-muted mb-2"></i>
                                <p class="text-muted">No category data available.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Popular Posts & Recent Activity -->
        <div class="row">
            <!-- Popular Posts -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Most Popular Posts</h6>
                    </div>
                    <div class="card-body">
                        <?php if ($analytics['popular_posts']): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Post</th>
                                            <th>Views</th>
                                            <th>Shares</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($analytics['popular_posts'] as $post): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars(truncate_text($post['title'], 40)); ?></strong>
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge bg-primary"><?php echo number_format($post['views_count']); ?></span>
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge bg-success"><?php echo number_format($post['shares_count']); ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-star fa-2x text-muted mb-2"></i>
                                <p class="text-muted">No popular posts data available.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Recent Activity</h6>
                    </div>
                    <div class="card-body">
                        <?php if ($recent_activity): ?>
                            <?php foreach ($recent_activity as $activity): ?>
                                <div class="d-flex align-items-center py-2 border-bottom">
                                    <div class="flex-shrink-0">
                                        <div class="bg-<?php echo $activity['type'] === 'post' ? 'primary' : 'success'; ?> rounded-circle d-flex align-items-center justify-content-center"
                                            style="width: 35px; height: 35px;">
                                            <i
                                                class="fas fa-<?php echo $activity['type'] === 'post' ? 'newspaper' : 'envelope'; ?> text-white"></i>
                                        </div>
                                    </div>
                                    <div class="ms-3">
                                        <h6 class="mb-1"><?php echo ucfirst($activity['type']); ?></h6>
                                        <p class="mb-1 small">
                                            <?php echo htmlspecialchars(truncate_text($activity['content'], 50)); ?></p>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i><?php echo time_ago($activity['created_at']); ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-history fa-2x text-muted mb-2"></i>
                                <p class="text-muted">No recent activity.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Analytics -->
        <div class="row">
            <div class="col-lg-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Traffic Summary</h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3 mb-3">
                                <div class="border-end">
                                    <h4 class="text-primary mb-1">
                                        <?php echo number_format($analytics['total_views']); ?></h4>
                                    <small class="text-muted">Total Views</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="border-end">
                                    <h4 class="text-success mb-1"><?php echo count($daily_views); ?></h4>
                                    <small class="text-muted">Active Days</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="border-end">
                                    <h4 class="text-info mb-1"><?php echo count($top_categories); ?></h4>
                                    <small class="text-muted">Categories</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <h4 class="text-warning mb-1">
                                    <?php
                                    $avg_daily = count($daily_views) > 0 ?
                                        round(array_sum(array_column($daily_views, 'views')) / count($daily_views)) : 0;
                                    echo number_format($avg_daily);
                                    ?>
                                </h4>
                                <small class="text-muted">Avg. Daily Views</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Chart.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

    <script>
        // Views Chart
        const ctx = document.getElementById('viewsChart').getContext('2d');
        const viewsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [
                    <?php
                    foreach ($daily_views as $day) {
                        echo "'" . date('M j', strtotime($day['date'])) . "',";
                    }
                    ?>
                ],
                datasets: [{
                    label: 'Page Views',
                    data: [
                        <?php
                        foreach ($daily_views as $day) {
                            echo $day['views'] . ",";
                        }
                        ?>
                    ],
                    borderColor: 'rgb(32, 57, 97)',
                    backgroundColor: 'rgba(32, 57, 97, 0.1)',
                    tension: 0.1,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle for mobile
        document.getElementById('sidebarToggle').addEventListener('click', function () {
            document.querySelector('.sidebar').classList.toggle('show');
        });
    </script>
</body>
</html>