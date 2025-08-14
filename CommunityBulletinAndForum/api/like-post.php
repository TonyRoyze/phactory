<?php
require_once '../includes/config.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
        exit;
    }
    
    // Verify CSRF token
    if (!isset($input['csrf_token']) || !verifyCSRFToken($input['csrf_token'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid security token']);
        exit;
    }
    
    $post_id = isset($input['post_id']) ? (int)$input['post_id'] : 0;
    $action = isset($input['action']) ? $input['action'] : '';
    
    // Validate input
    if ($post_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid post ID']);
        exit;
    }
    
    if (!in_array($action, ['like', 'unlike'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        exit;
    }
    
    $db = Database::getInstance();
    $user_id = $_SESSION['user_id'];
    
    // Check if post exists
    $post_exists = $db->selectOne(
        "SELECT id FROM posts WHERE id = ?",
        [$post_id],
        'i'
    );
    
    if (!$post_exists) {
        echo json_encode(['success' => false, 'error' => 'Post not found']);
        exit;
    }
    
    // Check current like status
    $existing_like = $db->selectOne(
        "SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?",
        [$post_id, $user_id],
        'ii'
    );
    
    $db->beginTransaction();
    
    try {
        if ($action === 'like') {
            if ($existing_like) {
                // Already liked
                echo json_encode(['success' => false, 'error' => 'Post already liked']);
                exit;
            }
            
            // Add like
            $db->execute(
                "INSERT INTO post_likes (post_id, user_id, created_at) VALUES (?, ?, NOW())",
                [$post_id, $user_id],
                'ii'
            );
            
            // Update post likes count
            $db->execute(
                "UPDATE posts SET likes_count = likes_count + 1 WHERE id = ?",
                [$post_id],
                'i'
            );
            
        } else { // unlike
            if (!$existing_like) {
                // Not liked
                echo json_encode(['success' => false, 'error' => 'Post not liked']);
                exit;
            }
            
            // Remove like
            $db->execute(
                "DELETE FROM post_likes WHERE post_id = ? AND user_id = ?",
                [$post_id, $user_id],
                'ii'
            );
            
            // Update post likes count
            $db->execute(
                "UPDATE posts SET likes_count = GREATEST(0, likes_count - 1) WHERE id = ?",
                [$post_id],
                'i'
            );
        }
        
        // Get updated like count
        $updated_post = $db->selectOne(
            "SELECT likes_count FROM posts WHERE id = ?",
            [$post_id],
            'i'
        );
        
        $db->commit();
        
        // Invalidate related caches
        invalidateRelatedCaches('post', $post_id);
        
        echo json_encode([
            'success' => true,
            'action' => $action,
            'like_count' => (int)$updated_post['likes_count']
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Like post API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while processing your request'
    ]);
}
?>