<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
global $conn;
include '../../config/db_connect.php';

$user_id = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;
if (!$user_id) {
    header("Location: ../../auth/login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../../home.php");
    exit;
}
$list_id = $_GET['id'];
$error_message = '';

// Xử lý POST (Update)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_list_name = trim($_POST['list_name']);
    $new_color = $_POST['color_code'];

    if (empty($new_list_name)) {
        $error_message = "List name cannot be empty.";
    } else {
        // Check double
        $stmt_check = $conn->prepare("SELECT list_id FROM Lists WHERE user_id = ? AND list_name = ? AND list_id != ?");
        $stmt_check->bind_param("isi", $user_id, $new_list_name, $list_id);
        $stmt_check->execute();

        if ($stmt_check->get_result()->num_rows > 0) {
            $error_message = "A list with this name already exists.";
        } else {
            // Update
            $stmt_update = $conn->prepare("UPDATE Lists SET list_name = ?, color_code = ? WHERE list_id = ? AND user_id = ?");
            $stmt_update->bind_param("ssii", $new_list_name, $new_color, $list_id, $user_id);

            if ($stmt_update->execute()) {
                // Log
                $stmt_log = $conn->prepare("INSERT INTO ActivityLogs (user_id, action_type, target_table, target_id, details) VALUES (?, 'UPDATE', 'Lists', ?, ?)");
                $detail = "Updated list: " . $new_list_name;
                $stmt_log->bind_param("iis", $user_id, $list_id, $detail);
                $stmt_log->execute();

                header("Location: manage_lists.php?status=list_edited");
                exit;
            } else {
                $error_message = "Failed to update list.";
            }
            $stmt_update->close();
        }
        $stmt_check->close();
    }
}

// Old Info
$stmt_get = $conn->prepare("SELECT list_name, color_code FROM Lists WHERE list_id = ? AND user_id = ?");
$stmt_get->bind_param("ii", $list_id, $user_id);
$stmt_get->execute();
$result_get = $stmt_get->get_result();

if ($result_get->num_rows != 1) {
    header("Location: ../../home.php");
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
    <title>Edit List - Todo App Pro</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
<div class="container auth-container">
    <h2>Edit List</h2>
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger" style="color: red; text-align: center; margin-bottom: 10px;"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <form action="edit_list.php?id=<?php echo $list_id; ?>" method="POST">
        <div>
            <label for="list_name">List Name</label>
            <input type="text" id="list_name" name="list_name" value="<?php echo htmlspecialchars($list['list_name']); ?>" required>
        </div>
        <div>
            <label for="color_code">Color</label>
            <input type="color" id="color_code" name="color_code" value="<?php echo htmlspecialchars($list['color_code'] ?? '#007bff'); ?>" style="height: 40px; width: 100%; padding: 2px;">
        </div>
        <div class="button-group" style="display: flex; gap: 10px; margin-top: 20px;">
            <button type="submit" class="btn">Save Changes</button>
            <a href="../../home.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
</body>
</html>