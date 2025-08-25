<?php
// This file is actions/update_post.php

session_start();
require '../connector.php';

if (isset($_SESSION['user_id'])) {
    $post_id = $_POST['post_id'];
    $title = $_POST['title'];
    $content = $_POST['content'];
    $category = $_POST['category'];
    if ($category === 'Other') {
        $category = $_POST['new_category'];
    }

    // Check if user is author or admin
    $sql_check = "SELECT author_id FROM posts WHERE post_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $post_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if ($result_check->num_rows == 1) {
        $post_data = $result_check->fetch_assoc();
        if ($post_data['author_id'] == $_SESSION['user_id'] || $_SESSION['user_type'] === 'ADMIN') {
            $sql = "UPDATE posts SET title = ?, content = ?, category = ? WHERE post_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $title, $content, $category, $post_id);
            $stmt->execute();
        }
    }
}
header("Location: ../app.php?view=post&id=" . $post_id);
exit();
