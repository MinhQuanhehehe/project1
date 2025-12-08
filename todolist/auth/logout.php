<?php
include '../config/db_connect.php';

session_unset();

session_destroy();

header("Location: login.php");
exit;
?>
