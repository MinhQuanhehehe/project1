<?php
// Tệp: toggle_complete.php
global $conn;
include 'db_connect.php'; // Bao gồm kết nối CSDL và session

// Bảo vệ: Phải đăng nhập
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

// TRUY VẤN: Lật trạng thái IsCompleted (sử dụng NOT để đảo ngược giá trị)
// Đảm bảo chỉ user hiện tại được phép thay đổi
$sql = "UPDATE Task 
        SET IsCompleted = NOT IsCompleted 
        WHERE TaskID = ? AND UserID = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $task_id, $current_user_id);

if ($stmt->execute()) {
    // Thành công: Quay lại trang trước (home.php hoặc trang có bộ lọc)
    $redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'home.php';
    header("Location: " . $redirect_url);
    exit;
} else {
    // Xử lý lỗi
    echo "Error updating task status: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>