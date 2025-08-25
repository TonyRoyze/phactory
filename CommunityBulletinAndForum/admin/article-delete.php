<?php global $conn;
include "../connector.php";

if (isset($_GET["post_id"])) {
    $post_id = $_GET["post_id"];

    // Check if this is a forum topic and delete associated replies first
    $check_sql = "SELECT post_type FROM posts WHERE post_id = '$post_id'";
    $check_result = $conn->query($check_sql);
    
    if ($check_result && $check_result->num_rows > 0) {
        $row = $check_result->fetch_assoc();
        
        // Delete replies first if it's a forum topic (cascading delete should handle this, but being explicit)
        if ($row["post_type"] == "FORUM") {
            $delete_replies_sql = "DELETE FROM replies WHERE post_id = '$post_id'";
            $conn->query($delete_replies_sql);
        }
    }

    // Delete the post
    $sql = "DELETE FROM posts WHERE post_id = '$post_id'";
    $conn->query($sql);

    $admin_id = $_GET["admin_id"];
    header("location: ./manage-news.php?admin_id=$admin_id");
    exit();
}
