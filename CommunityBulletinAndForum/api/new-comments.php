<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

try {
    $post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
    $since = isset($_GET['since']) ? $_GET['since'] : '';
    
    if ($post_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid post ID']);
        exit;
    }
    
    if (empty($since)) {
        echo json_encode(['success' => true, 'comments' => []]);
        exit;
    }
    
    $db = Database::getInstance();
    
    // Get new comments since the specified time
    $comments = $db->select("
        SELECT c.id, c.content, c.created_at, u.username, u.id as user_id
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.post_id = ? AND c.created_at > ?
        ORDER BY c.created_at ASC
        LIMIT 10
    ", [$post_id, $since], 'is');
    
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
        'comments' => $formatted_comments
    ]);
    
} catch (Exception $e) {
    error_log("New comments API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch new comments'
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