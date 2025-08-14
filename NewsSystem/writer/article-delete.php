<?php global $conn;
include "../connector.php";

if (isset($_GET["article_id"])) {
    $article_id = $_GET["article_id"];

    $sql = "DELETE FROM articles WHERE article_id = '$article_id'";
    $conn->query($sql);

    $writer_id = $_GET["writer_id"];
    header("location: ./writer.php?writer_id=$writer_id");
    exit();
}
