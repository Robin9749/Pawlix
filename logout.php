<?php
session_start();


if (
    isset($_GET['confirm']) &&
    ($_GET['confirm'] === 'true' || $_GET['confirm'] === 'yes')
) {
    session_unset();
    session_destroy();

    header("Location: login.php");
    exit();
}
?>