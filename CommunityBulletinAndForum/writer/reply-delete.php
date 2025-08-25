<?php
global $conn;
include "../connector.php";

$reply_id = $_GET["reply_id"];
$writer_id = $_GET["writer_id"];

// Authorization check - ensure user can only delete their own replies
$auth_sql = "SELECT author_id FROM replies WHERE reply_id = $reply_id";
$auth_result = $conn->query($auth_sql);
$auth_row = $auth_result->fetch_assoc();

if ($auth_row['author_id'] != $writer_id) {
    die("Unauthorized: You can only delete your own replies.");
}

// Delete the reply
$sql = "DELETE FROM replies WHERE reply_id = $reply_id";

if ($conn->query($sql) === TRUE) {
    // Redirect back to profile with success message
    header("Location: writer.php?writer_id=$writer_id&message=Reply deleted successfully");
} else {
    // Redirect back with error message
    header("Location: writer.php?writer_id=$writer_id&error=Error deleting reply");
}
exit();
?>