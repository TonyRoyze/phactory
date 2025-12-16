<?php
// This file is views/post.php

require 'connector.php';
$post_id = $_GET['id'] ?? 0;

// Fetch the post
$sql_post = "SELECT p.title, p.content, p.created_at, p.post_type, u.user_name FROM posts p JOIN user u ON p.author_id = u.user_id WHERE p.post_id = ?";
$stmt_post = $conn->prepare($sql_post);
$stmt_post->bind_param("i", $post_id);
$stmt_post->execute();
$result_post = $stmt_post->get_result();

if ($result_post->num_rows == 1) {
    $post = $result_post->fetch_assoc();
    $is_bulletin = ($post['post_type'] === 'BULLETIN');
    
    echo '<a href="app.php?view=posts" class="back-link">&larr; Back to Posts</a>';
    echo '<h1>' . htmlspecialchars($post['title']) . '</h1>';
    echo '<p class="post-meta">By ' . htmlspecialchars($post['user_name']) . ' on ' . date('F j, Y', strtotime($post['created_at'])) . '</p>';
    echo '<div class="post-content">' . nl2br(htmlspecialchars($post['content'])) . '</div>';

    // Only show replies section for non-bulletin posts
    if (!$is_bulletin) {
        // Fetch replies
        $sql_replies = "SELECT r.content, r.created_at, u.user_name FROM replies r JOIN user u ON r.author_id = u.user_id WHERE r.post_id = ? ORDER BY r.created_at ASC";
        $stmt_replies = $conn->prepare($sql_replies);
        $stmt_replies->bind_param("i", $post_id);
        $stmt_replies->execute();
        $result_replies = $stmt_replies->get_result();

        // Replies section
        echo '<hr class="post-divider">';
        echo '<h2>Replies</h2>';

        if ($result_replies->num_rows > 0) {
            echo '<div class="replies-list">';
            while($reply = $result_replies->fetch_assoc()) {
                echo '<div class="reply-card">';
                echo '<p class="reply-meta"><strong>' . htmlspecialchars($reply['user_name']) . '</strong><span class="reply-date"> on ' . date('F j, Y', strtotime($reply['created_at'])) . '</span></p>';
                echo '<p>' . nl2br(htmlspecialchars($reply['content'])) . '</p>';
                echo '</div>';
            }
            echo '</div>';
        } else {
            echo '<p>No replies yet.</p>';
        }
        
        if (isset($_SESSION['user_id'])) {
            echo '<form action="actions/add_reply.php" method="POST" class="reply-form">';
            echo '    <input type="hidden" name="post_id" value="' . htmlspecialchars($post_id) . '">';
            echo '    <textarea name="reply_content" placeholder="Write a reply..." required></textarea>';
            echo '    <button type="submit">Post</button>';
            echo '</form>';
        } else {
            echo '<p><a href="login.php">Log in</a> to leave a reply.</p>';
        }
    } else {
        // For bulletins, show a message that replies are not allowed
        echo '<hr class="post-divider">';
        echo '<div class="bulletin-notice">This is an official bulletin. Replies are not permitted.</div>';
    }

} else {
    echo '<h1>Post not found</h1>';
}
