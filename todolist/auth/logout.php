<?php
include '../config/db_connect.php';

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("UPDATE Users SET remember_token = NULL WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
}

if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, "/");

    unset($_COOKIE['remember_token']);
}

session_unset();
session_destroy();

header("Location: login.php");
exit;
?>