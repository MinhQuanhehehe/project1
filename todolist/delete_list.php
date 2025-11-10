<?php
include 'db_connect.php'; // Đã bao gồm session_start()

// 1. BẢO VỆ TRANG: Phải đăng nhập
if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}
$current_user_id = $_SESSION['UserID'];

// 2. KIỂM TRA LIST ID: Phải có ID trên URL và là số
if (!isset($_GET['id']) || empty($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: home.php");
    exit;
}
$list_id = $_GET['id'];

// 3. XỬ LÝ XÓA (NẾU USER ĐÃ XÁC NHẬN "YES")
if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {

    // Xóa tất cả task thuộc list này
    $stmt_delete_tasks = $conn->prepare("DELETE FROM Task WHERE ListID = ? AND UserID = ?");
    $stmt_delete_tasks->bind_param("ii", $list_id, $current_user_id);
    $stmt_delete_tasks->execute();
    $stmt_delete_tasks->close();

    // Xóa list
    $stmt_delete = $conn->prepare("DELETE FROM List WHERE ListID = ? AND UserID = ?");
    $stmt_delete->bind_param("ii", $list_id, $current_user_id);

    if ($stmt_delete->execute()) {
        // Xóa thành công, quay về trang chủ
        header("Location: home.php?status=list_deleted");
        exit;
    } else {
        // Lỗi
        header("Location: home.php?status=list_delete_error");
        exit;
    }
    $stmt_delete->close();
}

// 4. LẤY TÊN LIST ĐỂ HIỂN THỊ XÁC NHẬN (NẾU CHƯA XÁC NHẬN "YES")
$stmt_get = $conn->prepare("SELECT ListName FROM List WHERE ListID = ? AND UserID = ?");
$stmt_get->bind_param("ii", $list_id, $current_user_id);
$stmt_get->execute();
$result_get = $stmt_get->get_result();

if ($result_get->num_rows != 1) {
    // Không tìm thấy List hoặc không phải chủ sở hữu
    header("Location: home.php");
    exit;
}
$list = $result_get->fetch_assoc();
$stmt_get->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Delete List - Todo App</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

<div class="container auth-container">
    <h2 class="text-danger">Confirm Delete</h2>

    <p>Are you sure you want to delete the list: <strong><?php echo htmlspecialchars($list['ListName']); ?></strong>?</p>

    <p class="text-secondary">
    Note: All tasks in this list will be deleted.
    </p>

    <form class='button-group' action="delete_list.php?id=<?php echo $list_id; ?>&confirm=yes" method="POST" class="form-confirm-delete">
        <button type="submit" class="btn btn-danger">Yes, Delete</button>
        <a href="home.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

</body>
</html>