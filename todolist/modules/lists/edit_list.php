<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
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

$stmt_get = $conn->prepare("SELECT list_name, color_code FROM Lists WHERE list_id = ? AND user_id = ?");
$stmt_get->bind_param("ii", $list_id, $user_id);
$stmt_get->execute();
$list = $stmt_get->get_result()->fetch_assoc();
if (!$list) { header("Location: manage_lists.php"); exit; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit List</title>
    <link rel="stylesheet" href="../../assets/css/style1.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<?php
$path_to_root = '../../';
include '../../includes/sidebar.php';
?>

<div class="main-content">
    <div style="max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
        <h2 style="margin-top: 0; color: #2c3e50; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 20px;">
            <i class="fas fa-edit"></i> Edit List
        </h2>

        <?php if (!empty($error_message)): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form action="edit_list.php?id=<?php echo $list_id; ?>" method="POST">
            <div style="margin-bottom: 15px;">
                <label style="font-weight: 600; display: block; margin-bottom: 5px;">List Name</label>
                <input type="text" name="list_name" value="<?php echo htmlspecialchars($list['list_name']); ?>" required class="filter-field">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="font-weight: 600; display: block; margin-bottom: 5px;">Color</label>
                <input type="color" name="color_code" value="<?php echo htmlspecialchars($list['color_code'] ?? '#007bff'); ?>" style="height: 45px; width: 100%; padding: 2px; border: 1px solid #ced4da; border-radius: 6px;">
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn">Save Changes</button>
                <a href="manage_lists.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>