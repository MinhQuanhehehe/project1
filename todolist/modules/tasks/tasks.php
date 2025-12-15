<?php
// FILE: modules/tasks/index.php (hoặc modules/tasks/tasks.php)
if (session_status() === PHP_SESSION_NONE) { session_start(); }
global $conn;

// 1. CHỈNH SỬA ĐƯỜNG DẪN CONFIG (lùi ra 2 cấp thư mục)
include '../../config/db_connect.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    // 2. CHỈNH SỬA ĐƯỜNG DẪN REDIRECT
    header("Location: ../../auth/login.php");
    exit;
}

// --- GET DATA FOR FILTERS ---
// Tags (for filter dropdown)
$my_tags = [];
$res_t = $conn->query("SELECT tag_id, tag_name FROM Tags WHERE user_id = $user_id ORDER BY tag_name");
while ($row = $res_t->fetch_assoc()) $my_tags[] = $row;

// --- HANDLE FILTERS ---
$search_query = isset($_GET['search_query']) ? trim($_GET['search_query']) : '';
$filter_tag_id = $_GET['tag_id'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_matrix = $_GET['matrix_filter'] ?? '';
$filter_start_date = $_GET['start_date'] ?? '';
$filter_end_date = $_GET['end_date'] ?? '';

// --- BUILD DESCRIPTION TEXT ---
$filter_desc = [];
if (!empty($search_query)) $filter_desc[] = "Keyword: <strong>" . htmlspecialchars($search_query) . "</strong>";
if (!empty($filter_tag_id)) $filter_desc[] = "Filtered by Tag";

// Status Description
if (!empty($filter_status)) {
    if ($filter_status === 'overdue') {
        $filter_desc[] = "Status: <strong style='color:#dc3545;'>Overdue</strong>";
    } else {
        $label = ucfirst(str_replace('_', ' ', $filter_status));
        $filter_desc[] = "Status: <strong>$label</strong>";
    }
}

if (!empty($filter_matrix)) $filter_desc[] = "Priority: " . htmlspecialchars($filter_matrix);
if (!empty($filter_start_date) || !empty($filter_end_date)) $filter_desc[] = "Date Filtered";

$current_filter_text = empty($filter_desc) ? "All Tasks" : implode(" | ", $filter_desc);

// --- MAIN QUERY ---
$sql = "SELECT t.*, l.list_name, l.color_code,
               GROUP_CONCAT(CONCAT(tg.tag_name, '^', tg.color_code) SEPARATOR '|') as tag_data
        FROM Tasks t 
        LEFT JOIN Lists l ON t.list_id = l.list_id 
        LEFT JOIN TaskTags tt ON t.task_id = tt.task_id
        LEFT JOIN Tags tg ON tt.tag_id = tg.tag_id
        WHERE t.user_id = ?";

$params = [$user_id]; $types = "i";

// 1. Filter by Inbox
if (isset($_GET['list_id']) && $_GET['list_id'] === 'inbox') {
    $sql .= " AND t.list_id IS NULL";
}

// 2. Filter by Status
if (!empty($filter_status)) {
    if ($filter_status === 'overdue') {
        $sql .= " AND t.status != 'completed' AND t.status != 'canceled' AND t.due_date < NOW()";
    } else {
        $sql .= " AND t.status = ?";
        $params[] = $filter_status;
        $types .= "s";
    }
}

// 3. Filter by Tag
if (!empty($filter_tag_id)) {
    $sql .= " AND EXISTS (SELECT 1 FROM TaskTags sub_tt WHERE sub_tt.task_id = t.task_id AND sub_tt.tag_id = ?)";
    $params[] = $filter_tag_id; $types .= "i";
}

// 4. Search
if (!empty($search_query)) {
    $sql .= " AND (t.title LIKE ? OR t.description LIKE ?)";
    $like = "%$search_query%"; $params[] = $like; $params[] = $like; $types .= "ss";
}

// 5. Matrix
if (!empty($filter_matrix)) {
    switch ($filter_matrix) {
        case 'do_first': $sql .= " AND t.is_important = 1 AND t.is_urgent = 1"; break;
        case 'schedule': $sql .= " AND t.is_important = 1 AND t.is_urgent = 0"; break;
        case 'delegate': $sql .= " AND t.is_important = 0 AND t.is_urgent = 1"; break;
        case 'dont_do':  $sql .= " AND t.is_important = 0 AND t.is_urgent = 0"; break;
    }
}

// 6. Date Range
if (!empty($filter_start_date)) { $sql .= " AND DATE(t.due_date) >= ?"; $params[] = $filter_start_date; $types .= "s"; }
if (!empty($filter_end_date)) { $sql .= " AND DATE(t.due_date) <= ?"; $params[] = $filter_end_date; $types .= "s"; }

// Order By
$sql .= " GROUP BY t.task_id ORDER BY (t.status = 'completed') ASC, (t.status = 'canceled') ASC, t.is_important DESC, t.is_urgent DESC, t.due_date ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$tasks_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Tasks - Todo Pro</title>
    <link rel="stylesheet" href="../../assets/css/style1.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<?php
// 4. CẤU HÌNH SIDEBAR ĐỂ HIỂN THỊ ĐÚNG LINK
$path_to_root = '../../';
include '../../includes/sidebar.php';
?>

<div class="main-content">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h2 style="margin: 0; color: #2c3e50;"><i class="fas fa-tasks"></i> All Tasks</h2>
        <a href="../../home.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <div class="filter-wrapper">
        <form action="" method="GET">
            <div class="filter-row" style="margin-bottom: 15px;">
                <div class="filter-column" style="flex: 2;">
                    <input type="text" name="search_query" placeholder="Search tasks..." value="<?php echo htmlspecialchars($search_query); ?>" class="filter-field">
                </div>

                <div class="filter-column">
                    <select name="status" onchange="this.form.submit()" class="filter-field">
                        <option value="">-- All Statuses --</option>
                        <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="in_progress" <?php echo $filter_status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="canceled" <?php echo $filter_status === 'canceled' ? 'selected' : ''; ?>>Canceled</option>
                        <option value="overdue" <?php echo $filter_status === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                    </select>
                </div>

                <div class="filter-column">
                    <select name="tag_id" onchange="this.form.submit()" class="filter-field">
                        <option value="">-- All Tags --</option>
                        <?php foreach ($my_tags as $t) echo "<option value='{$t['tag_id']}' ".($filter_tag_id == $t['tag_id']?'selected':'').">".htmlspecialchars($t['tag_name'])."</option>"; ?>
                    </select>
                </div>
            </div>

            <div class="filter-row">
                <div class="filter-column">
                    <select name="matrix_filter" onchange="this.form.submit()" class="filter-field">
                        <option value="">-- All Priorities --</option>
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
                    <button type="submit" class="btn btn-secondary"><i class="fas fa-filter"></i></button>
                </div>
                <div class="filter-column" style="flex: 0;">
                    <a href="index.php" class="btn btn-secondary" title="Clear Filters"><i class="fas fa-times"></i></a>
                </div>
            </div>
        </form>
    </div>

    <div class="filter-status-bar" style="margin-bottom: 20px;">
        <div>Viewing: <?php echo $current_filter_text; ?></div>
    </div>

    <div class="task-list">
        <?php if ($tasks_result->num_rows > 0): ?>
            <?php while ($task = $tasks_result->fetch_assoc()):
                $is_completed = ($task['status'] === 'completed');
                $is_canceled = ($task['status'] === 'canceled');
                $is_doing = ($task['status'] === 'in_progress');
                $is_overdue = (!$is_completed && !$is_canceled && !empty($task['due_date']) && strtotime($task['due_date']) < time());
                $css_class = $is_completed ? 'completed' : ($is_canceled ? 'canceled-task' : '');
                ?>
                <div class="task-item <?php echo $css_class; ?>" style="<?php echo $is_doing ? 'border-left: 4px solid #007bff;' : ''; ?>">
                    <a href="toggle_complete.php?id=<?php echo $task['task_id']; ?>" class="task-toggle" style="text-decoration: none;">
                        <?php if ($is_completed): ?><i class="fas fa-check-square" style="color: #28a745; font-size: 24px;"></i>
                        <?php elseif ($is_doing): ?><i class="fas fa-spinner fa-spin" style="color: #007bff; font-size: 24px;"></i>
                        <?php elseif ($is_canceled): ?><i class="fas fa-ban" style="color: #dc3545; font-size: 24px; opacity: 0.5;"></i>
                        <?php else: ?><i class="far fa-square" style="color: #adb5bd; font-size: 24px;"></i>
                        <?php endif; ?>
                    </a>

                    <div style="flex-grow: 1;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <a href="task_detail.php?id=<?php echo $task['task_id']; ?>" class="task-title <?php echo $is_canceled ? 'status-canceled-text' : ''; ?>">
                                <?php echo htmlspecialchars($task['title']); ?>
                            </a>
                            <?php if ($is_doing): ?><span style="font-size: 0.7em; color: #007bff; background: #e7f1ff; padding: 1px 5px; border-radius: 4px; font-weight: bold;">DOING</span><?php endif; ?>
                            <?php if ($is_overdue): ?><span class="text-overdue" title="Overdue!"><i class="fas fa-exclamation-circle"></i></span><?php endif; ?>

                            <?php
                            if (!empty($task['tag_data'])) {
                                $tags_array = explode('|', $task['tag_data']);
                                foreach ($tags_array as $tag_str) {
                                    $parts = explode('^', $tag_str);
                                    if (count($parts) === 2) {
                                        echo "<span class='tag-pill' style='color: {$parts[1]}; border-color: {$parts[1]};'><i class='fas fa-tag'></i> ".htmlspecialchars($parts[0])."</span>";
                                    }
                                }
                            }
                            ?>
                        </div>
                        <div style="margin-top: 6px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <?php if ($task['is_important']): ?><span class="matrix-badge matrix-imp">Important</span><?php endif; ?>
                            <?php if ($task['is_urgent']): ?><span class="matrix-badge matrix-urg">Urgent</span><?php endif; ?>

                            <?php if (!empty($task['list_name'])): ?>
                                <a href="../lists/view_list.php?id=<?php echo $task['list_id']; ?>" class="badge-pill" style="background-color: <?php echo $task['color_code']; ?>;"><i class="fas fa-folder"></i> <?php echo htmlspecialchars($task['list_name']); ?></a>
                            <?php else: ?>
                                <span class="badge-pill" style="background-color: #95a5a6; color: white;">Inbox</span>
                            <?php endif; ?>

                            <?php if (!empty($task['due_date'])): ?>
                                <span style="font-size: 0.8em; color: #888; margin-left: auto;"><i class="far fa-clock"></i> <?php echo date("M d, H:i", strtotime($task['due_date'])); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="task-actions">
                        <a href="edit_task.php?id=<?php echo $task['task_id']; ?>" class="action-icon icon-edit"><i class="fas fa-pen"></i></a>
                        <a href="delete_task.php?id=<?php echo $task['task_id']; ?>" class="action-icon icon-delete" onclick="return confirm('Delete task?');"><i class="fas fa-trash"></i></a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="text-align: center; color: #888; margin-top: 50px;">
                <p>No tasks found.</p>
                <a href="create_task.php" class="btn">Create Task</a>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>