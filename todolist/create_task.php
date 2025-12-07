<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
global $conn;
include 'db_connect.php';

$user_id = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;
if (!$user_id) { header("Location: login.php"); exit; }

$task_id = $_GET['id'] ?? null;
if ($task_id) {
    // Update status = canceled
    $stmt = $conn->prepare("UPDATE Tasks SET status = 'canceled' WHERE task_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $task_id, $user_id);
    if ($stmt->execute()) {
        // Ghi log
        $conn->query("INSERT INTO ActivityLogs (user_id, action_type, target_table, target_id, details) VALUES ($user_id, 'UPDATE', 'Tasks', $task_id, 'Canceled task')");
    }
    $stmt->close();
}
$conn->close();

$redirect_url = $_SERVER['HTTP_REFERER'] ?? 'home.php';
header("Location: " . $redirect_url);
exit;
?>