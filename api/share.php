<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON input']);
        exit();
    }
    
    // Validate required fields
    if (!isset($input['post_id']) || !isset($input['platform'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit();
    }
    
    $post_id = intval($input['post_id']);
    $platform = sanitize_input($input['platform']);
    
    // Validate platform
    $allowed_platforms = ['facebook', 'twitter', 'linkedin', 'whatsapp', 'copy'];
    if (!in_array($platform, $allowed_platforms)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid platform']);
        exit();
    }
    
    // Verify post exists
    $stmt = $pdo->prepare("SELECT id FROM posts WHERE id = ? AND status = 'published'");
    $stmt->execute([$post_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Post not found']);
        exit();
    }
    
    // Track the share
    if (increment_post_shares($post_id, $platform)) {
        // Get updated share count
        $stmt = $pdo->prepare("SELECT shares_count FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);
        $post = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'message' => 'Share tracked successfully',
            'shares_count' => intval($post['shares_count'])
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to track share']);
    }
    
} catch (Exception $e) {
    error_log('Share API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>