<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
include '../connector.php';

if (isset($_GET['product_id'])) {
    $product_id = $_GET['product_id'];

    $check_orders_sql = "SELECT COUNT(*) as order_count FROM order_items WHERE product_id = $product_id AND is_shipped = 0";
    $check_result = $conn->query($check_orders_sql);
    $order_count = $check_result->fetch_assoc()['order_count'];

    if ($order_count > 0) {
        echo "<script>alert('Cannot delete product. It is associated with existing orders.');</script>";
        echo "<script>window.location.href = './products_page.php';</script>";
        exit();
    }

    $sql = "DELETE FROM products WHERE product_id = $product_id";
    $result = $conn->query($sql);   

    if ($result) {
        header("Location: ./users_page.php");
        exit();
    } else {
        echo "<script>alert('Failed to delete user.');</script>";
    }
}
?>