<?php
global $conn;
include "../connector.php";

$post_id = $_GET["post_id"];
$writer_id = $_GET["writer_id"];

// Authorization check - ensure user can only delete their own posts
$auth_sql = "SELECT author_id, post_type FROM posts WHERE post_id = $post_id";
$auth_result = $conn->query($auth_sql);
$auth_row = $auth_result->fetch_assoc();

if ($auth_row['author_id'] != $writer_id) {
    die("Unauthorized: You can only delete your own posts.");
}

// Delete the post (replies will be automatically deleted due to CASCADE)
$sql = "DELETE FROM posts WHERE post_id = $post_id";

if ($conn->query($sql) === TRUE) {
    // Redirect back to profile with success message
    header("Location: writer.php?writer_id=$writer_id&message=Post deleted successfully");
} else {
    // Redirect back with error message
    header("Location: writer.php?writer_id=$writer_id&error=Error deleting post");
}
exit();
?>