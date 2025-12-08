<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
global $conn;
include 'db_connect.php';

$user_id = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
        $error = "Please fill in all fields.";
    } elseif ($new_pass !== $confirm_pass) {
        $error = "New password and confirmation do not match.";
    } else {
        $stmt = $conn->prepare("SELECT password_hash FROM Users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($current_pass, $user['password_hash'])) {
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);

            $stmt_update = $conn->prepare("UPDATE Users SET password_hash = ? WHERE user_id = ?");
            $stmt_update->bind_param("si", $new_hash, $user_id);

            if ($stmt_update->execute()) {
                $conn->query("INSERT INTO ActivityLogs (user_id, action_type, target_table, target_id, details) 
                              VALUES ($user_id, 'UPDATE', 'Users', $user_id, 'User changed their own password')");

                $success = "Password changed successfully!";
            } else {
                $error = "Error updating password.";
            }
            $stmt_update->close();
        } else {
            $error = "Current password is incorrect.";
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password - Todo App Pro</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<div class="container auth-container">
    <form action="change_password.php" method="POST">
        <h2><i class="fas fa-key"></i> Change Password</h2>

        <?php if(!empty($error)): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if(!empty($success)): ?>
            <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div>
            <label>Current Password</label>
            <input type="password" name="current_password" required placeholder="Enter current password">
        </div>

        <div>
            <label>New Password</label>
            <input type="password" name="new_password" required placeholder="Enter new password">
        </div>

        <div>
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" required placeholder="Re-enter new password">
        </div>

        <div style="margin-top: 20px; display: flex; flex-direction: column; gap: 10px;">
            <button type="submit" class="btn">Update Password</button>
            <a href="home.php" class="btn btn-secondary" style="text-align: center;">Cancel / Back to Home</a>
        </div>
    </form>
</div>
</body>
</html>