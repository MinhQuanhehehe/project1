<?php
global $conn;
include 'db_connect.php';

if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}
$current_user_id = $_SESSION['UserID'];

// Kiểm tra Task ID
if (!isset($_GET['id']) || empty($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: home.php");
    exit;
}
$task_id = $_GET['id'];

$sql = "UPDATE Task 
        SET IsCompleted = NOT IsCompleted 
        WHERE TaskID = ? AND UserID = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $task_id, $current_user_id);

if ($stmt->execute()) {
    $redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'home.php';
    header("Location: " . $redirect_url);
    exit;
} else {
    echo "Error updating task status: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>