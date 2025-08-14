<?php global $conn;
include "../connector.php";

if (isset($_GET["user_id"])) {
    $user_id = $_GET["user_id"];

    $sql = "DELETE FROM user WHERE user_id = '$user_id'";
    $conn->query($sql);

    $admin_id = $_GET["admin_id"];
    header("location: ./manage-users.php?admin_id=$admin_id");
    exit();
}
