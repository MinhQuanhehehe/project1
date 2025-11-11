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
$filter_start_date = isset($_GET['filter_start_date']) ? trim($_GET['filter_start_date']) : '';
$filter_end_date = isset($_GET['filter_end_date']) ? trim($_GET['filter_end_date']) : '';
$priority_filter = isset($_GET['priority_filter']) ? trim($_GET['priority_filter']) : '';

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


$date_filter_title_parts = [];
if (!empty($filter_start_date)) {
    $where_clauses .= " AND t.DueDate >= ?";
    $params[] = $filter_start_date;
    $types .= "s";
    $date_filter_title_parts[] = "From: " . htmlspecialchars(date("d/m/Y", strtotime($filter_start_date)));
}
if (!empty($filter_end_date)) {
    $where_clauses .= " AND t.DueDate <= ?";
    $params[] = $filter_end_date;
    $types .= "s";
    $date_filter_title_parts[] = "To: " . htmlspecialchars(date("d/m/Y", strtotime($filter_end_date)));
}
if ($page_title != "Search Results" && !empty($date_filter_title_parts)) {
     $page_title = "Tasks (" . implode(', ', $date_filter_title_parts) . ")";
}


if (!empty($priority_filter)) {
    $where_clauses .= " AND t.Priority = ?";
    $params[] = $priority_filter;
    $types .= "s";
    
    if ($page_title == "Search Results"){
         $page_title .= " (" . htmlspecialchars($priority_filter) . ")";
    } else if (!empty($date_filter_title_parts)){
         $page_title .= " (" . htmlspecialchars($priority_filter) . ")";
    }
    else {
        $page_title = htmlspecialchars($priority_filter) . " Priority Tasks";
    }
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
    <link rel="stylesheet" href="search-filter.css"> 
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

    <form id="searchForm" action="home.php" method="GET">
        
        <?php if ($list_filter_id !== null): ?>
            <input type="hidden" name="list_id" value="<?php echo htmlspecialchars($list_filter_id); ?>">
        <?php endif; ?>
        <div>
        <div class="filter-row">
            
            <div class="filter-column">
                <label for="search_query" class="filter-label">Search</label>
                <input type="text" id="search_query" name="search_query" placeholder="Search tasks..." 
                       value="<?php echo htmlspecialchars($search_query ?? ''); ?>" 
                       class="filter-field filter-input-search">
            </div>

            <div class="filter-column">
                <label for="filter_start_date" class="filter-label">From Date</label>
                <input type="date" id="filter_start_date" name="filter_start_date" 
                       value="<?php echo htmlspecialchars($filter_start_date ?? ''); ?>" 
                       class="filter-field">
            </div>
        </div>

        <div class="filter-row">
            
            <div class="filter-column">
                <label for="priority_filter" class="filter-label">Priority</label>
                <select id="priority_filter" name="priority_filter" 
                        onchange="this.form.submit()"
                        class="filter-field">
                    <option value="" <?php echo ($priority_filter == '') ? 'selected' : ''; ?>>All Priorities</option>
                    <option value="Low" <?php echo ($priority_filter == 'Low') ? 'selected' : ''; ?>>Low</option>
                    <option value="Medium" <?php echo ($priority_filter == 'Medium') ? 'selected' : ''; ?>>Medium</option>
                    <option value="High" <?php echo ($priority_filter == 'High') ? 'selected' : ''; ?>>High</option>
                </select>
            </div>

            <div class="filter-column">
                <label for="filter_end_date" class="filter-label">To Date</label>
                <input type="date" id="filter_end_date" name="filter_end_date" 
                       value="<?php echo htmlspecialchars($filter_end_date ?? ''); ?>" 
                       class="filter-field">
            </div>

        </div>
        </div>
        <div class="">
            <button type="submit" class="btn search-btn">
                Search
            </button>
        </div>
    </form>

</div> 
<div class="list-filter-container">
        <h4 class="list-filter-title">My Lists</h4>
        <form method="GET" action="home.php">
            <select name="list_id" onchange="this.form.submit()">
                <option value="" <?php echo ($list_filter_id === null) ? 'selected' : ''; ?>>All Tasks</option>
                <option value="none" <?php echo ($list_filter_id === 'none') ? 'selected' : ''; ?>>Tasks (No List)</option>
                <?php
                // Tái thiết lập và thực thi statement cho lists
                $stmt_lists->data_seek(0);
                while($list = $lists_data->fetch_assoc()):
                    ?>
                    <option value="<?php echo $list['ListID']; ?>" <?php echo ($list_filter_id == $list['ListID']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($list['ListName']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            
            <?php if (!empty($search_query)): ?>
                <input type="hidden" name="search_query" value="<?php echo htmlspecialchars($search_query); ?>">
            <?php endif; ?>
            <?php if (!empty($filter_start_date)): ?>
                <input type="hidden" name="filter_start_date" value="<?php echo htmlspecialchars($filter_start_date); ?>">
            <?php endif; ?>
            <?php if (!empty($filter_end_date)): ?>
                <input type="hidden" name="filter_end_date" value="<?php echo htmlspecialchars($filter_end_date); ?>">
            <?php endif; ?>
            <?php if (!empty($priority_filter)): ?>
                <input type="hidden" name="priority_filter" value="<?php echo htmlspecialchars($priority_filter); ?>">
            <?php endif; ?>
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
            
            <?php if(!empty($search_query) || !empty($filter_start_date) || !empty($filter_end_date) || !empty($priority_filter)): ?>
                <p>No tasks found matching your filters.</p>
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
