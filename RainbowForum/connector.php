<?php

$dbServerName = "localhost";
$dbUserName = "root";
$dbPassword = "deliquescent";
$dbName = "forum_db";

$conn = mysqli_connect($dbServerName, $dbUserName, $dbPassword, $dbName);

if ($conn->connect_error) {
    die("Connection Failed" . $conn->connect_error);
}
