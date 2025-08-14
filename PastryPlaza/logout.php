<?php
session_start();

if (isset($_SESSION['user_id'])) {
    unset($_SESSION['user_id']);
}
if (isset($_SESSION['cart'])) {
    unset($_SESSION['cart']);
}

session_destroy();
header('location: user/homepage.php');
?>