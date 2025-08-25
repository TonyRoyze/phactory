<?php
// This file is actions/add_reply.php

session_start();
require '../connector.php';

if (isset($_SESSION['user_id'])) {
    $content = $_POST['reply_content'];
    $post_id = $_POST['post_id'];
    $author_id = $_SESSION['user_id'];

    $sql = "INSERT INTO replies (post_id, content, author_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isi", $post_id, $content, $author_id);
    $stmt->execute();

    // Redirect back to the post view
    header("Location: ../app.php?view=post&id=" . $post_id);
    exit();
}
