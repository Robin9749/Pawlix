<?php

$servername = "127.0.0.1";
$username = "root";
$password = "";
$database = "pawlix_db";

$conn = mysqli_connect($servername, $username, $password, $database);

if (!$conn) {
    die("Connection Failed: " . mysqli_connect_error());
}
?>