<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
global $conn;
include '../../config/db_connect.php';

$user_id = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;
if (!$user_id) { header("Location: ../../auth/login.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subtask'])) {
    $task_id = $_POST['task_id'];
    $title = trim($_POST['subtask_title']);

    if (!empty($title) && is_numeric($task_id)) {
        $check = $conn->prepare("SELECT task_id FROM Tasks WHERE task_id = ? AND user_id = ?");
        $check->bind_param("ii", $task_id, $user_id);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $stmt = $conn->prepare("INSERT INTO SubTasks (task_id, title, is_completed) VALUES (?, ?, 0)");
            $stmt->bind_param("is", $task_id, $title);

            if ($stmt->execute()) {
                $new_sub_id = $conn->insert_id;

                $log_detail = "Added checklist item: " . $title;
                $conn->query("INSERT INTO ActivityLogs (user_id, action_type, target_table, target_id, details) VALUES ($user_id, 'CREATE', 'SubTasks', $new_sub_id, '$log_detail')");

                $conn->query("UPDATE Tasks SET status = 'in_progress' WHERE task_id = $task_id AND status = 'pending'");

                if ($conn->affected_rows > 0) {
                    $conn->query("INSERT INTO ActivityLogs (user_id, action_type, target_table, target_id, details) VALUES ($user_id, 'UPDATE', 'Tasks', $task_id, 'Auto-started task (In Progress) due to new checklist item')");
                }
            }
            $stmt->close();
        }
        $check->close();
    }
    header("Location: task_detail.php?id=" . $task_id);
    exit;
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $subtask_id = $_GET['id'];

    $sql_check = "SELECT st.task_id, st.is_completed, st.title FROM SubTasks st JOIN Tasks t ON st.task_id = t.task_id WHERE st.subtask_id = ? AND t.user_id = ?";
    $stmt = $conn->prepare($sql_check);
    $stmt->bind_param("ii", $subtask_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $parent_task_id = $row['task_id'];
        $current_status = $row['is_completed'];
        $sub_title = $row['title'];

        if ($_GET['action'] === 'toggle') {
            $new_sub_status = $current_status ? 0 : 1;
            $conn->query("UPDATE SubTasks SET is_completed = $new_sub_status WHERE subtask_id = $subtask_id");

            $status_text = $new_sub_status ? "Completed" : "Unchecked";
            $log_detail = "$status_text checklist item: $sub_title";
            $safe_detail = $conn->real_escape_string($log_detail);

            $conn->query("INSERT INTO ActivityLogs (user_id, action_type, target_table, target_id, details) VALUES ($user_id, 'UPDATE', 'SubTasks', $subtask_id, '$safe_detail')");

            if ($new_sub_status == 1) {
                $conn->query("UPDATE Tasks SET status = 'in_progress' WHERE task_id = $parent_task_id AND (status = 'pending' OR status = 'canceled')");

                if ($conn->affected_rows > 0) {
                    $conn->query("INSERT INTO ActivityLogs (user_id, action_type, target_table, target_id, details) VALUES ($user_id, 'UPDATE', 'Tasks', $parent_task_id, 'Auto-started task (In Progress) due to checklist activity')");
                }
            }
        } elseif ($_GET['action'] === 'delete') {
            $conn->query("DELETE FROM SubTasks WHERE subtask_id = $subtask_id");

            $log_detail = "Deleted checklist item: " . $sub_title;
            $safe_detail = $conn->real_escape_string($log_detail);
            $conn->query("INSERT INTO ActivityLogs (user_id, action_type, target_table, target_id, details) VALUES ($user_id, 'DELETE', 'SubTasks', $subtask_id, '$safe_detail')");
        }

        header("Location: task_detail.php?id=" . $parent_task_id);
        exit;
    }
}

header("Location: ../../home.php");
exit;
?>