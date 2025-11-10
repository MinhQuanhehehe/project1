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
$error_message = '';

// 3. XỬ LÝ FORM KHI USER SUBMIT (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_list_name = trim($_POST['list_name']);

    if (empty($new_list_name)) {
        $error_message = "List name cannot be empty.";
    } else {
        // Kiểm tra xem tên mới có bị trùng với list khác của user này không
        $stmt_check = $conn->prepare("SELECT ListID FROM List WHERE UserID = ? AND ListName = ? AND ListID != ?");
        $stmt_check->bind_param("isi", $current_user_id, $new_list_name, $list_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $error_message = "A list with this name already exists.";
        } else {
            // Cập nhật tên list
            $stmt_update = $conn->prepare("UPDATE List SET ListName = ? WHERE ListID = ? AND UserID = ?");
            $stmt_update->bind_param("sii", $new_list_name, $list_id, $current_user_id);

            if ($stmt_update->execute()) {
                header("Location: home.php?status=list_edited");
                exit;
            } else {
                $error_message = "Failed to update list. Please try again.";
            }
            $stmt_update->close();
        }
        $stmt_check->close();
    }
}

// 4. LẤY DỮ LIỆU LIST ĐỂ HIỂN THỊ (GET)
// (Cũng chạy nếu POST bị lỗi để nạp lại tên cũ)
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
    <title>Edit List - Todo App</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

<div class="container auth-container">
    <h2>Edit List</h2>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <form action="edit_list.php?id=<?php echo $list_id; ?>" method="POST">
        <div>
            <label for="list_name">List Name *</label>
            <!-- Dùng $list['ListName'] lấy từ CSDL để điền vào value -->
            <input type="text" id="list_name" name="list_name" value="<?php echo htmlspecialchars($list['ListName']); ?>" required>
        </div>
        <div class="button-group">
            <button type="submit" class="btn">Save Changes</button>
            <a href="home.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

</body>
</html>