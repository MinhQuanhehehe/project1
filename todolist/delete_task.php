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

if (!isset($_GET['id']) || empty($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: home.php");
    exit;
}
$task_id = $_GET['id'];

// 3. Lấy thông tin Task trước khi xóa
$stmt_get = $conn->prepare("SELECT title FROM Tasks WHERE task_id = ? AND user_id = ?");
$stmt_get->bind_param("ii", $task_id, $user_id);
$stmt_get->execute();
$result = $stmt_get->get_result();

if ($result->num_rows == 1) {
    $task = $result->fetch_assoc();
    $task_title = $task['title'];
    $stmt_get->close();

    // Xóa Task
    $stmt_delete = $conn->prepare("DELETE FROM Tasks WHERE task_id = ? AND user_id = ?");
    $stmt_delete->bind_param("ii", $task_id, $user_id);

    if ($stmt_delete->execute()) {

        // Log
        $log_action = "DELETE";
        $log_table = "Tasks";
        $log_detail = "Deleted task: " . $task_title;

        $stmt_log = $conn->prepare("INSERT INTO ActivityLogs (user_id, action_type, target_table, target_id, details) VALUES (?, ?, ?, ?, ?)");
        $stmt_log->bind_param("issis", $user_id, $log_action, $log_table, $task_id, $log_detail);
        $stmt_log->execute();
        $stmt_log->close();
        $redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'home.php';
        header("Location: " . $redirect_url);
        exit;
    } else {
        echo "Error deleting task: " . $stmt_delete->error;
    }
    $stmt_delete->close();

} else {
    // Không tìm thấy task hoặc không có quyền xóa
    header("Location: home.php");
    exit;
}

$conn->close();
?>