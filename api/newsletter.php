<?php
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

try {
    // Get email from POST data
    $email = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';
    
    if (empty($email)) {
        http_response_code(400);
        echo json_encode(['error' => 'Email is required']);
        exit();
    }
    
    if (!is_valid_email($email)) {
        http_response_code(400);
        echo json_encode(['error' => 'Please enter a valid email address']);
        exit();
    }
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM newsletter_subscribers WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'You are already subscribed to our newsletter!'
        ]);
        exit();
    }
    
    // Create newsletter_subscribers table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS newsletter_subscribers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) UNIQUE NOT NULL,
            status ENUM('active', 'unsubscribed') DEFAULT 'active',
            subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(45),
            user_agent TEXT
        )
    ");
    
    // Insert subscriber
    $stmt = $pdo->prepare("
        INSERT INTO newsletter_subscribers (email, ip_address, user_agent) 
        VALUES (?, ?, ?)
    ");
    
    $ip_address = get_client_ip();
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    if ($stmt->execute([$email, $ip_address, $user_agent])) {
        echo json_encode([
            'success' => true,
            'message' => 'Successfully subscribed to our newsletter!'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to subscribe. Please try again.']);
    }
    
} catch (Exception $e) {
    error_log('Newsletter API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred. Please try again later.']);
}
?>