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

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: home.php");
    exit;
}
$list_id = $_GET['id'];

// Kiểm tra xác nhận xóa
if (isset($_POST['confirm']) && $_POST['confirm'] == 'yes') {

    // Lấy tên list để ghi log
    $stmt_get = $conn->prepare("SELECT list_name FROM Lists WHERE list_id = ? AND user_id = ?");
    $stmt_get->bind_param("ii", $list_id, $user_id);
    $stmt_get->execute();
    $list_name = $stmt_get->get_result()->fetch_assoc()['list_name'] ?? 'Unknown List';
    $stmt_get->close();

    // Thực hiện xóa
    $stmt_delete = $conn->prepare("DELETE FROM Lists WHERE list_id = ? AND user_id = ?");
    $stmt_delete->bind_param("ii", $list_id, $user_id);

    if ($stmt_delete->execute()) {
        // log
        $stmt_log = $conn->prepare("INSERT INTO ActivityLogs (user_id, action_type, target_table, target_id, details) VALUES (?, 'DELETE', 'Lists', ?, ?)");
        $detail = "Deleted list: " . $list_name;
        $stmt_log->bind_param("iis", $user_id, $list_id, $detail);
        $stmt_log->execute();

        header("Location: home.php?status=list_deleted");
        exit;
    } else {
        echo "Error deleting list.";
    }
    $stmt_delete->close();
    exit;
}

// Lấy thông tin hiển thị Form xác nhận
$stmt_get = $conn->prepare("SELECT list_name FROM Lists WHERE list_id = ? AND user_id = ?");
$stmt_get->bind_param("ii", $list_id, $user_id);
$stmt_get->execute();
$result_get = $stmt_get->get_result();

if ($result_get->num_rows != 1) {
    header("Location: home.php");
    exit;
}
$list = $result_get->fetch_assoc();
$stmt_get->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete List - Todo App Pro</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container auth-container">
    <h2 style="color: #dc3545;">Delete List?</h2>

    <p>Are you sure you want to delete the list: <strong><?php echo htmlspecialchars($list['list_name']); ?></strong>?</p>

    <div style="background: #fff3cd; padding: 10px; border-radius: 5px; color: #856404; margin-bottom: 20px; font-size: 0.9em;">
        <strong>Note:</strong> Tasks in this list will NOT be deleted. They will be moved to "Inbox" (No List).
    </div>

    <form action="delete_list.php?id=<?php echo $list_id; ?>" method="POST" style="display: flex; gap: 10px; justify-content: center;">
        <input type="hidden" name="confirm" value="yes">
        <button type="submit" class="btn btn-danger" style="background-color: #dc3545;">Yes, Delete</button>
        <a href="home.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</body>
</html>