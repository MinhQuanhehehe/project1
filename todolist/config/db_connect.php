<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'todo_app_db');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

mysqli_set_charset($conn, "utf8mb4");
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {

    $token_cookie = $_COOKIE['remember_token'];

    $stmt_check = $conn->prepare("SELECT user_id, username, role FROM Users WHERE remember_token = ?");
    $stmt_check->bind_param("s", $token_cookie);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();

    if ($res_check->num_rows > 0) {
        $user = $res_check->fetch_assoc();

        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['UserID'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        setcookie('remember_token', $token_cookie, time() + (86400 * 30), "/");
    }

    $stmt_check->close();
}
?>