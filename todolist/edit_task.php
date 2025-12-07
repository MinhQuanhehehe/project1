<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
global $conn;
include 'db_connect.php';

$user_id = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;
if (!$user_id) { header("Location: login.php"); exit; }

$task_id = $_GET['id'] ?? null;
if (!$task_id) { header("Location: home.php"); exit; }

$error_message = '';

// Lấy Lists
$lists_result = $conn->query("SELECT list_id, list_name FROM Lists WHERE user_id = $user_id ORDER BY list_name");

// Lấy Tất cả Tags của User
$all_tags_result = $conn->query("SELECT * FROM Tags WHERE user_id = $user_id ORDER BY tag_name");

// Lấy Tags hiện tại của Task này
$current_tags = [];
$stmt_cur_tags = $conn->prepare("SELECT tag_id FROM TaskTags WHERE task_id = ?");
$stmt_cur_tags->bind_param("i", $task_id);
$stmt_cur_tags->execute();
$res_cur = $stmt_cur_tags->get_result();
while ($row = $res_cur->fetch_assoc()) {
    $current_tags[] = $row['tag_id'];
}
$stmt_cur_tags->close();


// 2. XỬ LÝ POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $description = $_POST['description'];
    $due_date = empty($_POST['due_date']) ? NULL : $_POST['due_date'];
    $list_id = !empty($_POST['list_id']) ? $_POST['list_id'] : NULL;
    $is_important = isset($_POST['is_important']) ? 1 : 0;
    $is_urgent = isset($_POST['is_urgent']) ? 1 : 0;
    $status = $_POST['status'];

    $selected_tags = isset($_POST['tags']) ? $_POST['tags'] : [];

    if (empty($title)) {
        $error_message = "Title cannot be empty.";
    } else {
        // Update Tasks
        $stmt = $conn->prepare("UPDATE Tasks SET title=?, description=?, due_date=?, list_id=?, is_important=?, is_urgent=?, status=? WHERE task_id=? AND user_id=?");
        $stmt->bind_param("sssiiisii", $title, $description, $due_date, $list_id, $is_important, $is_urgent, $status, $task_id, $user_id);

        if ($stmt->execute()) {
            $conn->query("DELETE FROM TaskTags WHERE task_id = $task_id");
            if (!empty($selected_tags)) {
                $stmt_insert = $conn->prepare("INSERT INTO TaskTags (task_id, tag_id) VALUES (?, ?)");
                foreach ($selected_tags as $tag_id) {
                    $stmt_insert->bind_param("ii", $task_id, $tag_id);
                    $stmt_insert->execute();
                }
                $stmt_insert->close();
            }
            // Log
            $conn->query("INSERT INTO ActivityLogs (user_id, action_type, target_table, target_id, details) VALUES ($user_id, 'UPDATE', 'Tasks', $task_id, 'Updated task info and tags')");

            header("Location: home.php");
            exit;
        } else {
            $error_message = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// LẤY THÔNG TIN TASK ĐỂ ĐIỀN FORM
$stmt_task = $conn->prepare("SELECT * FROM Tasks WHERE task_id = ? AND user_id = ?");
$stmt_task->bind_param("ii", $task_id, $user_id);
$stmt_task->execute();
$task = $stmt_task->get_result()->fetch_assoc();
if (!$task) { header("Location: home.php"); exit; }
$formatted_date = $task['due_date'] ? date('Y-m-d\TH:i', strtotime($task['due_date'])) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Task - Todo App Pro</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<div class="container">
    <h2>Edit Task</h2>
    <?php if ($error_message): ?>
        <p style="color: red; text-align: center;"><?php echo htmlspecialchars($error_message); ?></p>
    <?php endif; ?>

    <form action="" method="POST">
        <div>
            <label>Title</label>
            <input type="text" name="title" value="<?php echo htmlspecialchars($task['title']); ?>" required>
        </div>

        <div>
            <label>Description</label>
            <textarea name="description"><?php echo htmlspecialchars($task['description']); ?></textarea>
        </div>

        <div style="display: flex; gap: 20px;">
            <div style="flex: 1;">
                <label>List</label>
                <select name="list_id">
                    <option value="">-- Inbox --</option>
                    <?php while($list = $lists_result->fetch_assoc()):
                        $sel = ($list['list_id'] == $task['list_id']) ? 'selected' : '';
                        ?>
                        <option value="<?php echo $list['list_id']; ?>" <?php echo $sel; ?>>
                            <?php echo htmlspecialchars($list['list_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div style="flex: 1;">
                <label>Due Date</label>
                <input type="datetime-local" name="due_date" value="<?php echo $formatted_date; ?>">
            </div>
        </div>

        <div style="margin-top: 15px;">
            <label><i class="fas fa-tags"></i> Tags</label>
            <div style="display: flex; flex-wrap: wrap; gap: 10px; padding: 10px; background: #fff; border: 1px solid #ced4da; border-radius: 8px;">
                <?php if ($all_tags_result->num_rows > 0): ?>
                    <?php while($tag = $all_tags_result->fetch_assoc()):
                        // Kiểm tra xem tag này có trong danh sách tag của task không
                        $is_checked = in_array($tag['tag_id'], $current_tags) ? 'checked' : '';
                        ?>
                        <label style="display: inline-flex; align-items: center; cursor: pointer; margin-bottom: 0;">
                            <input type="checkbox" name="tags[]" value="<?php echo $tag['tag_id']; ?>"
                                   style="width: auto; margin-right: 5px;" <?php echo $is_checked; ?>>
                            <span style="color: <?php echo $tag['color_code']; ?>; font-weight: bold;">
                                <?php echo htmlspecialchars($tag['tag_name']); ?>
                            </span>
                        </label>
                    <?php endwhile; ?>
                <?php else: ?>
                    <span style="color: #888;">No tags available.</span>
                <?php endif; ?>
            </div>
        </div>

        <div style="margin-top: 15px;">
            <label>Status</label>
            <select name="status">
                <option value="pending" <?php echo ($task['status']=='pending')?'selected':''; ?>>Pending</option>
                <option value="in_progress" <?php echo ($task['status']=='in_progress')?'selected':''; ?>>In Progress</option>
                <option value="completed" <?php echo ($task['status']=='completed')?'selected':''; ?>>Completed</option>
                <option value="canceled" <?php echo ($task['status']=='canceled')?'selected':''; ?>>Canceled</option>
            </select>
        </div>

        <div style="margin-top: 15px;">
            <label>Priority</label>
            <div class="eisenhower-group" style="display: flex; gap: 20px; padding: 10px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 8px;">
                <label><input type="checkbox" name="is_important" value="1" <?php echo $task['is_important']?'checked':''; ?> style="width: auto;"> Important</label>
                <label><input type="checkbox" name="is_urgent" value="1" <?php echo $task['is_urgent']?'checked':''; ?> style="width: auto;"> Urgent</label>
            </div>
        </div>

        <div style="margin-top: 20px; display: flex; gap: 10px;">
            <button type="submit" class="btn">Save Changes</button>
            <a href="home.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
</body>
</html>