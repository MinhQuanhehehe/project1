<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
global $conn;
include '../../config/db_connect.php';

$user_id = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;
if (!$user_id) {
    header("Location: ../../auth/login.php");
    exit;
}

$task_id = $_GET['id'] ?? null;
if (!$task_id) {
    header("Location: ../../home.php");
    exit;
}

$error_message = '';

// Lists
$lists_result = $conn->query("SELECT list_id, list_name FROM Lists WHERE user_id = $user_id ORDER BY list_name");

// Tags
$all_tags_result = $conn->query("SELECT * FROM Tags WHERE user_id = $user_id ORDER BY tag_name");

// Curent Tags
$current_tags = [];
$stmt_cur_tags = $conn->prepare("SELECT tag_id FROM TaskTags WHERE task_id = ?");
$stmt_cur_tags->bind_param("i", $task_id);
$stmt_cur_tags->execute();
$res_cur = $stmt_cur_tags->get_result();
while ($row = $res_cur->fetch_assoc()) { $current_tags[] = $row['tag_id']; }

// UPDATE
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

            header("Location: ../../home.php");
            exit;
        } else {
            $error_message = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Task Info
$stmt_task = $conn->prepare("SELECT * FROM Tasks WHERE task_id = ? AND user_id = ?");
$stmt_task->bind_param("ii", $task_id, $user_id);
$stmt_task->execute();
$task = $stmt_task->get_result()->fetch_assoc();
if (!$task) { header("Location: ../../home.php"); exit; }
$formatted_date = $task['due_date'] ? date('Y-m-d\TH:i', strtotime($task['due_date'])) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Task</title>
    <link rel="stylesheet" href="../../assets/css/style1.css?v=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<?php
$path_to_root = '../../';
include '../../includes/sidebar.php';
?>

<div class="main-content">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0; color: #2c3e50;"><i class="fas fa-edit"></i> Edit Task</h2>
        <a href="../../home.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <div class="form-card">
        <?php if ($error_message): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($task['title']); ?>" required class="form-control" style="font-size: 1.1em; padding: 12px;">
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="4" class="form-control"><?php echo htmlspecialchars($task['description']); ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <label>List</label>
                    <select name="list_id" class="form-control">
                        <option value="">-- Inbox --</option>
                        <?php while($list = $lists_result->fetch_assoc()): ?>
                            <option value="<?php echo $list['list_id']; ?>" <?php echo ($list['list_id'] == $task['list_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($list['list_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-col">
                    <label>Due Date</label>
                    <input type="datetime-local" name="due_date" value="<?php echo $formatted_date; ?>" class="form-control">
                </div>
                <div class="form-col">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="pending" <?php echo ($task['status']=='pending')?'selected':''; ?>>Pending</option>
                        <option value="in_progress" <?php echo ($task['status']=='in_progress')?'selected':''; ?>>In Progress</option>
                        <option value="completed" <?php echo ($task['status']=='completed')?'selected':''; ?>>Completed</option>
                        <option value="canceled" <?php echo ($task['status']=='canceled')?'selected':''; ?>>Canceled</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label><i class="fas fa-tags"></i> Tags</label>
                <div class="tag-selection-box">
                    <?php if ($all_tags_result->num_rows > 0): ?>
                        <?php while($tag = $all_tags_result->fetch_assoc()):
                            $is_checked = in_array($tag['tag_id'], $current_tags) ? 'checked' : '';
                            ?>
                            <label class="tag-checkbox">
                                <input type="checkbox" name="tags[]" value="<?php echo $tag['tag_id']; ?>" <?php echo $is_checked; ?>>
                                <span style="color: <?php echo $tag['color_code']; ?>; border-color: <?php echo $tag['color_code']; ?>;">
                                    <?php echo htmlspecialchars($tag['tag_name']); ?>
                                </span>
                            </label>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <span style="color: #888;">No tags available.</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label>Priority</label>
                <div class="priority-group">
                    <label class="priority-option">
                        <input type="checkbox" name="is_important" value="1" <?php echo $task['is_important']?'checked':''; ?>>
                        <span style="color: #f1c40f;"><i class="fas fa-star"></i> Important</span>
                    </label>
                    <label class="priority-option">
                        <input type="checkbox" name="is_urgent" value="1" <?php echo $task['is_urgent']?'checked':''; ?>>
                        <span style="color: #e74c3c;"><i class="fas fa-fire"></i> Urgent</span>
                    </label>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary-large">Save Changes</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
