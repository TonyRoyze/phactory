<?php
echo '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pastry Plaza</title>
<link rel="stylesheet" href="../default.css">
</head>
<body>
<header>
    <nav>
        <div class="logo">
            <h1>PASTRY PLAZA</h1>
        </div>
        <ul>
            <li><a href="./homepage.php">Home</a></li>
            <li><a href="./menu.php">Our Menu</a></li>
            <li><a href="./aboutus.php">About Us</a></li>
            <li><a href="./cart.php">Cart</a></li>';
            if (isset($_SESSION['user_id'])) {
                echo '<li><a href="./orders_page.php">Orders</a></li>';
                echo '<li><a href="../logout.php">Logout</a></li>';
            } else {
                echo '<li><a href="./login.php">Login</a></li>';
            }
        echo'</ul>
    </nav>
</header>';
?>