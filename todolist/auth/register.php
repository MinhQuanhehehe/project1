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
        // Check Username or Email exsist
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
            $insert_email = empty($email) ? NULL : $email;
            $stmt_insert->bind_param("sss", $username, $insert_email, $hashed_password);

            if ($stmt_insert->execute()) {
                $new_user_id = $conn->insert_id;

                // Log
                $log_action = "REGISTER";
                $log_table = "Users";
                $log_detail = "New user registered: " . $username;

                $stmt_log = $conn->prepare("INSERT INTO ActivityLogs (user_id, action_type, target_table, target_id, details) VALUES (?, ?, ?, ?, ?)");
                $stmt_log->bind_param("issis", $new_user_id, $log_action, $log_table, $new_user_id, $log_detail);
                $stmt_log->execute();
                $stmt_log->close();
                echo "<script>
                        alert('Account created successfully! Please login to continue.');
                        window.location.href = 'login.php';
                      </script>";
                exit;
            } else {
                $error = "Error: " . $stmt_insert->error;
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - Todo App Pro</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="container auth-container">
    <form action="register.php" method="POST" autocomplete="off">
        <h2>Create Account</h2>

        <?php if(!empty($error)): ?>
            <div style="color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div>
            <label for="username">Username *</label>
            <input type="text" id="username" name="username"
                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                   required placeholder="Choose a username">
        </div>

        <div>
            <label for="email">Email (Optional)</label>
            <input type="email" id="email" name="email"
                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                   placeholder="name@example.com">
        </div>

        <div>
            <label for="password">Password *</label>
            <input type="password" id="password" name="password" required placeholder="Create a password">
        </div>

        <div>
            <label for="confirm_password">Confirm Password *</label>
            <input type="password" id="confirm_password" name="confirm_password" required placeholder="Repeat password">
        </div>

        <button type="submit" class="btn">Register</button>

        <p class="text-center mt-1">
            Already have an account? <a href="login.php">Login here</a>
        </p>
    </form>
</div>
</body>
</html>