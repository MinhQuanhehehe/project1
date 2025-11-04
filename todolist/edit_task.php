<?php
global $conn;
include 'db_connect.php';

if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: home.php");
    exit;
}
$task_id = $_GET['id'];
$current_user_id = $_SESSION['UserID'];
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $due_date = $_POST['due_date'];
    $priority = $_POST['priority'];

    if (empty($title)) {
        $error = "Title is required.";
    } else {
        $stmt = $conn->prepare("UPDATE Task SET Title = ?, Description = ?, DueDate = ?, Priority = ? WHERE TaskID = ? AND UserID = ?");
        $stmt->bind_param("ssssii", $title, $description, $due_date, $priority, $task_id, $current_user_id);

        if ($stmt->execute()) {
            header("Location: home.php"); // Sửa xong về trang chủ
            exit;
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

$stmt_get = $conn->prepare("SELECT * FROM Task WHERE TaskID = ? AND UserID = ?");
$stmt_get->bind_param("ii", $task_id, $current_user_id);
$stmt_get->execute();
$result = $stmt_get->get_result();

if ($result->num_rows != 1) {
    echo "Task not found or you don't have permission to edit it.";
    exit;
}
$task = $result->fetch_assoc();
$stmt_get->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Task - Todo App</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h2>Edit Task</h2>

    <?php if(!empty($error)): ?>
        <p style="color: red; text-align: center;"><?php echo $error; ?></p>
    <?php endif; ?>

    <form action="edit_task.php?id=<?php echo $task_id; ?>" method="POST">
        <div>
            <label for="title">Title</label>
            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($task['Title']); ?>" required>
        </div>
        <div>
            <label for="description">Description</label>
            <textarea id="description" name="description"><?php echo htmlspecialchars($task['Description']); ?></textarea>
        </div>
        <div>
            <label for="due_date">Due Date</label>
            <input type="date" id="due_date" name="due_date" value="<?php echo htmlspecialchars($task['DueDate']); ?>">
        </div>
        <div>
            <label for="priority">Priority</label>
            <select id="priority" name="priority">
                <!-- Dùng 'selected' để chọn đúng priority hiện tại -->
                <option value="low" <?php if($task['Priority'] == 'low') echo 'selected'; ?>>Low</option>
                <option value="medium" <?php if($task['Priority'] == 'medium') echo 'selected'; ?>>Medium</option>
                <option value="high" <?php if($task['Priority'] == 'high') echo 'selected'; ?>>High</option>
            </select>
        </div>
        <button type="submit" class="btn">Save Changes</button>
        <a href="home.php" class="btn btn-secondary mt-1">Cancel</a>
    </form>
</div>
</body>
</html>
