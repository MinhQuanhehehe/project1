<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
global $conn;
include '../config/db_connect.php';

// Check Authentication
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: auth/login.php");
    exit;
}

$message = "";
$msg_type = ""; // 'success' or 'error'

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // 1. Validate input
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = "Please fill in all fields.";
        $msg_type = "error";
    } elseif ($new_password !== $confirm_password) {
        $message = "New passwords do not match.";
        $msg_type = "error";
    } elseif (strlen($new_password) < 6) {
        $message = "New password must be at least 6 characters long.";
        $msg_type = "error";
    } else {
        // 2. Fetch current password hash from DB
        $stmt = $conn->prepare("SELECT password FROM Users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            // 3. Verify current password
            if (password_verify($current_password, $row['password'])) {
                // 4. Hash new password and update
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);

                $update_stmt = $conn->prepare("UPDATE Users SET password = ? WHERE user_id = ?");
                $update_stmt->bind_param("si", $new_hash, $user_id);

                if ($update_stmt->execute()) {
                    $message = "Password changed successfully!";
                    $msg_type = "success";
                } else {
                    $message = "System error, please try again later.";
                    $msg_type = "error";
                }
            } else {
                $message = "Incorrect current password.";
                $msg_type = "error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password - Todo Pro</title>
    <link rel="stylesheet" href="../assets/css/style1.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Form Card Styling */
        .form-card {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            max-width: 500px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }

        .form-control:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .btn-submit {
            background-color: #3498db;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            width: 100%;
            transition: background 0.3s;
        }

        .btn-submit:hover {
            background-color: #2980b9;
        }

        /* Alert Messages */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-error {
            background-color: #fce4e4;
            color: #c0392b;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>

<?php
$path_to_root = '../';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div style="margin-bottom: 30px;">
        <h2 style="margin: 0; color: #2c3e50;">Change Password</h2>
        <p style="color: #7f8c8d;">Update your security credentials.</p>
    </div>

    <div class="form-card">

        <?php if (!empty($message)): ?>
            <div class="alert <?php echo ($msg_type == 'error') ? 'alert-error' : 'alert-success'; ?>">
                <?php if($msg_type == 'error'): ?>
                    <i class="fas fa-exclamation-circle"></i>
                <?php else: ?>
                    <i class="fas fa-check-circle"></i>
                <?php endif; ?>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label class="form-label">Current Password</label>
                <input type="password" name="current_password" class="form-control" placeholder="Enter current password" required>
            </div>

            <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control" placeholder="Enter new password (min 6 chars)" required>
            </div>

            <div class="form-group">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter new password" required>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-save"></i> Update Password
            </button>
        </form>
    </div>
</div>

</body>
</html>