<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
global $conn;
include 'db_connect.php';

$user_id = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}
$username = $_SESSION['username'] ?? 'User';

// Filter Feature
// Query Lists
$my_lists = [];
$stmt_lists = $conn->prepare("SELECT list_id, list_name, color_code FROM Lists WHERE user_id = ? ORDER BY list_name");
$stmt_lists->bind_param("i", $user_id);
$stmt_lists->execute();
$res_l = $stmt_lists->get_result();
while ($row = $res_l->fetch_assoc()) $my_lists[] = $row;
$stmt_lists->close();

// Query Tags
$my_tags = [];
$stmt_tags = $conn->prepare("SELECT tag_id, tag_name, color_code FROM Tags WHERE user_id = ? ORDER BY tag_name");
$stmt_tags->bind_param("i", $user_id);
$stmt_tags->execute();
$res_t = $stmt_tags->get_result();
while ($row = $res_t->fetch_assoc()) $my_tags[] = $row;
$stmt_tags->close();

// Processing filter
$search_query = isset($_GET['search_query']) ? trim($_GET['search_query']) : '';
$filter_list_id = $_GET['list_id'] ?? '';
$filter_tag_id = $_GET['tag_id'] ?? '';
$filter_matrix = $_GET['matrix_filter'] ?? '';
$filter_start_date = $_GET['start_date'] ?? '';
$filter_end_date = $_GET['end_date'] ?? '';

// Filter title
$filter_desc = [];

if (!empty($search_query)) {
    $filter_desc[] = "Keyword: <strong>" . htmlspecialchars($search_query) . "</strong>";
}

if ($filter_list_id !== '') {
    if ($filter_list_id === 'inbox') {
        $filter_desc[] = "List: <span class='badge-pill' style='background-color: #6c757d'><i class='fas fa-inbox'></i> Inbox</span>";
    } else {
        foreach ($my_lists as $l) {
            if ($l['list_id'] == $filter_list_id) {
                $filter_desc[] = "List: <span class='badge-pill' style='background-color: " . htmlspecialchars($l['color_code']) . "'><i class='fas fa-folder-open'></i> " . htmlspecialchars($l['list_name']) . "</span>";
                break;
            }
        }
    }
}

if (!empty($filter_tag_id)) {
    foreach ($my_tags as $t) {
        if ($t['tag_id'] == $filter_tag_id) {
            $filter_desc[] = "Tag: <span class='tag-pill' style='color: " . htmlspecialchars($t['color_code']) . "; border-color: " . htmlspecialchars($t['color_code']) . "'><i class='fas fa-tag'></i> " . htmlspecialchars($t['tag_name']) . "</span>";
            break;
        }
    }
}

if (!empty($filter_matrix)) {
    $matrix_labels = ['do_first'=>'Do First', 'schedule'=>'Schedule', 'delegate'=>'Delegate', 'dont_do'=>'Don\'t Do'];
    if (isset($matrix_labels[$filter_matrix])) {
        $filter_desc[] = "Priority: <strong>" . $matrix_labels[$filter_matrix] . "</strong>";
    }
}

if (!empty($filter_start_date)) $filter_desc[] = "From: <strong>" . htmlspecialchars($filter_start_date) . "</strong>";
if (!empty($filter_end_date)) $filter_desc[] = "To: <strong>" . htmlspecialchars($filter_end_date) . "</strong>";

$current_filter_text = empty($filter_desc) ? "All Tasks" : implode(" <span style='color:#ccc; margin:0 5px'>|</span> ", $filter_desc);


// SQL code
// GROUP_CONCAT: Gom Tags thành chuỗi "Name^Color|Name^Color"
$sql = "SELECT t.*, 
               l.list_name, 
               l.color_code,
               GROUP_CONCAT(CONCAT(tg.tag_name, '^', tg.color_code) SEPARATOR '|') as tag_data
        FROM Tasks t 
        LEFT JOIN Lists l ON t.list_id = l.list_id 
        LEFT JOIN TaskTags tt ON t.task_id = tt.task_id
        LEFT JOIN Tags tg ON tt.tag_id = tg.tag_id
        WHERE t.user_id = ?";

$params = [$user_id];
$types = "i";

// Lọc List
if ($filter_list_id !== '') {
    if ($filter_list_id === 'inbox') $sql .= " AND t.list_id IS NULL";
    else {
        $sql .= " AND t.list_id = ?";
        $params[] = $filter_list_id;
        $types .= "i";
    }
}

// Lọc Tag (Quan trọng: Dùng EXISTS để không ảnh hưởng hiển thị các tag khác)
if (!empty($filter_tag_id)) {
    $sql .= " AND EXISTS (SELECT 1 FROM TaskTags sub_tt WHERE sub_tt.task_id = t.task_id AND sub_tt.tag_id = ?)";
    $params[] = $filter_tag_id;
    $types .= "i";
}

// Lọc Keyword
if (!empty($search_query)) {
    $sql .= " AND (t.title LIKE ? OR t.description LIKE ?)";
    $like = "%$search_query%";
    $params[] = $like;
    $params[] = $like;
    $types .= "ss";
}

// Lọc Matrix
if (!empty($filter_matrix)) {
    switch ($filter_matrix) {
        case 'do_first': $sql .= " AND t.is_important = 1 AND t.is_urgent = 1"; break;
        case 'schedule': $sql .= " AND t.is_important = 1 AND t.is_urgent = 0"; break;
        case 'delegate': $sql .= " AND t.is_important = 0 AND t.is_urgent = 1"; break;
        case 'dont_do':  $sql .= " AND t.is_important = 0 AND t.is_urgent = 0"; break;
    }
}

// Lọc Ngày
if (!empty($filter_start_date)) { $sql .= " AND DATE(t.due_date) >= ?"; $params[] = $filter_start_date; $types .= "s"; }
if (!empty($filter_end_date)) { $sql .= " AND DATE(t.due_date) <= ?"; $params[] = $filter_end_date; $types .= "s"; }

// Group & Order
$sql .= " GROUP BY t.task_id";
// Thứ tự: Chưa xong lên trước -> Quan trọng -> Khẩn cấp -> Ngày gần nhất
$sql .= " ORDER BY (t.status = 'completed') ASC, (t.status = 'canceled') ASC, t.is_important DESC, t.is_urgent DESC, t.due_date ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$tasks_result = $stmt->get_result();
?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard - Todo App Pro</title>
        <link rel="stylesheet" href="style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            .filter-status-bar { display: flex; align-items: center; justify-content: space-between; }
        </style>
    </head>
    <body>
    <div class="container">

        <div class="header">
            <h2>Hello, <?php echo htmlspecialchars($username); ?>!</h2>
            <a href="logout.php" class="btn btn-secondary" title="Logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <div class="task-controls">
            <a href="create_task.php" class="btn"><i class="fas fa-plus"></i> New Task</a>
            <a href="manage_lists.php" class="btn btn-secondary"><i class="fas fa-folder-plus"></i>Manage List</a>
            <a href="manage_tags.php" class="btn btn-secondary"><i class="fas fa-tags"></i>Manage Tags</a>
        </div>

        <div class="search-bar">
            <form action="home.php" method="GET" id="filterForm">
                <div class="filter-row" style="margin-bottom: 10px;">
                    <div class="filter-column" style="flex: 2;">
                        <input type="text" name="search_query" placeholder="Search tasks..." value="<?php echo htmlspecialchars($search_query); ?>" class="filter-field">
                    </div>
                    <div class="filter-column">
                        <select name="list_id" onchange="this.form.submit()" class="filter-field">
                            <option value="">All Lists</option>
                            <option value="inbox" <?php echo $filter_list_id === 'inbox' ? 'selected' : ''; ?>>Inbox</option>
                            <?php foreach ($my_lists as $l) echo "<option value='{$l['list_id']}' ".($filter_list_id == $l['list_id']?'selected':'').">".htmlspecialchars($l['list_name'])."</option>"; ?>
                        </select>
                    </div>
                    <div class="filter-column">
                        <select name="tag_id" onchange="this.form.submit()" class="filter-field">
                            <option value="">All Tags</option>
                            <?php foreach ($my_tags as $t) echo "<option value='{$t['tag_id']}' ".($filter_tag_id == $t['tag_id']?'selected':'').">".htmlspecialchars($t['tag_name'])."</option>"; ?>
                        </select>
                    </div>
                </div>

                <div class="filter-row">
                    <div class="filter-column">
                        <select name="matrix_filter" onchange="this.form.submit()" class="filter-field">
                            <option value="">All Priorities</option>
                            <option value="do_first" <?php echo $filter_matrix === 'do_first' ? 'selected' : ''; ?>>🔴 Do First</option>
                            <option value="schedule" <?php echo $filter_matrix === 'schedule' ? 'selected' : ''; ?>>🔵 Schedule</option>
                            <option value="delegate" <?php echo $filter_matrix === 'delegate' ? 'selected' : ''; ?>>🟡 Delegate</option>
                            <option value="dont_do" <?php echo $filter_matrix === 'dont_do' ? 'selected' : ''; ?>>⚪ Don't Do</option>
                        </select>
                    </div>
                    <div class="filter-column filter-date-group">
                        <input type="date" name="start_date" value="<?php echo htmlspecialchars($filter_start_date); ?>" class="filter-field">
                        <span>to</span>
                        <input type="date" name="end_date" value="<?php echo htmlspecialchars($filter_end_date); ?>" class="filter-field">
                    </div>
                    <div class="filter-column" style="flex: 0;">
                        <button type="submit" class="btn btn-secondary" title="Apply Filter"><i class="fas fa-filter"></i></button>
                    </div>
                    <div class="filter-column" style="flex: 0;">
                        <a href="home.php" class="btn btn-secondary" title="Clear Filters" style="background: #e2e6ea; color: #333;"><i class="fas fa-times"></i></a>
                    </div>
                </div>
            </form>
        </div>

        <div class="filter-status-bar">
            <div>
                <i class="fas fa-layer-group" style="color: #007bff; margin-right: 5px;"></i>
                Filtered by: <?php echo $current_filter_text; ?>
            </div>
            <?php if (!empty($filter_desc)): ?>
                <a href="home.php" style="color: #dc3545; font-size: 0.9em; font-weight: 600; text-decoration: none;">
                    <i class="fas fa-times"></i> Clear All
                </a>
            <?php endif; ?>
        </div>

        <div class="task-list">
            <?php if ($tasks_result->num_rows > 0): ?>
                <?php while ($task = $tasks_result->fetch_assoc()):
                    // Logic Status
                    $is_completed = ($task['status'] === 'completed');
                    $is_canceled = ($task['status'] === 'canceled');
                    $is_doing = ($task['status'] === 'in_progress');
                    $is_overdue = (!$is_completed && !$is_canceled && !empty($task['due_date']) && strtotime($task['due_date']) < time());

                    $css_class = $is_completed ? 'completed' : ($is_canceled ? 'canceled-task' : '');
                    ?>

                    <div class="task-item <?php echo $css_class; ?>" style="<?php echo $is_doing ? 'border-left: 4px solid #007bff;' : ''; ?>">

                        <a href="toggle_complete.php?id=<?php echo $task['task_id']; ?>" class="task-toggle" style="text-decoration: none;">
                            <?php if ($is_completed): ?>
                                <i class="fas fa-check-square" style="color: #28a745; font-size: 24px;" title="Done! Click to Re-open"></i>
                            <?php elseif ($is_doing): ?>
                                <i class="fas fa-spinner fa-spin" style="color: #007bff; font-size: 24px;" title="Working... Click to Complete"></i>
                            <?php elseif ($is_canceled): ?>
                                <i class="fas fa-ban" style="color: #dc3545; font-size: 24px; opacity: 0.5;" title="Canceled. Click to Restore"></i>
                            <?php else: ?>
                                <i class="far fa-square" style="color: #adb5bd; font-size: 24px;" title="Click to Start"></i>
                            <?php endif; ?>
                        </a>

                        <div style="flex-grow: 1;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <a href="task_detail.php?id=<?php echo $task['task_id']; ?>" class="task-title <?php echo $is_canceled ? 'status-canceled-text' : ''; ?>">
                                    <?php echo htmlspecialchars($task['title']); ?>
                                </a>
                                <?php if ($is_doing): ?>
                                    <span style="font-size: 0.7em; color: #007bff; background: #e7f1ff; padding: 1px 5px; border-radius: 4px; font-weight: bold;">DOING</span>
                                <?php endif; ?>
                                <?php if ($is_overdue): ?>
                                    <span class="text-overdue" title="Overdue!"><i class="fas fa-exclamation-circle"></i></span>
                                <?php endif; ?>
                                <?php
                                if (!empty($task['tag_data'])) {
                                    $tags_array = explode('|', $task['tag_data']);
                                    foreach ($tags_array as $tag_str) {
                                        $parts = explode('^', $tag_str);
                                        if (count($parts) === 2) {
                                            $t_name = $parts[0];
                                            $t_color = $parts[1];
                                            // Tag Pill Style
                                            echo "<span class='tag-pill' style='color: $t_color; border-color: $t_color;'>";
                                            echo "<i class='fas fa-tag'></i> " . htmlspecialchars($t_name);
                                            echo "</span>";
                                        }
                                    }
                                }
                                ?>
                            </div>

                            <div style="margin-top: 6px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">

                                <?php if ($task['is_important']): ?><span class="matrix-badge matrix-imp"><i class="fas fa-star"></i> Important</span><?php endif; ?>
                                <?php if ($task['is_urgent']): ?><span class="matrix-badge matrix-urg"><i class="fas fa-fire"></i> Urgent</span><?php endif; ?>

                                <?php if (!empty($task['list_name'])): ?>
                                    <a href="home.php?list_id=<?php echo $task['list_id']; ?>" class="badge-pill" style="background-color: <?php echo $task['color_code']; ?>;">
                                        <i class="fas fa-folder-open"></i> <?php echo htmlspecialchars($task['list_name']); ?>
                                    </a>
                                <?php endif; ?>

                                <?php if (!empty($task['due_date'])): ?>
                                    <span style="font-size: 0.8em; color: #888; margin-left: auto;">
                                    <i class="far fa-clock"></i> <?php echo date("d/m H:i", strtotime($task['due_date'])); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="task-actions">
                            <?php if (!$is_canceled && !$is_completed): ?>
                                <a href="cancel_task.php?id=<?php echo $task['task_id']; ?>" class="action-icon" style="background-color: #6c757d;" title="Cancel Task" onclick="return confirm('Cancel this task?');">
                                    <i class="fas fa-ban"></i>
                                </a>
                            <?php endif; ?>
                            <a href="edit_task.php?id=<?php echo $task['task_id']; ?>" class="action-icon icon-edit" title="Edit"><i class="fas fa-pen"></i></a>
                            <a href="delete_task.php?id=<?php echo $task['task_id']; ?>" class="action-icon icon-delete" title="Delete" onclick="return confirm('Delete this task?');"><i class="fas fa-trash"></i></a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align: center; color: #888; margin-top: 50px;">
                    <i class="fas fa-clipboard-check" style="font-size: 3rem; color: #dee2e6; margin-bottom: 15px;"></i>
                    <p>No tasks found matching your filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    </body>
    </html>
<?php
$conn->close();
?>