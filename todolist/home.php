<?php
global $conn;
include 'db_connect.php';

if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

$current_user_id = $_SESSION['UserID'];
$current_username = $_SESSION['Username'];

$search_query = '';
if (isset($_GET['search_query']) && !empty(trim($_GET['search_query']))) {
    $search_query = trim($_GET['search_query']);
}

$sql = "SELECT TaskID, Title, Priority FROM Task WHERE UserID = ?";
$types = "i";
$params = [$current_user_id];

if (!empty($search_query)) {
    $sql .= " AND (Title LIKE ? OR Description LIKE ?)";

    $types .= "ss";

    $search_like_query = "%" . $search_query . "%";

    $params[] = $search_like_query;
    $params[] = $search_like_query;
}

$sql .= " ORDER BY CreatedAt DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$tasks_result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Todo App</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <div class="header-nav">
        <h2>Welcome, <?php echo htmlspecialchars($current_username); ?>!</h2>
        <a href="logout.php" class="btn btn-secondary">Logout</a>
    </div>

    <a href="create_task.php" class="btn mt-1">Create New Task</a>

    <form action="home.php" method="GET" class="mt-2">
        <div style="display: flex;">
            <input type="text" name="search_query" placeholder="Search tasks by title or description..."
                   value="<?php echo htmlspecialchars($search_query); ?>"
                   style="flex-grow: 1; padding: 0.75rem; border: 1px solid #ddd; border-radius: 5px;">
            <button type="submit" class="btn" style="width: auto; margin-left: 0.5rem; padding: 0.75rem 1rem;">Search</button>
        </div>
    </form>

    <h3 class="mt-2"><?php echo !empty($search_query) ? 'Search Results' : 'Your Tasks'; ?></h3>
    <ul class="task-list">
        <?php if ($tasks_result->num_rows > 0): ?>
            <?php while($task = $tasks_result->fetch_assoc()): ?>
                <li class="task-item">
                        <span class="task-title">
                            <a href="task_detail.php?id=<?php echo $task['TaskID']; ?>">
                                <?php echo htmlspecialchars($task['Title']); ?>
                            </a>
                            <small>(<?php echo htmlspecialchars($task['Priority']); ?>)</small>
                        </span>
                    <div class="task-actions">
                        <a href="edit_task.php?id=<?php echo $task['TaskID']; ?>" class="btn btn-edit">Edit</a>
                        <a href="delete_task.php?id=<?php echo $task['TaskID']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this task?');">Delete</a>
                    </div>
                </li>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-center mt-1">
                <?php echo !empty($search_query) ? 'No tasks found matching your search.' : 'You have no tasks yet. Create one!'; ?>
            </p>
        <?php endif; ?>
    </ul>
</div>
</body>
</html>
<?php $conn->close(); ?>

