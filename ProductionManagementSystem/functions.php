<?php

function checkLogin($conn)
{
    if (isset($_SESSION["Username"])) {
        $username = $_SESSION["Username"];

        $sql = "SELECT * FROM user WHERE user_name='$username' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && mysqli_num_rows($result) > 0) {
            return $result->fetch_assoc();
        }
    }

    header("location: /login.php");
    exit();
}
