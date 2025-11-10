<?php
global $conn;
include 'db_connect.php'; 

if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

$current_user_id = $_SESSION['UserID'];
$username = $_SESSION['Username'];

$stmt_lists = $conn->prepare("SELECT ListID, ListName FROM List WHERE UserID = ? ORDER BY ListName");
$stmt_lists->bind_param("i", $current_user_id);
$stmt_lists->execute();
$lists_data = $stmt_lists->get_result();

$page_title = "Your Tasks";
$search_query = isset($_GET['search_query']) ? trim($_GET['search_query']) : '';
$list_filter_id = isset($_GET['list_id']) ? $_GET['list_id'] : null;

$params = [$current_user_id];
$types = "i";
$where_clauses = "WHERE t.UserID = ?";

if (isset($_GET['list_id']) && $_GET['list_id'] !== '') {
    $list_filter_id = $_GET['list_id'];

    if ($list_filter_id === 'none') {
        $where_clauses .= " AND t.ListID IS NULL";
        $page_title = "Tasks (No List)";
    } else {
        $where_clauses .= " AND t.ListID = ?";
        $params[] = $list_filter_id;
        $types .= "i";

        $stmt_title = $conn->prepare("SELECT ListName FROM List WHERE ListID = ? AND UserID = ?");
        $stmt_title->bind_param("ii", $list_filter_id, $current_user_id);
        $stmt_title->execute();
        $list_title_result = $stmt_title->get_result();
        if ($list_title_row = $list_title_result->fetch_assoc()) {
            $page_title = "Tasks: " . htmlspecialchars($list_title_row['ListName']);
        }
        $stmt_title->close();
    }
} else {
    $list_filter_id = null;
    $page_title = "All Tasks";
}

if (!empty($search_query)) {
    $where_clauses .= " AND (t.Title LIKE ? OR t.Description LIKE ?)";
    $like_query = "%" . $search_query . "%";
    $params[] = $like_query;
    $params[] = $like_query;
    $types .= "ss";
    $page_title = "Search Results";
}

$sql = "SELECT t.*, l.ListName, t.IsCompleted
       FROM Task t
       LEFT JOIN List l ON t.ListID = l.ListID
       $where_clauses
       ORDER BY t.IsCompleted ASC, t.CreatedAt DESC";

$stmt_tasks = $conn->prepare($sql);

if (!empty($params)) {
    $stmt_tasks->bind_param($types, ...$params);
}

$stmt_tasks->execute();
$tasks_result = $stmt_tasks->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Todo App</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<div class="container">
    <div class="header">
        <h2>Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
        <a href="logout.php" class="btn btn-secondary">Logout</a>
    </div>
    <div class="task-controls">
        <a href="create_task.php" class="btn">Create New Task</a>
        <a href="create_list.php" class="btn btn-secondary">Create New List</a>
    </div>

    <div class="search-bar">
        <form action="home.php" method="GET">
            <?php if ($list_filter_id !== null): ?>
                <input type="hidden" name="list_id" value="<?php echo htmlspecialchars($list_filter_id); ?>">
            <?php endif; ?>
            <input type="text" name="search_query" placeholder="Search tasks..." value="<?php echo htmlspecialchars($search_query); ?>">
            <button type="submit" class="btn">Search</button>
        </form>
    </div>
    <div class="list-filter-container">
        <h4 class="list-filter-title">My Lists</h4>
        <form method="GET" action="home.php" style="display: flex; align-items: center; gap: 10px;">
            <select name="list_id" onchange="this.form.submit()">
                <option value="" <?php echo ($list_filter_id === null) ? 'selected' : ''; ?>>All Tasks</option>
                <option value="none" <?php echo ($list_filter_id === 'none') ? 'selected' : ''; ?>>Tasks (No List)</option>
                <?php
                $stmt_lists->data_seek(0);
                while($list = $lists_data->fetch_assoc()):
                    ?>
                    <option value="<?php echo $list['ListID']; ?>" <?php echo ($list_filter_id == $list['ListID']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($list['ListName']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </form>
    </div>
    <div class="task-header">
        <h3><?php echo $page_title; ?></h3>
        <?php if (!empty($list_filter_id) && $list_filter_id !== 'none'): ?>
            <div class="list-actions">
                <a href="edit_list.php?id=<?php echo htmlspecialchars($list_filter_id); ?>" class="action-icon icon-edit" title="Edit List">
                    <i class="fas fa-edit"></i>
                </a>
                <a href="delete_list.php?id=<?php echo htmlspecialchars($list_filter_id); ?>" class="action-icon icon-delete" title="Delete List" onclick="return confirm('Are you sure you want to delete this list and all its tasks?');">
                    <i class="fas fa-trash-alt"></i>
                </a>
            </div>
        <?php endif; ?>
    </div>
    <div class="task-list">
        <?php if ($tasks_result->num_rows == 0): ?>
            <?php if(!empty($search_query)): ?>
                <p>No tasks found matching your search.</p>
            <?php else: ?>
                <p>You have no tasks here. Click "Create New Task" to add one!</p>
            <?php endif; ?>
        <?php else: ?>
            <?php while($task = $tasks_result->fetch_assoc()):
                $completed_class = $task['IsCompleted'] ? ' is-completed' : '';
                $checked_attribute = $task['IsCompleted'] ? 'checked' : '';
                ?>
                <div class="task-item priority-<?php echo htmlspecialchars($task['Priority']); ?><?php echo $completed_class; ?>">
                    
                    <a href="toggle_complete.php?id=<?php echo $task['TaskID']; ?>" class="task-toggle" title="Mark as <?php echo $task['IsCompleted'] ? 'Pending' : 'Completed'; ?>">
                        <input type="checkbox" onclick="window.location.href='toggle_complete.php?id=<?php echo $task['TaskID']; ?>';" <?php echo $checked_attribute; ?>>
                    </a>

                    <a href="task_detail.php?id=<?php echo $task['TaskID']; ?>" class="task-title">
                        <?php echo htmlspecialchars($task['Title']); ?>
                    </a>
                    <div class="task-meta">
                        <?php if(!empty($task['ListName'])): ?>
                            <span class="task-list-badge" title="List: <?php echo htmlspecialchars($task['ListName']); ?>">
                                   <?php echo htmlspecialchars($task['ListName']); ?>
                               </span>
                        <?php endif; ?>
                        <?php if(!empty($task['DueDate'])): ?>
                            <span class="task-due-date">
                                   Due: <?php echo date("d/m/Y", strtotime($task['DueDate'])); ?>
                               </span>
                        <?php endif; ?>
                    </div>
                    <div class="task-actions">
                        <a href="edit_task.php?id=<?php echo $task['TaskID']; ?>" class="action-icon icon-edit" title="Edit Task">
                            <i class="fas fa-edit"></i> 
                        </a>
                        <a href="delete_task.php?id=<?php echo $task['TaskID']; ?>" class="action-icon icon-delete" title="Delete Task" onclick="return confirm('Are you sure you want to delete this task?');">
                            <i class="fas fa-trash-alt"></i> 
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>

<?php
$stmt_lists->close();
$stmt_tasks->close();
$conn->close();
?>