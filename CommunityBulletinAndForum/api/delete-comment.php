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
    
    $comment_id = isset($input['comment_id']) ? (int)$input['comment_id'] : 0;
    
    // Validate input
    if ($comment_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid comment ID']);
        exit;
    }
    
    $db = Database::getInstance();
    $user_id = $_SESSION['user_id'];
    
    // Get comment details and verify ownership
    $comment = $db->selectOne(
        "SELECT id, post_id, user_id FROM comments WHERE id = ?",
        [$comment_id],
        'i'
    );
    
    if (!$comment) {
        echo json_encode(['success' => false, 'error' => 'Comment not found']);
        exit;
    }
    
    // Check if user owns the comment
    if ($comment['user_id'] != $user_id) {
        echo json_encode(['success' => false, 'error' => 'Permission denied']);
        exit;
    }
    
    $db->beginTransaction();
    
    try {
        // Delete the comment
        $result = $db->execute(
            "DELETE FROM comments WHERE id = ?",
            [$comment_id],
            'i'
        );
        
        if ($result && $result['affected_rows'] > 0) {
            // Update post comment count
            $db->execute(
                "UPDATE posts SET comments_count = GREATEST(0, comments_count - 1) WHERE id = ?",
                [$comment['post_id']],
                'i'
            );
            
            $db->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Comment deleted successfully'
            ]);
        } else {
            throw new Exception('Failed to delete comment');
        }
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Delete comment API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while deleting the comment'
    ]);
}
?>