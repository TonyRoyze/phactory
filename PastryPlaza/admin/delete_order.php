<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
include '../connector.php';

if (isset($_GET['order_id'])) {
    $order_id = $_GET['order_id'];

    $sql = "DELETE FROM order_items WHERE order_id = $order_id";
    $result = $conn->query($sql); 

    $sql = "DELETE FROM orders WHERE order_id = $order_id";
    $result = $conn->query($sql);   

    if ($result) {
        header("Location: ./orders_page.php");
        exit();
    } else {
        echo "<script>alert('Failed to delete order.');</script>";
    }
}
?>