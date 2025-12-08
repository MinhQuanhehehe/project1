<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
global $conn;
include 'db_connect.php';

$user_id = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;
if (!$user_id) { header("Location: login.php"); exit; }

$task_id = $_GET['id'] ?? null;
if (!$task_id) { header("Location: home.php"); exit; }

$stmt = $conn->prepare("SELECT status FROM Tasks WHERE task_id = ? AND user_id = ?");
$stmt->bind_param("ii", $task_id, $user_id);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$task) { header("Location: home.php"); exit; }

$current_status = $task['status'];
$new_status = 'pending';
$completed_at = NULL;
$log_detail = "";

switch ($current_status) {
    case 'pending':
        $new_status = 'in_progress';
        $log_detail = "Started task (In Progress)";
        break;

    case 'in_progress':
        $new_status = 'completed';
        $completed_at = date("Y-m-d H:i:s");

        $conn->query("UPDATE SubTasks SET is_completed = 1 WHERE task_id = $task_id AND is_completed = 0");

        $affected_subtasks = $conn->affected_rows;

        $log_detail = "Completed task";
        if ($affected_subtasks > 0) {
            $log_detail .= " (auto-completed $affected_subtasks checklist items)";
        }
        break;

    case 'completed':
        $new_status = 'pending';
        $completed_at = NULL;

        $conn->query("UPDATE SubTasks SET is_completed = 0 WHERE task_id = $task_id AND is_completed = 1");

        $affected_subtasks = $conn->affected_rows;

        $log_detail = "Re-opened task (Pending)";
        if ($affected_subtasks > 0) {
            $log_detail .= " (reset $affected_subtasks checklist items)";
        }
        break;

    case 'canceled':
        $new_status = 'pending';
        $completed_at = NULL;
        $log_detail = "Restored task from Canceled";
        break;
}

$stmt_update = $conn->prepare("UPDATE Tasks SET status = ?, completed_at = ? WHERE task_id = ? AND user_id = ?");
$stmt_update->bind_param("ssii", $new_status, $completed_at, $task_id, $user_id);

if ($stmt_update->execute()) {
    $conn->query("INSERT INTO ActivityLogs (user_id, action_type, target_table, target_id, details) VALUES ($user_id, 'UPDATE', 'Tasks', $task_id, '$log_detail')");
}
$stmt_update->close();
$conn->close();

$redirect_url = $_SERVER['HTTP_REFERER'] ?? 'home.php';
header("Location: " . $redirect_url);
exit;
?>