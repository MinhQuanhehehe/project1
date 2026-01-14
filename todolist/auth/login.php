<?php
session_start();
global $conn;
include '../config/db_connect.php';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

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

            if (isset($_POST['remember'])) {
                $token = bin2hex(random_bytes(32));

                $stmt_token = $conn->prepare("UPDATE Users SET remember_token = ? WHERE user_id = ?");
                $stmt_token->bind_param("si", $token, $user['user_id']);
                $stmt_token->execute();
                $stmt_token->close();

                setcookie('remember_token', $token, time() + (86400 * 30), "/");
            }

            // 3. Ghi Log đăng nhập
            $log_action = "LOGIN";
            $log_table = "Users";
            $log_detail = "User logged in successfully.";

            $stmt_log = $conn->prepare("INSERT INTO ActivityLogs (user_id, action_type, target_table, target_id, details) VALUES (?, ?, ?, ?, ?)");
            $stmt_log->bind_param("issis", $user['user_id'], $log_action, $log_table, $user['user_id'], $log_detail);
            $stmt_log->execute();
            $stmt_log->close();

            header("Location: ../home.php");
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
    <link rel="stylesheet" href="../assets/css/style.css?v=1.0">

    <style>
        body {
            background-image: url('../assets/css/background.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .auth-container {
            background: rgba(255, 255, 255, 0.9); /* Tăng độ đậm lên xíu cho dễ đọc */
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
            backdrop-filter: blur(5px);
        }

        .auth-container h2 {
            margin-bottom: 25px;
            color: #333;
            text-align: center;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background-color: #007bff;
            border: none;
            color: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: 0.3s;
        }

        .btn:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }

        /* Style chung cho input text/password */
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0 20px 0;
            border: 1px solid #ddd;
            border-radius: 6px;
            box-sizing: border-box;
        }

        /* Style riêng cho checkbox để nó thẳng hàng */
        .remember-group {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            font-size: 0.95em;
            color: #555;
            cursor: pointer;
        }
        .remember-group input[type="checkbox"] {
            width: auto; /* Reset chiều rộng */
            margin: 0;   /* Reset margin */
            cursor: pointer;
            width: 16px;
            height: 16px;
        }
    </style>
</head>
<body>

<div class="container auth-container">
    <form action="login.php" method="POST">
        <h2>Login System</h2>

        <?php if(!empty($error)): ?>
            <p style="color: red; text-align: center; background: #ffe6e6; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                <?php echo $error; ?>
            </p>
        <?php endif; ?>

        <div>
            <label for="username" style="font-weight: 600; display: block; margin-bottom: 5px;">Username</label>
            <input type="text" id="username" name="username" required placeholder="Enter username">
        </div>
        <div>
            <label for="password" style="font-weight: 600; display: block; margin-bottom: 5px;">Password</label>
            <input type="password" id="password" name="password" required placeholder="Enter password">
        </div>

        <label class="remember-group">
            <input type="checkbox" name="remember" value="1">
            Remember Me
        </label>

        <button type="submit" class="btn">Login</button>

        <p class="text-center mt-1" style="margin-top: 20px; text-align: center;">
            Don't have an account? <a href="register.php" style="color: #007bff; text-decoration: none; font-weight: bold;">Register here</a>
        </p>

        <div style="text-align: center; font-size: 0.85em; color: #666; margin-top: 25px; border-top: 1px solid #eee; padding-top: 15px;">
            Admin Account: <br>
            <strong>admin</strong> / <strong>123456</strong>
        </div>
    </form>
</div>

</body>
</html>