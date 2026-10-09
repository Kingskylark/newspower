<?php
// Include database configuration
require_once dirname(__DIR__) . '/config/database.php';

// Admin authentication functions
function is_admin_logged_in() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_username']);
}

function require_admin_login() {
    if (!is_admin_logged_in()) {
        header('Location: ' . ADMIN_URL . '/login.php');
        exit;
    }
}

function admin_login($username, $password) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ? AND status = 'active'");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
      
        
        
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['admin_role'] = $admin['role'];
            $_SESSION['login_time'] = time();
            
            // Update last login
            $stmt = $pdo->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$admin['id']]);
            
            return true;
        }
        return false;
    } catch(PDOException $e) {
        return false;
    }
}

function admin_logout() {
    session_unset();
    session_destroy();
    header('Location: ' . ADMIN_URL . '/login.php');
    exit;
}

function check_session_timeout() {
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > SESSION_TIMEOUT) {
        admin_logout();
    }
    $_SESSION['login_time'] = time(); // Reset timeout
}

// Content management functions
function create_post($title, $content, $excerpt, $category, $tags, $author_id, $status = 'draft', $is_featured = false, $featured_image = null) {
    global $pdo;
    try {
        $slug = generate_unique_slug($title, 'posts');
        $published_at = ($status == 'published') ? date('Y-m-d H:i:s') : null;
        
        $stmt = $pdo->prepare("
            INSERT INTO posts (title, slug, content, excerpt, category, tags, author_id, status, is_featured, featured_image, published_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $title, $slug, $content, $excerpt, $category, $tags, 
            $author_id, $status, $is_featured ? 1 : 0, $featured_image, $published_at
        ]);
    } catch(PDOException $e) {
        return false;
    }
}

// Updated update_post function to handle author changes
function update_post($id, $title, $content, $excerpt, $category, $tags, $status = 'draft', $is_featured = false, $featured_image = null, $author_id = null) {
    global $pdo;
    try {
        $published_at = null;
        if ($status == 'published') {
            // Check if it was already published
            $stmt = $pdo->prepare("SELECT published_at FROM posts WHERE id = ?");
            $stmt->execute([$id]);
            $current = $stmt->fetch();
            $published_at = $current['published_at'] ?? date('Y-m-d H:i:s');
        }
        
        $sql = "UPDATE posts SET title = ?, content = ?, excerpt = ?, category = ?, tags = ?, status = ?, is_featured = ?, updated_at = NOW()";
        $params = [$title, $content, $excerpt, $category, $tags, $status, $is_featured ? 1 : 0];
        
        // Add author_id if provided
        if ($author_id !== null) {
            $sql .= ", author_id = ?";
            $params[] = $author_id;
        }
        
        if ($featured_image !== null) {
            $sql .= ", featured_image = ?";
            $params[] = $featured_image;
        }
        
        if ($published_at !== null) {
            $sql .= ", published_at = ?";
            $params[] = $published_at;
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $id;
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    } catch(PDOException $e) {
        return false;
    }
}

// Helper function to get author information by author name
function get_post_author($author_name) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id, username, full_name, email FROM admin_users WHERE full_name = ?");
        $stmt->execute([$author_name]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        return null;
    }
}

// Function to get posts by author name
function get_posts_by_author($author_name, $limit = 10, $offset = 0, $status = null) {
    global $pdo;
    try {
        $sql = "SELECT p.* 
                FROM posts p 
                WHERE p.author_name = ?";
        
        $params = [$author_name];
        
        if ($status) {
            $sql .= " AND p.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
    }
}

// Function to check if user can edit post (for role-based permissions)
function can_edit_post($post_id, $user_id, $user_role) {
    global $pdo;
    try {
        // Super admins can edit any post
        if ($user_role === 'super_admin') {
            return true;
        }
        
        // Get post author
        $stmt = $pdo->prepare("SELECT author_id FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);
        $post = $stmt->fetch();
        
        if (!$post) {
            return false;
        }
        
        // Admins can edit any post, authors can only edit their own
        if ($user_role === 'admin') {
            return true;
        } elseif ($user_role === 'author') {
            return $post['author_id'] == $user_id;
        }
        
        return false;
    } catch(PDOException $e) {
        return false;
    }
}

// Function to validate author assignment based on user role
function can_assign_author($current_user_role, $target_author_id, $current_user_id) {
    global $pdo;
    
    // Super admins can assign any author
    if ($current_user_role === 'super_admin') {
        return true;
    }
    
    // Admins can assign any author
    if ($current_user_role === 'admin') {
        return true;
    }
    
    // Authors can only assign themselves
    if ($current_user_role === 'author') {
        return $target_author_id == $current_user_id;
    }
    
    return false;
}

function delete_post($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        return $stmt->execute([$id]);
    } catch(PDOException $e) {
        return false;
    }
}

// Analytics functions
function get_total_posts() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE status = 'published'");
        $stmt->execute();
        return $stmt->fetchColumn();
    } catch(PDOException $e) {
        return 0;
    }
}

function get_total_views() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT SUM(views_count) FROM posts");
        $stmt->execute();
        return $stmt->fetchColumn() ?: 0;
    } catch(PDOException $e) {
        return 0;
    }
}

function get_total_messages() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM contact_messages");
        $stmt->execute();
        return $stmt->fetchColumn();
    } catch(PDOException $e) {
        return 0;
    }
}

function get_recent_views($days = 30) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT DATE(viewed_at) as date, COUNT(*) as views 
            FROM page_views 
            WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL ? DAY) 
            GROUP BY DATE(viewed_at) 
            ORDER BY date DESC
        ");
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
    }
}

function get_popular_posts($limit = 10) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT title, slug, views_count, shares_count 
            FROM posts 
            WHERE status = 'published' 
            ORDER BY views_count DESC, shares_count DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
    }
}

// Image upload function
function upload_image($file, $directory = 'uploads/') {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        return false;
    }
    
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $max_size) {
        return false;
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $filepath = $directory . $filename;
    
    if (!is_dir($directory)) {
        mkdir($directory, 0777, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    }
    
    return false;
}

// Security functions
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function clean_html($html) {
    // Allow basic HTML tags but strip dangerous ones
    $allowed_tags = '<p><br><strong><b><em><i><u><h1><h2><h3><h4><h5><h6><ul><ol><li><a><img><blockquote>';
    return strip_tags($html, $allowed_tags);
}

// Utility functions
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function set_flash_message($message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'success';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function get_client_ip() {
    $ipkeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    foreach ($ipkeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

// Category management functions
function create_category($name, $description = '', $color = '#007bff') {
    global $pdo;
    try {
        $slug = generate_unique_slug($name, 'categories');
        $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, color) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$name, $slug, $description, $color]);
    } catch(PDOException $e) {
        return false;
    }
}

function update_category($id, $name, $description = '', $color = '#007bff') {
    global $pdo;
    try {
        // Generate new slug if name changed
        $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $current = $stmt->fetch();
        
        $slug = ($current && $current['name'] !== $name) ? 
                generate_unique_slug($name, 'categories', $id) : null;
        
        if ($slug) {
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, color = ?, updated_at = NOW() WHERE id = ?");
            return $stmt->execute([$name, $slug, $description, $color, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ?, color = ?, updated_at = NOW() WHERE id = ?");
            return $stmt->execute([$name, $description, $color, $id]);
        }
    } catch(PDOException $e) {
        return false;
    }
}

function delete_category($id) {
    global $pdo;
    try {
        // Check if category is being used by posts
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE category = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            return false; // Category is in use
        }
        
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        return $stmt->execute([$id]);
    } catch(PDOException $e) {
        return false;
    }
}

function get_category_posts_count($category_slug) {
    global $pdo;
    try {
        // First get category ID by slug
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
        $stmt->execute([$category_slug]);
        $category = $stmt->fetch();
        
        if (!$category) return 0;
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE category = ? AND status = 'published'");
        $stmt->execute([$category['id']]);
        return $stmt->fetchColumn();
    } catch(PDOException $e) {
        return 0;
    }
}

// User management functions
function create_user($username, $email, $password, $full_name, $role = 'author', $status = 'active') {
    global $pdo;
    try {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            return false; // Username or email already exists
        }
        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO admin_users (username, email, password, full_name, role, status) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$username, $email, $hashed_password, $full_name, $role, $status]);
    } catch(PDOException $e) {
        return false;
    }
}

function update_user($id, $username, $email, $password, $full_name, $role, $status) {
    global $pdo;
    try {
        // Check if username or email already exists for other users
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $id]);
        if ($stmt->fetchColumn() > 0) {
            return false; // Username or email already exists
        }
        
        if (!empty($password)) {
            // Update with password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                UPDATE admin_users 
                SET username = ?, email = ?, password = ?, full_name = ?, role = ?, status = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            return $stmt->execute([$username, $email, $hashed_password, $full_name, $role, $status, $id]);
        } else {
            // Update without password
            $stmt = $pdo->prepare("
                UPDATE admin_users 
                SET username = ?, email = ?, full_name = ?, role = ?, status = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            return $stmt->execute([$username, $email, $full_name, $role, $status, $id]);
        }
    } catch(PDOException $e) {
        return false;
    }
}

function delete_user($id) {
    global $pdo;
    try {
        // Don't allow deletion of the last super admin
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_users WHERE role = 'super_admin' AND status = 'active'");
        $stmt->execute();
        $super_admin_count = $stmt->fetchColumn();
        
        if ($super_admin_count <= 1) {
            $stmt = $pdo->prepare("SELECT role FROM admin_users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch();
            if ($user && $user['role'] === 'super_admin') {
                return false; // Cannot delete the last super admin
            }
        }
        
        $stmt = $pdo->prepare("DELETE FROM admin_users WHERE id = ?");
        return $stmt->execute([$id]);
    } catch(PDOException $e) {
        return false;
    }
}

// Utility functions
function time_ago($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    return floor($time/31536000) . ' years ago';
}

// Update the existing generate_unique_slug function to handle exclusions
function generate_unique_slug($title, $table, $exclude_id = null) {
    global $pdo;
    $base_slug = generate_slug($title);
    $slug = $base_slug;
    $counter = 1;
    
    while (true) {
        $sql = "SELECT COUNT(*) FROM $table WHERE slug = ?";
        $params = [$slug];
        
        if ($exclude_id) {
            $sql .= " AND id != ?";
            $params[] = $exclude_id;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        if ($stmt->fetchColumn() == 0) {
            return $slug;
        }
        $slug = $base_slug . '-' . $counter;
        $counter++;
    }
}

// Message management functions (if needed)
function get_contact_messages($limit = 10, $offset = 0, $status = null) {
    global $pdo;
    try {
        $sql = "SELECT * FROM contact_messages";
        $params = [];
        
        if ($status) {
            $sql .= " WHERE status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
    }
}

function update_message_status($id, $status) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE contact_messages SET status = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$status, $id]);
    } catch(PDOException $e) {
        return false;
    }
}

function delete_message($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
        return $stmt->execute([$id]);
    } catch(PDOException $e) {
        return false;
    }
}

// Additional utility functions that might be needed
function get_post_count_by_status($status = null) {
    global $pdo;
    try {
        if ($status) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE status = ?");
            $stmt->execute([$status]);
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts");
            $stmt->execute();
        }
        return $stmt->fetchColumn();
    } catch(PDOException $e) {
        return 0;
    }
}

function get_user_posts_count($user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE author_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    } catch(PDOException $e) {
        return 0;
    }
}

// Enhanced categories function to include post count
function get_categories_with_post_count() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, 
                   COUNT(p.id) as post_count
            FROM categories c 
            LEFT JOIN posts p ON c.id = p.category AND p.status = 'published'
            GROUP BY c.id 
            ORDER BY c.name
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
    }
}

?>