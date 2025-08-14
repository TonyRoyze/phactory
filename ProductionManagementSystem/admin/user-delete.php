<?php global $conn;
session_start();
include "../connector.php";
include "../functions.php";

$user_data = checkLogin($conn);

if (isset($_GET["user_id"])) {
    $user_id = $_GET["user_id"];

    $sql = "DELETE FROM user WHERE user_id = '$user_id'";
    $conn->query($sql);

    header("location: ./user-dashboard.php");
    exit();
}
