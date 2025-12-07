<?php
global $conn;
include 'db_connect.php';

$user_id = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;
if (!$user_id) { header("Location: login.php"); exit; }

// LISTS & TAGS
$lists = $conn->query("SELECT list_id, list_name FROM Lists WHERE user_id = $user_id ORDER BY list_name");
$tags  = $conn->query("SELECT * FROM Tags WHERE user_id = $user_id ORDER BY tag_name");

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $description = $_POST['description'];
    $due_date = empty($_POST['due_date']) ? NULL : $_POST['due_date'];
    $list_id = !empty($_POST['list_id']) ? $_POST['list_id'] : NULL;
    $is_important = isset($_POST['is_important']) ? 1 : 0;
    $is_urgent = isset($_POST['is_urgent']) ? 1 : 0;

    // Lấy mảng Tags ID từ form (nếu có)
    $selected_tags = isset($_POST['tags']) ? $_POST['tags'] : [];

    if (empty($title)) {
        $error = "Task title is required.";
    } else {
        // Insert Task
        $stmt = $conn->prepare("INSERT INTO Tasks (user_id, list_id, title, description, due_date, is_important, is_urgent, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("iisssii", $user_id, $list_id, $title, $description, $due_date, $is_important, $is_urgent);

        if ($stmt->execute()) {
            $new_task_id = $conn->insert_id;

            if (!empty($selected_tags)) {
                $stmt_tag = $conn->prepare("INSERT INTO TaskTags (task_id, tag_id) VALUES (?, ?)");
                foreach ($selected_tags as $tag_id) {
                    $stmt_tag->bind_param("ii", $new_task_id, $tag_id);
                    $stmt_tag->execute();
                }
                $stmt_tag->close();
            }
            // Log
            $detail = "Created task: " . $title;
            $conn->query("INSERT INTO ActivityLogs (user_id, action_type, target_table, target_id, details) VALUES ($user_id, 'CREATE', 'Tasks', $new_task_id, '$detail')");

            header("Location: home.php");
            exit;
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Task - Todo App Pro</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2>Create New Task</h2>
        <a href="manage_tags.php" class="btn btn-secondary" style="font-size: 0.8em; padding: 5px 10px;">
            <i class="fas fa-tags"></i> Manage Tags
        </a>
    </div>

    <?php if(!empty($error)): ?>
        <p style="color: red; text-align: center;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form action="create_task.php" method="POST">
        <div>
            <label>Title *</label>
            <input type="text" name="title" required placeholder="What needs to be done?">
        </div>

        <div>
            <label>Description</label>
            <textarea name="description" placeholder="Add details..."></textarea>
        </div>

        <div style="display: flex; gap: 20px;">
            <div style="flex: 1;">
                <label>List</label>
                <select name="list_id">
                    <option value="">-- Inbox --</option>
                    <?php while($list = $lists->fetch_assoc()): ?>
                        <option value="<?php echo $list['list_id']; ?>"><?php echo htmlspecialchars($list['list_name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div style="flex: 1;">
                <label>Due Date</label>
                <input type="datetime-local" name="due_date">
            </div>
        </div>

        <div style="margin-top: 15px;">
            <label><i class="fas fa-tag"></i> Tags</label>
            <div style="display: flex; flex-wrap: wrap; gap: 10px; padding: 10px; background: #fff; border: 1px solid #ced4da; border-radius: 8px;">
                <?php
                if ($tags->num_rows > 0):
                    while($tag = $tags->fetch_assoc()):
                        ?>
                        <label style="display: inline-flex; align-items: center; cursor: pointer; margin-bottom: 0; font-weight: normal;">
                            <input type="checkbox" name="tags[]" value="<?php echo $tag['tag_id']; ?>" style="width: auto; margin-right: 5px;">
                            <span style="color: <?php echo $tag['color_code']; ?>; font-weight: bold;">
                            <?php echo htmlspecialchars($tag['tag_name']); ?>
                        </span>
                        </label>
                    <?php
                    endwhile;
                else:
                    ?>
                    <span style="color: #888; font-size: 0.9em;">No tags created yet. <a href="manage_tags.php">Create one?</a></span>
                <?php endif; ?>
            </div>
        </div>

        <div style="margin-top: 20px;">
            <label>Priority Matrix</label>
            <div class="eisenhower-group" style="display: flex; gap: 20px; padding: 10px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 8px;">
                <label style="display: flex; align-items: center; cursor: pointer;">
                    <input type="checkbox" name="is_important" value="1" style="width: auto; margin-right: 8px;"> Important
                </label>
                <label style="display: flex; align-items: center; cursor: pointer;">
                    <input type="checkbox" name="is_urgent" value="1" style="width: auto; margin-right: 8px;"> Urgent
                </label>
            </div>
        </div>

        <div style="margin-top: 20px; display: flex; gap: 10px;">
            <button type="submit" class="btn">Create Task</button>
            <a href="home.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
</body>
</html>