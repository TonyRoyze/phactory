<?php global $conn;
include "../connector.php";

if (isset($_GET["reply_id"])) {
    $reply_id = $_GET["reply_id"];
    $post_id = $_GET["post_id"];
    $admin_id = $_GET["admin_id"];

    $sql = "DELETE FROM replies WHERE reply_id = '$reply_id'";
    $conn->query($sql);

    header("location: ./manage-replies.php?post_id=$post_id&admin_id=$admin_id");
    exit();
}
?>