<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
include '../connector.php';

if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];

    $check_orders_sql = "SELECT COUNT(*) as order_count FROM orders WHERE user_id = $user_id AND is_shipped = 0";
    $check_result = $conn->query($check_orders_sql);
    $order_count = $check_result->fetch_assoc()['order_count'];

    if ($order_count > 0) {
        echo "<script>alert('Cannot delete user. There are unshipped orders associated with this account.');</script>";
        echo "<script>window.location.href = './users_page.php';</script>";
        exit();
    }

    $sql = "DELETE FROM users WHERE user_id = $user_id";
    $result = $conn->query($sql);   

    if ($result) {
        header("Location: ./users_page.php");
        exit();
    } else {
        echo "<script>alert('Failed to delete user.');</script>";
    }
}
?>