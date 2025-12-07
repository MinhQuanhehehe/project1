<?php
session_start(); // Đảm bảo session được start
global $conn;
include 'db_connect.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    // Cột trong DB: user_id, username, password_hash, role
    $stmt = $conn->prepare("SELECT user_id, username, password_hash, role FROM Users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['UserID'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Log
            $log_action = "LOGIN";
            $log_table = "Users";
            $log_detail = "User logged in successfully.";

            $stmt_log = $conn->prepare("INSERT INTO ActivityLogs (user_id, action_type, target_table, target_id, details) VALUES (?, ?, ?, ?, ?)");
            // target_id là user_id
            $stmt_log->bind_param("issis", $user['user_id'], $log_action, $log_table, $user['user_id'], $log_detail);
            $stmt_log->execute();
            $stmt_log->close();

            // Nav
            if ($user['role'] === 'admin') {
                header("Location: home.php");
            } else {
                header("Location: home.php");
            }
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Invalid username or password.";
    }
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Todo App Pro</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container auth-container">
    <form action="login.php" method="POST">
        <h2>Login System</h2>

        <?php if(!empty($error)): ?>
            <p style="color: red; text-align: center; background: #ffe6e6; padding: 10px; border-radius: 5px;">
                <?php echo $error; ?>
            </p>
        <?php endif; ?>

        <div>
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required placeholder="Enter username">
        </div>
        <div>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required placeholder="Enter password">
        </div>

        <button type="submit" class="btn">Login</button>

        <p class="text-center mt-1">
            Don't have an account? <a href="register.php">Register here</a>
        </p>

        <p style="text-align: center; font-size: 0.8em; color: #888; margin-top: 20px;">
            Admin Account: <strong>admin</strong> / <strong>123456</strong>
        </p>
    </form>
</div>
</body>
</html>