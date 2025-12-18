<?php
define('DB_HOST', 'sql100.infinityfree.com');
define('DB_USER', 'if0_40499116');
define('DB_PASS', 'Qs1Wd2Ef3Rg4');
define('DB_NAME', 'if0_40499116_todo_app_db_2');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

mysqli_set_charset($conn, "utf8mb4");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
