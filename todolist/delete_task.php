<?php
global $conn;
include 'db_connect.php';

if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: home.php");
    exit;
}

$task_id = $_GET['id'];
$current_user_id = $_SESSION['UserID'];

$stmt = $conn->prepare("DELETE FROM Task WHERE TaskID = ? AND UserID = ?");
$stmt->bind_param("ii", $task_id, $current_user_id);

if ($stmt->execute()) {
    header("Location: home.php");
    exit;
} else {
    echo "Error deleting task: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
