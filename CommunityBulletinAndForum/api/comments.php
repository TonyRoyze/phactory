<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

try {
    $post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 20) : 10;
    
    if ($post_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid post ID']);
        exit;
    }
    
    $db = Database::getInstance();
    
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
    
    // Get comments with pagination
    $comments = $db->select("
        SELECT c.id, c.content, c.created_at, u.username, u.id as user_id
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.post_id = ?
        ORDER BY c.created_at ASC
        LIMIT ? OFFSET ?
    ", [$post_id, $limit, $offset], 'iii');
    
    // Format comments for display
    $formatted_comments = array_map(function($comment) {
        return [
            'id' => $comment['id'],
            'content' => htmlspecialchars($comment['content']),
            'created_at' => $comment['created_at'],
            'username' => htmlspecialchars($comment['username']),
            'user_id' => $comment['user_id'],
            'time_ago' => timeAgo($comment['created_at'])
        ];
    }, $comments);
    
    echo json_encode([
        'success' => true,
        'comments' => $formatted_comments,
        'has_more' => count($comments) === $limit
    ]);
    
} catch (Exception $e) {
    error_log("Comments API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch comments'
    ]);
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    return floor($time/2592000) . ' months ago';
}
?>