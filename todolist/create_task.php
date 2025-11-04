<?php
global $conn;
include 'db_connect.php';

if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $due_date = $_POST['due_date'];
    $priority = $_POST['priority'];
    $user_id = $_SESSION['UserID'];

    if (empty($title)) {
        $error = "Title is required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO Task (UserID, Title, Description, DueDate, Priority) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user_id, $title, $description, $due_date, $priority);

        if ($stmt->execute()) {
            header("Location: home.php");
            exit;
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Task - Todo App</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h2>Create New Task</h2>

    <?php if(!empty($error)): ?>
        <p style="color: red; text-align: center;"><?php echo $error; ?></p>
    <?php endif; ?>

    <form action="create_task.php" method="POST">
        <div>
            <label for="title">Title</label>
            <input type="text" id="title" name="title" required>
        </div>
        <div>
            <label for="description">Description</label>
            <textarea id="description" name="description"></textarea>
        </div>
        <div>
            <label for="due_date">Due Date</label>
            <input type="date" id="due_date" name="due_date">
        </div>
        <div>
            <label for="priority">Priority</label>
            <select id="priority" name="priority">
                <option value="low">Low</option>
                <option value="medium" selected>Medium</option>
                <option value="high">High</option>
            </select>
        </div>
        <button type="submit" class="btn">Create Task</button>
        <a href="home.php" class="btn btn-secondary mt-1">Cancel</a>
    </form>
</div>
</body>
</html>
