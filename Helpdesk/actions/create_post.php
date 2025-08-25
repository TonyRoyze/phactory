<?php
// This file is actions/create_post.php

session_start();
require '../connector.php';

if (isset($_SESSION['user_id']) && in_array($_SESSION['user_type'], ['ADMIN', 'MEMBER'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $category = $_POST['category'];
    if ($category === 'Other') {
        $category = $_POST['new_category'];
    }
    $author_id = $_SESSION['user_id'];
    // For simplicity, we'll hardcode post_type. This could be a form field.
    $post_type = 'FORUM'; 

    $sql = "INSERT INTO posts (title, content, author_id, post_type, category) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssiss", $title, $content, $author_id, $post_type, $category);
    $stmt->execute();

    // Redirect to the main posts view
    header("Location: ../app.php?view=posts");
    exit();
}
