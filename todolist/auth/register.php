<?php
session_start();
global $conn;
include '../config/db_connect.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($username === '' || $password === '' || $confirm_password === '') {
        $error = "Please fill in all required fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Password confirmation does not match.";
    } else {
        // Check Username or Email exist
        $stmt_check = $conn->prepare("SELECT user_id FROM Users WHERE username = ? OR (email IS NOT NULL AND email = ?)");
        $check_email = empty($email) ? NULL : $email;
        $stmt_check->bind_param("ss", $username, $check_email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $error = "Username or Email already exists.";
        } else {
            // Hash
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert
            $stmt_insert = $conn->prepare("INSERT INTO Users (username, email, password_hash) VALUES (?, ?, ?)");
            $stmt_insert->bind_param("sss", $username, $email, $hashed_password);

            if ($stmt_insert->execute()) {
                header("Location: login.php?registered=1");
                exit;
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Todo App Pro</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <style>
        body {
            /* Đường dẫn từ project1/auth/register.php tới project1/assets/css/background.jpg */
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
            background: rgba(255, 255, 255, 0.85); /* Trắng trong suốt */
            padding: 35px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
            backdrop-filter: blur(10px); /* Hiệu ứng kính mờ */
            border: 1px solid rgba(255, 255, 255, 0.3);
            margin: 20px;
        }

        .auth-container h2 {
            text-align: center;
            color: #333;
            margin-bottom: 25px;
            font-size: 24px;
        }

        .error-msg {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        input:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }

        .btn {
            width: 100%;
            padding: 13px;
            background-color: #007bff;
            border: none;
            color: white;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            margin-top: 10px;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,123,255,0.3);
        }

        .text-center {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }

        .text-center a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
        }

        .text-center a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="auth-container">
    <form action="register.php" method="POST">
        <h2>Create Account</h2>

        <?php if(!empty($error)): ?>
            <div class="error-msg">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="username">Username *</label>
            <input type="text" id="username" name="username"
                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                   required placeholder="Choose a username">
        </div>

        <div class="form-group">
            <label for="email">Email (Optional)</label>
            <input type="email" id="email" name="email"
                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                   placeholder="name@example.com">
        </div>

        <div class="form-group">
            <label for="password">Password *</label>
            <input type="password" id="password" name="password" required placeholder="Create a password">
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm Password *</label>
            <input type="password" id="confirm_password" name="confirm_password" required placeholder="Repeat password">
        </div>

        <button type="submit" class="btn">Register Now</button>

        <p class="text-center">
            Already have an account? <a href="login.php">Login here</a>
        </p>
    </form>
</div>

</body>
</html>
