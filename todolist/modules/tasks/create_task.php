<?php
global $conn;
include '../../config/db_connect.php';

$user_id = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;
if (!$user_id) {
    header("Location: ../../auth/login.php");
    exit;
}

// LISTS & TAGS
$lists = $conn->query("SELECT list_id, list_name FROM Lists WHERE user_id = $user_id ORDER BY list_name");
$tags  = $conn->query("SELECT * FROM Tags WHERE user_id = $user_id ORDER BY tag_name");

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $description = $_POST['description'];
    $due_date = empty($_POST['due_date']) ? NULL : $_POST['due_date'];
    $list_id = !empty($_POST['list_id']) ? $_POST['list_id'] : ($_GET['list_id'] ?? NULL);
    $is_important = isset($_POST['is_important']) ? 1 : 0;
    $is_urgent = isset($_POST['is_urgent']) ? 1 : 0;
    $selected_tags = isset($_POST['tags']) ? $_POST['tags'] : [];

    if (empty($title)) {
        $error = "Task title is required.";
    } else {
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

            header("Location: ../../home.php");
            exit;
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create New Task</title>
    <link rel="stylesheet" href="../../assets/css/style1.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<?php
$path_to_root = '../../';
include '../../includes/sidebar.php';
?>

<div class="main-content">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0; color: #2c3e50;"><i class="fas fa-plus-circle"></i> Create New Task</h2>
        <a href="../../home.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <div class="form-card">
        <?php if(!empty($error)): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label>Task Title <span style="color:red">*</span></label>
                <input type="text" name="title" required placeholder="What needs to be done?" class="form-control" style="font-size: 1.1em; padding: 12px;">
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="4" placeholder="Add details, notes, links..." class="form-control"></textarea>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <label>Add to List</label>
                    <select name="list_id" class="form-control">
                        <option value="">-- Inbox --</option>
                        <?php while($list = $lists->fetch_assoc()):
                            $selected = ($list['list_id'] == $list_id) ? 'selected' : '';
                            ?>
                            <option value="<?php echo $list['list_id']; ?>" <?php echo $selected; ?>>
                                <?php echo htmlspecialchars($list['list_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-col">
                    <label>Due Date</label>
                    <input type="datetime-local" name="due_date" class="form-control">
                </div>
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                    <label style="margin-bottom: 0;"><i class="fas fa-tags"></i> Tags</label>
                    <a href="../tags/manage_tags.php" style="font-size: 0.85em; color: #3498db; text-decoration: none;">
                        <i class="fas fa-plus"></i> Manage Tags
                    </a>
                </div>

                <div class="tag-selection-box">
                    <?php if ($tags->num_rows > 0): ?>
                        <?php while($tag = $tags->fetch_assoc()): ?>
                            <label class="tag-checkbox">
                                <input type="checkbox" name="tags[]" value="<?php echo $tag['tag_id']; ?>">
                                <span style="color: <?php echo $tag['color_code']; ?>; border-color: <?php echo $tag['color_code']; ?>;">
                                    <?php echo htmlspecialchars($tag['tag_name']); ?>
                                </span>
                            </label>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <span style="color: #888; font-size: 0.9em;">No tags found.</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label>Priority Matrix</label>
                <div class="priority-group">
                    <label class="priority-option">
                        <input type="checkbox" name="is_important" value="1">
                        <span style="color: #f1c40f;"><i class="fas fa-star"></i> Important</span>
                    </label>
                    <label class="priority-option">
                        <input type="checkbox" name="is_urgent" value="1">
                        <span style="color: #e74c3c;"><i class="fas fa-fire"></i> Urgent</span>
                    </label>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary-large">Create Task</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>