<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized access");
}

$redirect_url = $_REQUEST['redirect'] ?? 'admin.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_role') {
        $uid = $_POST['user_id'];
        $new_role = $_POST['role'];

        if ($uid != $_SESSION['user_id']) {
            $stmt = $conn->prepare("UPDATE Users SET role = ? WHERE user_id = ?");
            $stmt->bind_param("si", $new_role, $uid);
            if ($stmt->execute()) {
                $admin_id = $_SESSION['user_id'];
                $log_detail = "Changed role for User ID $uid to $new_role";

                $conn->query("INSERT INTO ActivityLogs (user_id, action_type, target_table, target_id, details) 
                              VALUES ($admin_id, 'UPDATE', 'Users', $uid, '$log_detail')");
            }
        }
    }
    header("Location: " . $redirect_url);
    exit;
}

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? 0;

if ($action === 'delete_user' && $id > 0) {
    if ($id == $_SESSION['user_id']) {
        echo "<script>alert('Cannot delete yourself!'); window.location='$redirect_url';</script>";
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM Users WHERE user_id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $admin_id = $_SESSION['user_id'];
        $conn->query("INSERT INTO ActivityLogs (user_id, action_type, target_table, target_id, details) 
                      VALUES ($admin_id, 'DELETE', 'Users', $id, 'Admin deleted user ID $id')");
    }
}

if ($action === 'reset_pass' && $id > 0) {
    $default_pass = '123456';
    $hash = password_hash($default_pass, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE Users SET password_hash = ? WHERE user_id = ?");
    $stmt->bind_param("si", $hash, $id);
    if ($stmt->execute()) {
        $admin_id = $_SESSION['user_id'];
        $conn->query("INSERT INTO ActivityLogs (user_id, action_type, target_table, target_id, details) 
                      VALUES ($admin_id, 'UPDATE', 'Users', $id, 'Admin reset password for user ID $id')");
    }
}

if ($action === 'clean_logs') {
    $conn->query("DELETE FROM ActivityLogs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
}

$conn->close();
header("Location: " . $redirect_url);
exit;
?>