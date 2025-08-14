<?php global $conn;
session_start();
include "../connector.php";
include "../functions.php";

$user_data = checkLogin($conn);

if (isset($_GET["workstation_id"])) {
    $workstation_id = $_GET["workstation_id"];

    $checkSql = "SELECT COUNT(*) as count FROM orders WHERE workstation_id = $workstation_id AND order_status < 3";
    $result = $conn->query($checkSql);
    $row = $result->fetch_assoc();

    if ($row["count"] > 0) {
        echo "<script>alert('Cannot delete workstation because there are orders bieng processed.'); window.location.href = 'part-dashboard.php';</script>";
    } else {
        $sql = "DELETE FROM orders WHERE workstation_id = '$workstation_id'";
        $conn->query($sql);

        $sql = "DELETE FROM user WHERE workstation_id = '$workstation_id'";
        $conn->query($sql);

        $sql = "DELETE FROM workstation WHERE workstation_id = '$workstation_id'";
        $conn->query($sql);

        header("location: ./workstation-dashboard.php");
        exit();
    }
}
