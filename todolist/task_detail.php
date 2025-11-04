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

$stmt = $conn->prepare("SELECT * FROM Task WHERE TaskID = ? AND UserID = ?");
$stmt->bind_param("ii", $task_id, $current_user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows != 1) {
    echo "Task not found.";
    exit;
}
$task = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Detail - Todo App</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h2><?php echo htmlspecialchars($task['Title']); ?></h2>

    <div class="task-detail">
        <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($task['Description'])); ?></p>
        <p><strong>Due Date:</strong> <?php echo htmlspecialchars($task['DueDate']); ?></p>
        <p><strong>Priority:</strong> <?php echo ucfirst(htmlspecialchars($task['Priority'])); ?></p>
    </div>

    <div class="task-actions mt-2" style="display: flex; gap: 1rem;">
        <!-- Nút Edit -->
        <a href="edit_task.php?id=<?php echo $task['TaskID']; ?>" class="btn btn-edit">Edit</a>
        <!-- Nút Delete (có confirm popup) -->
        <a href="delete_task.php?id=<?php echo $task['TaskID']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this task?');">Delete</a>
    </div>
    <a href="home.php" class="btn btn-secondary mt-1">Back to Home</a>
</div>
</body>
</html>
