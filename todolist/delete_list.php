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

    // Câu lệnh DELETE
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
</head>
<body>

<div class="container auth-container">
    <h2 class="text-danger">Confirm Delete</h2>

    <p>Are you sure you want to delete the list: <strong><?php echo htmlspecialchars($list['ListName']); ?></strong>?</p>

    <p class="text-secondary">
        Note: Tasks in this list will not be deleted. They will be moved to "Tasks (No List)".
    </p>

    <form action="delete_list.php?id=<?php echo $list_id; ?>&confirm=yes" method="POST" class="form-confirm-delete">
        <!-- Nút "Yes" sẽ submit form (hoặc đơn giản là 1 link) -->
        <a href="delete_list.php?id=<?php echo $list_id; ?>&confirm=yes" class="btn btn-danger">Yes, Delete</a>

        <!-- Nút "No" quay về trang chủ -->
        <a href="home.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

</body>
</html>