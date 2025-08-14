<?php global $conn;
session_start();
include "../connector.php";
include "../functions.php";

$user_data = checkLogin($conn);

if (isset($_GET["part_id"])) {
    $part_id = $_GET["part_id"];
    $checkSql = "SELECT COUNT(*) as count FROM workstation WHERE part_id = $part_id";
    $result = $conn->query($checkSql);
    $row = $result->fetch_assoc();

    if ($row["count"] > 0) {
        echo "<script>alert('Cannot delete part because it is assigned to a workstations.'); window.location.href = 'part-dashboard.php';</script>";
    } else {
        $deleteSql = "DELETE FROM part WHERE part_id = $part_id";
        if ($conn->query($deleteSql) === true) {
            echo "Part deleted successfully.";
        } else {
            echo "Error deleting part: " . $conn->error;
        }
    }
}
