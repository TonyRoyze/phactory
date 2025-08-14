<?php global $conn;
include "../connector.php";

if (isset($_GET["article_id"])) {
    $article_id = $_GET["article_id"];

    $sql = "DELETE FROM articles WHERE article_id = '$article_id'";
    $conn->query($sql);

    $admin_id = $_GET["admin_id"];
    header("location: ./manage-news.php?admin_id=$admin_id");
    exit();
}
