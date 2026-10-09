<?php
// Database configuration
define('DB_HOST', 'sql113.infinityfree.com');
define('DB_NAME', 'if0_39866059_newspower_db');
define('DB_USER', 'if0_39866059');  // Change this to your MySQL username
define('DB_PASS', 'uXd0dUWwsN');      // Change this to your MySQL password

// Site configuration
define('SITE_URL', 'https://newspower.free.nf/newspower'); // Change this to your local URL
define('ADMIN_URL', SITE_URL . '/admin');

// Security
define('SESSION_TIMEOUT', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 6);

// Database connection class
class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $conn = null;

    public function connect() {
        if ($this->conn == null) {
            try {
                $this->conn = new PDO(
                    "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                    $this->username,
                    $this->password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
            } catch(PDOException $e) {
                echo "Connection Error: " . $e->getMessage();
                die();
            }
        }
        return $this->conn;
    }
}

// Global database connection
$database = new Database();
$pdo = $database->connect();

// Helper functions
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generate_slug($text) {
    $text = preg_replace('/[^A-Za-z0-9-]+/', '-', $text);
    $text = strtolower($text);
    $text = trim($text, '-');
    return $text;
}

function get_setting($name, $default = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_name = ?");
        $stmt->execute([$name]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $default;
    } catch(PDOException $e) {
        return $default;
    }
}

function update_setting($name, $value) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO site_settings (setting_name, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        return $stmt->execute([$name, $value, $value]);
    } catch(PDOException $e) {
        return false;
    }
}

function get_page_by_slug($slug) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = ? AND status = 'published'");
        $stmt->execute([$slug]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        return false;
    }
}

function get_post_by_slug($slug) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT p.* 
            FROM posts p 
            WHERE p.slug = ? AND p.status = 'published'
        ");
        $stmt->execute([$slug]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        return false;
    }
}

function get_posts($limit = 10, $offset = 0, $category = null, $featured_only = false) {
    global $pdo;
    try {
        $sql = "SELECT p.* 
                FROM posts p 
                WHERE p.status = 'published'";
        
        $params = [];
        
        if ($category) {
            $sql .= " AND p.category = ?";
            $params[] = $category;
        }
        
        if ($featured_only) {
            $sql .= " AND p.is_featured = 1";
        }
        
        $sql .= " ORDER BY p.published_at DESC, p.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
    }
}

function get_categories() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM categories ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
    }
}

function increment_post_views($post_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE posts SET views_count = views_count + 1 WHERE id = ?");
        $stmt->execute([$post_id]);
        
        // Log the view
        $stmt = $pdo->prepare("INSERT INTO page_views (page_type, page_id, page_url, ip_address, user_agent, referer) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            'post',
            $post_id,
            $_SERVER['REQUEST_URI'] ?? '',
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['HTTP_REFERER'] ?? ''
        ]);
        
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

function increment_post_shares($post_id, $platform) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE posts SET shares_count = shares_count + 1 WHERE id = ?");
        $stmt->execute([$post_id]);
        
        // Log the share
        $stmt = $pdo->prepare("INSERT INTO social_shares (post_id, platform) VALUES (?, ?)");
        $stmt->execute([$post_id, $platform]);
        
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

function save_contact_message($name, $email, $subject, $message) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$name, $email, $subject, $message]);
    } catch(PDOException $e) {
        return false;
    }
}

function format_date($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

function truncate_text($text, $length = 150) {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

function get_post_url($slug) {
    return SITE_URL . '/post.php?slug=' . urlencode($slug);
}

function get_page_url($slug) {
    return SITE_URL . '/' . $slug . '.php';
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>