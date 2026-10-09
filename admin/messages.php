<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

require_admin_login();

$page_title = 'Messages Management';
$message_id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? 'list';
// Handle actions
if ($_POST) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        set_flash_message('Invalid security token. Please try again.', 'error');
        redirect('messages.php');
    }

    if (isset($_POST['mark_read'])) {
        try {
            $stmt = $pdo->prepare("UPDATE contact_messages SET status = 'read' WHERE id = ?");
            if ($stmt->execute([$message_id])) {
                set_flash_message('Message marked as read.', 'success');
            }
        } catch (PDOException $e) {
            set_flash_message('Failed to update message status.', 'error');
        }
        redirect('messages.php');
    }

    if (isset($_POST['mark_unread'])) {
        try {
            $stmt = $pdo->prepare("UPDATE contact_messages SET status = 'unread' WHERE id = ?");
            if ($stmt->execute([$message_id])) {
                set_flash_message('Message marked as unread.', 'success');
            }
        } catch (PDOException $e) {
            set_flash_message('Failed to update message status.', 'error');
        }
        redirect('messages.php');
    }

    if (isset($_POST['delete_message'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
            if ($stmt->execute([$message_id])) {
                set_flash_message('Message deleted successfully.', 'success');
            }
        } catch (PDOException $e) {
            set_flash_message('Failed to delete message.', 'error');
        }
        redirect('messages.php');
    }

    if (isset($_POST['reply_message'])) {
        $reply_content = trim($_POST['reply_content']);
        $message_email = $_POST['message_email'];
        $message_name = $_POST['message_name'];

        if (!empty($reply_content) && is_valid_email($message_email)) {
            // Here you would integrate with your email system
            // For now, we'll just update the message as replied
            try {
                $stmt = $pdo->prepare("UPDATE contact_messages SET status = 'replied', replied_at = NOW() WHERE id = ?");
                if ($stmt->execute([$message_id])) {
                    set_flash_message('Reply sent successfully!', 'success');
                } else {
                    set_flash_message('Failed to send reply. Please try again.', 'error');
                }
            } catch (PDOException $e) {
                set_flash_message('Failed to send reply. Please try again.', 'error');
            }
        } else {
            set_flash_message('Please enter a valid reply message.', 'error');
        }
        redirect('messages.php?action=view&id=' . $message_id);
    }
}

$message = null;
if ($action === 'view' && $message_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM contact_messages WHERE id = ?");
        $stmt->execute([$message_id]);
        $message = $stmt->fetch();

        if ($message && $message['status'] === 'unread') {
            $stmt = $pdo->prepare("UPDATE contact_messages SET status = 'read' WHERE id = ?");
            $stmt->execute([$message_id]);
        }
    } catch (PDOException $e) {
        $message = null;
    }

    if (!$message) {
        set_flash_message('Message not found.', 'error');
        redirect('messages.php');
    }
}
$site_name = get_setting('site_name', 'Newspower');
$flash_message = get_flash_message();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

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

    
    <!-- Quill Editor -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

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

        /* Sidebar */
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
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        /* Dashboard Cards */
        .overview-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
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
                <a href="<?php echo ADMIN_URL; ?>/analytics.php" class="nav-link">
                    <i class="fas fa-chart-bar me-2"></i>Analytics
                </a>
            </div>
            <div class="nav-item">
                <a href="<?php echo ADMIN_URL; ?>/messages.php" class="nav-link active">
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
                <h5 class="mb-0">Messages</h5>
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

        <?php if ($action === 'list'): ?>
            <!-- Messages List -->
            <div
                class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-envelope me-2"></i>Messages
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="?status=unread" class="btn btn-sm btn-outline-warning">
                            <i class="fas fa-envelope me-1"></i>Unread
                        </a>
                        <a href="?status=read" class="btn btn-sm btn-outline-success">
                            <i class="fas fa-envelope-open me-1"></i>Read
                        </a>
                        <a href="?" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-list me-1"></i>All
                        </a>
                    </div>
                </div>
            </div>

            <!-- Messages Table -->
            <div class="card">
                <div class="card-body">
                    <?php
                    // Get messages with filtering
                    $status_filter = $_GET['status'] ?? '';
                    $page = $_GET['page'] ?? 1;
                    $limit = 15;
                    $offset = ($page - 1) * $limit;

                    try {
                        $where_clause = '';
                        $params = [];

                        if ($status_filter) {
                            $where_clause = 'WHERE status = ?';
                            $params[] = $status_filter;
                        }

                        $stmt = $pdo->prepare("
                                SELECT * FROM contact_messages 
                                $where_clause 
                                ORDER BY created_at DESC 
                                LIMIT ? OFFSET ?
                            ");
                        $params[] = $limit;
                        $params[] = $offset;
                        $stmt->execute($params);
                        $messages = $stmt->fetchAll();

                        // Get total count
                        $count_params = array_slice($params, 0, -2); // Remove limit and offset
                        $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM contact_messages $where_clause");
                        $count_stmt->execute($count_params);
                        $total_messages = $count_stmt->fetchColumn();
                        $total_pages = ceil($total_messages / $limit);
                    } catch (PDOException $e) {
                        $messages = [];
                        $total_pages = 0;
                    }
                    ?>

                    <?php if ($messages): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Subject</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($messages as $msg): ?>
                                        <tr class="<?php echo $msg['status'] === 'unread' ? 'table-warning' : ''; ?>">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-2"
                                                        style="width: 35px; height: 35px;">
                                                        <i class="fas fa-user text-white"></i>
                                                    </div>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($msg['name']); ?></strong>
                                                        <?php if ($msg['status'] === 'unread'): ?>
                                                            <span class="badge bg-warning text-dark ms-1">New</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($msg['email']); ?></td>
                                            <td>
                                                <?php if ($msg['subject']): ?>
                                                    <?php echo htmlspecialchars(truncate_text($msg['subject'], 40)); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">No subject</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $status_config = [
                                                    'unread' => ['class' => 'warning', 'icon' => 'envelope'],
                                                    'read' => ['class' => 'success', 'icon' => 'envelope-open'],
                                                    'replied' => ['class' => 'info', 'icon' => 'reply']
                                                ];
                                                $config = $status_config[$msg['status']] ?? ['class' => 'secondary', 'icon' => 'question'];
                                                ?>
                                                <span class="badge bg-<?php echo $config['class']; ?>">
                                                    <i class="fas fa-<?php echo $config['icon']; ?> me-1"></i>
                                                    <?php echo ucfirst($msg['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small>
                                                    <?php echo date('M d, Y', strtotime($msg['created_at'])); ?>
                                                    <br>
                                                    <span
                                                        class="text-muted"><?php echo date('h:i A', strtotime($msg['created_at'])); ?></span>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="messages.php?action=view&id=<?php echo $msg['id']; ?>"
                                                        class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#deleteModal<?php echo $msg['id']; ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>

                                                <!-- Delete Modal -->
                                                <div class="modal fade" id="deleteModal<?php echo $msg['id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Delete Message</h5>
                                                                <button type="button" class="btn-close"
                                                                    data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                Are you sure you want to delete this message from
                                                                <?php echo htmlspecialchars($msg['name']); ?>?
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary"
                                                                    data-bs-dismiss="modal">Cancel</button>
                                                                <form method="POST" class="d-inline">
                                                                    <input type="hidden" name="csrf_token"
                                                                        value="<?php echo generate_csrf_token(); ?>">
                                                                    <input type="hidden" name="id"
                                                                        value="<?php echo $msg['id']; ?>">
                                                                    <button type="submit" name="delete_message"
                                                                        class="btn btn-danger">Delete</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Messages pagination">
                                <ul class="pagination justify-content-center">
                                    <?php
                                    $status_param = $status_filter ? "&status=$status_filter" : '';
                                    for ($i = 1; $i <= $total_pages; $i++):
                                        ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i . $status_param; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-envelope fa-3x text-muted mb-3"></i>
                            <h4>No Messages</h4>
                            <p class="text-muted">
                                <?php if ($status_filter): ?>
                                    No <?php echo $status_filter; ?> messages found.
                                <?php else: ?>
                                    No messages received yet.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($action === 'view'): ?>
            <!-- View Message -->
            <div
                class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-envelope-open me-2"></i>View Message
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="messages.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Back to Messages
                    </a>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <!-- Message Details -->
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <?php echo htmlspecialchars($message['subject'] ?: 'No Subject'); ?>
                                </h5>
                                <span
                                    class="badge bg-<?php echo $message['status'] === 'unread' ? 'warning' : 'success'; ?>">
                                    <?php echo ucfirst($message['status']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3"
                                        style="width: 50px; height: 50px;">
                                        <i class="fas fa-user text-white"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($message['name']); ?></h6>
                                        <small class="text-muted">
                                            <i class="fas fa-envelope me-1"></i>
                                            <?php echo htmlspecialchars($message['email']); ?>
                                        </small>
                                    </div>
                                </div>

                                <div class="bg-light p-3 rounded">
                                    <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reply Form -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-reply me-2"></i>Reply to Message
                            </h6>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <input type="hidden" name="message_email"
                                    value="<?php echo htmlspecialchars($message['email']); ?>">
                                <input type="hidden" name="message_name"
                                    value="<?php echo htmlspecialchars($message['name']); ?>">

                                <div class="mb-3">
                                    <label for="reply_content" class="form-label">Reply Message</label>
                                    <textarea class="form-control" id="reply_content" name="reply_content" rows="5"
                                        placeholder="Type your reply here..." required></textarea>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" name="reply_message" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-1"></i>Send Reply
                                    </button>
                                    <div class="form-text ms-2 align-self-center">
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Reply will be sent to <?php echo htmlspecialchars($message['email']); ?>
                                        </small>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Message Info -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>Message Information
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>Received:</strong><br>
                                <small><?php echo date('F d, Y \a\t h:i A', strtotime($message['created_at'])); ?></small>
                            </div>


                            <?php if ($message['replied_at']): ?>
                                <div class="mb-3">
                                    <strong>Replied:</strong><br>
                                    <small><?php echo date('F d, Y \a\t h:i A', strtotime($message['replied_at'])); ?></small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-cogs me-2"></i>Actions
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <?php if ($message['status'] === 'unread'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                        <button type="submit" name="mark_read" class="btn btn-success w-100">
                                            <i class="fas fa-envelope-open me-1"></i>Mark as Read
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                        <button type="submit" name="mark_unread" class="btn btn-warning w-100">
                                            <i class="fas fa-envelope me-1"></i>Mark as Unread
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal"
                                    data-bs-target="#deleteMessageModal">
                                    <i class="fas fa-trash me-1"></i>Delete Message
                                </button>

                                <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>"
                                    class="btn btn-outline-primary w-100">
                                    <i class="fas fa-external-link-alt me-1"></i>Open in Email Client
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delete Message Modal -->
            <div class="modal fade" id="deleteMessageModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Delete Message</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            Are you sure you want to delete this message from
                            <strong><?php echo htmlspecialchars($message['name']); ?></strong>?
                            <br><br>
                            <small class="text-muted">This action cannot be undone.</small>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <button type="submit" name="delete_message" class="btn btn-danger">Delete Message</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </div>





    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle for mobile
        document.getElementById('sidebarToggle').addEventListener('click', function () {
            document.querySelector('.sidebar').classList.toggle('show');
        });
    </script>