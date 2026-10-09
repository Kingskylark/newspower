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
            $username = sanitize_input($_POST['username'] ?? '');
            $email = sanitize_input($_POST['email'] ?? '');
            $full_name = sanitize_input($_POST['full_name'] ?? '');
            $role = in_array($_POST['role'] ?? '', ['super_admin', 'admin', 'author']) ? $_POST['role'] : 'author';
            $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';
            
            if (empty($username) || empty($email) || empty($full_name)) {
                set_flash_message('Username, email, and full name are required.', 'error');
            } elseif (!is_valid_email($email)) {
                set_flash_message('Please enter a valid email address.', 'error');
            } else {
                if ($action === 'create') {
                    $password = $_POST['password'] ?? '';
                    if (strlen($password) < PASSWORD_MIN_LENGTH) {
                        set_flash_message('Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.', 'error');
                    } else {
                        if (create_user($username, $email, $password, $full_name, $role, $status)) {
                            set_flash_message('User created successfully!', 'success');
                        } else {
                            set_flash_message('Failed to create user. Username or email might already exist.', 'error');
                        }
                    }
                } else {
                    $user_id = (int)($_POST['user_id'] ?? 0);
                    $password = $_POST['password'] ?? '';
                    
                    if (update_user($user_id, $username, $email, $password, $full_name, $role, $status)) {
                        set_flash_message('User updated successfully!', 'success');
                    } else {
                        set_flash_message('Failed to update user.', 'error');
                    }
                }
            }
        } elseif ($action === 'delete') {
            $user_id = (int)($_POST['user_id'] ?? 0);
            if ($user_id === $_SESSION['admin_id']) {
                set_flash_message('You cannot delete your own account.', 'error');
            } elseif (delete_user($user_id)) {
                set_flash_message('User deleted successfully!', 'success');
            } else {
                set_flash_message('Failed to delete user.', 'error');
            }
        }
    }
    header('Location: ' . ADMIN_URL . '/users.php');
    exit;
}

// Get users
$page = (int)($_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    $stmt = $pdo->prepare("
        SELECT * FROM admin_users 
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$limit, $offset]);
    $users = $stmt->fetchAll();
    
    // Get total count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_users");
    $stmt->execute();
    $total_users = $stmt->fetchColumn();
    $total_pages = ceil($total_users / $limit);
} catch(PDOException $e) {
    $users = [];
    $total_users = 0;
    $total_pages = 0;
}

// Handle edit mode
$editing_user = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE id = ?");
        $stmt->execute([$_GET['edit']]);
        $editing_user = $stmt->fetch();
    } catch(PDOException $e) {
        $editing_user = null;
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
    <title>User Management - <?php echo htmlspecialchars($site_name); ?></title>
    
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
        
        .role-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .role-super_admin {
            background-color: #dc3545;
            color: white;
        }
        
        .role-admin {
            background-color: #007bff;
            color: white;
        }
        
        .role-author {
            background-color: #28a745;
            color: white;
        }
        
        .status-active {
            background-color: #d1e7dd;
            color: #0a3622;
        }
        
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
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
                <a href="<?php echo ADMIN_URL; ?>/users.php" class="nav-link active">
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
                <h5 class="mb-0">User Management</h5>
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

            <!-- User Form -->
            <div class="content-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        <?php echo $editing_user ? 'Edit User' : 'Create New User'; ?>
                    </h5>
                    <?php if ($editing_user): ?>
                        <a href="<?php echo ADMIN_URL; ?>/users.php" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i>Cancel Edit
                        </a>
                    <?php endif; ?>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="action" value="<?php echo $editing_user ? 'update' : 'create'; ?>">
                    <?php if ($editing_user): ?>
                        <input type="hidden" name="user_id" value="<?php echo $editing_user['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo $editing_user ? htmlspecialchars($editing_user['username']) : ''; ?>" 
                                       required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo $editing_user ? htmlspecialchars($editing_user['email']) : ''; ?>" 
                                       required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       value="<?php echo $editing_user ? htmlspecialchars($editing_user['full_name']) : ''; ?>" 
                                       required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    Password <?php echo $editing_user ? '(leave empty to keep current)' : ''; ?>
                                </label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       <?php echo !$editing_user ? 'required' : ''; ?>>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="author" <?php echo ($editing_user && $editing_user['role'] === 'author') ? 'selected' : ''; ?>>Author</option>
                                    <option value="admin" <?php echo ($editing_user && $editing_user['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                    <option value="super_admin" <?php echo ($editing_user && $editing_user['role'] === 'super_admin') ? 'selected' : ''; ?>>Super Admin</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="active" <?php echo ($editing_user && $editing_user['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo ($editing_user && $editing_user['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>
                        <?php echo $editing_user ? 'Update User' : 'Create User'; ?>
                    </button>
                </form>
            </div>

            <!-- Users List -->
            <div class="content-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">All Users (<?php echo number_format($total_users); ?>)</h5>
                </div>
                
                <?php if (empty($users)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No users found.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Last Login</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar me-3" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($user['full_name']); ?></h6>
                                                    <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span class="role-badge role-<?php echo $user['role']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="role-badge status-<?php echo $user['status']; ?>">
                                                <?php echo ucfirst($user['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($user['last_login']): ?>
                                                <?php echo format_date($user['last_login'], 'M d, Y H:i'); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Never</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo format_date($user['created_at']); ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="<?php echo ADMIN_URL; ?>/users.php?edit=<?php echo $user['id']; ?>" 
                                                   class="btn btn-sm btn-outline-secondary" title="Edit User">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if ($user['id'] !== $_SESSION['admin_id']): ?>
                                                    <form method="POST" class="d-inline" 
                                                          onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete User">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Users pagination">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle for mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
        });
    </script>
</body>
</html>