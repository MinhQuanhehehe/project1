<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
global $conn;

$redirect_url = '../../home.php';

if (isset($_POST['redirect_url']) && !empty($_POST['redirect_url'])) {
    $redirect_url = $_POST['redirect_url'];
}
elseif (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
    if (strpos($_SERVER['HTTP_REFERER'], 'create_task.php') === false) {
        $redirect_url = $_SERVER['HTTP_REFERER'];
    }
}

// 1. CHỈNH SỬA ĐƯỜNG DẪN CONFIG
include '../../config/db_connect.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: ../../auth/login.php");
    exit;
}

// --- GET DATA FOR FILTERS ---
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
if (!empty($filter_tag_id)) {
    $tag_name_display = 'Unknown Tag';
    foreach ($my_tags as $mt) {
        if ($mt['tag_id'] == $filter_tag_id) {
            $tag_name_display = htmlspecialchars($mt['tag_name']);
            break;
        }
    }
    $filter_desc[] = "Tag: <strong>" . $tag_name_display . "</strong>";
}
if (!empty($filter_status)) {
    if ($filter_status === 'overdue') {
        $filter_desc[] = "Status: <strong style='color:#dc3545;'>Overdue</strong>";
    } else {
        $label = ucfirst(str_replace('_', ' ', $filter_status));
        $filter_desc[] = "Status: <strong>$label</strong>";
    }
}
if (!empty($filter_matrix)) {
    $matrix_labels = [
            'do_first' => '🔴 Do First',
            'schedule' => '🔵 Schedule',
            'delegate' => '🟡 Delegate',
            'dont_do'  => '⚪ Don\'t Do'
    ];
    $label = isset($matrix_labels[$filter_matrix]) ? $matrix_labels[$filter_matrix] : $filter_matrix;

    $filter_desc[] = "Priority: <strong>" . $label . "</strong>";
}

if (!empty($filter_start_date)) {
    $filter_desc[] = "From: <strong>" . date("d/m/Y", strtotime($filter_start_date)) . "</strong>";
}
if (!empty($filter_end_date)) {
    $filter_desc[] = "To: <strong>" . date("d/m/Y", strtotime($filter_end_date)) . "</strong>";
}
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

if (isset($_GET['list_id']) && $_GET['list_id'] === 'inbox') {
    $sql .= " AND t.list_id IS NULL";
}
if (!empty($filter_status)) {
    if ($filter_status === 'overdue') {
        $sql .= " AND t.status != 'completed' AND t.status != 'canceled' AND t.due_date < NOW()";
    } else {
        $sql .= " AND t.status = ?";
        $params[] = $filter_status;
        $types .= "s";
    }
}
if (!empty($filter_tag_id)) {
    $sql .= " AND EXISTS (SELECT 1 FROM TaskTags sub_tt WHERE sub_tt.task_id = t.task_id AND sub_tt.tag_id = ?)";
    $params[] = $filter_tag_id; $types .= "i";
}
if (!empty($search_query)) {
    $sql .= " AND (t.title LIKE ? OR t.description LIKE ?)";
    $like = "%$search_query%"; $params[] = $like; $params[] = $like; $types .= "ss";
}
if (!empty($filter_matrix)) {
    switch ($filter_matrix) {
        case 'do_first': $sql .= " AND t.is_important = 1 AND t.is_urgent = 1"; break;
        case 'schedule': $sql .= " AND t.is_important = 1 AND t.is_urgent = 0"; break;
        case 'delegate': $sql .= " AND t.is_important = 0 AND t.is_urgent = 1"; break;
        case 'dont_do':  $sql .= " AND t.is_important = 0 AND t.is_urgent = 0"; break;
    }
}
if (!empty($filter_start_date)) { $sql .= " AND DATE(t.due_date) >= ?"; $params[] = $filter_start_date; $types .= "s"; }
if (!empty($filter_end_date)) { $sql .= " AND DATE(t.due_date) <= ?"; $params[] = $filter_end_date; $types .= "s"; }

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
    
    <style>
        /* Tinh chỉnh giao diện Filter */
        .filter-wrapper {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .filter-row {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }
        .filter-field {
            height: 40px;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 0 10px;
            outline: none;
        }

        /* NÚT BACK TO DASHBOARD (Cập nhật mới) */
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background-color: transparent;
            color: #6c757d;
            border: 1px solid #dee2e6; /* Viền mỏng */
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease; /* Hiệu ứng mượt */
        }

        .btn-back:hover {
            transform: scale(1.1); /* Phóng to lên 10% */
            background-color: #f8f9fa;
            color: #343a40;
            border-color: #adb5bd;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        /* Nút Apply màu xanh */
        .btn-apply {
            background-color: #007bff !important;
            color: white !important;
            border: none;
            height: 40px;
            padding: 0 20px;
            border-radius: 5px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-apply:hover {
            background-color: #0056b3 !important;
            box-shadow: 0 4px 6px rgba(0, 123, 255, 0.2);
            transform: translateY(-5px);
        }

        /* Nút Clear màu đỏ */
        .btn-clear {
            background-color: #6c757d !important;
            color: white !important;
            border: none;
            height: 40px;
            padding: 0 15px;
            border-radius: 5px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: 0.3s;
        }
        .btn-clear:hover {
            background-color: #5a6268 !important;
            box-shadow: 0 4px 6px rgba(94, 105, 117, 0.2);
            transform: translateY(-5px);
        }
        .filter-field {
            height: 42px;
            border-radius: 6px;
            border: 2px solid #2962ffff;
            padding: 0 10px;
            width: 100%;
            box-sizing: border-box;
        }
    </style>
</head>
<body>

<?php
$path_to_root = '../../';
include '../../includes/sidebar.php';
?>

<div class="main-content">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h2 style="margin: 0; color: #2c3e50;"><i class="fas fa-tasks"></i> All Tasks</h2>
        <a href="<?php echo $redirect_url?>" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    <div class="filter-wrapper">
        <form action="" method="GET">
            <div class="filter-row" style="margin-bottom: 15px;">
                <input type="text" name="search_query" placeholder="Search tasks..." value="<?php echo htmlspecialchars($search_query); ?>" class="filter-field" style="flex: 2;">
                
                <select name="status" onchange="this.form.submit()" class="filter-field" style="flex: 1;">
                    <option value="">-- All Statuses --</option>
                    <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="in_progress" <?php echo $filter_status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="canceled" <?php echo $filter_status === 'canceled' ? 'selected' : ''; ?>>Canceled</option>
                    <option value="overdue" <?php echo $filter_status === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                </select>

                <select name="tag_id" onchange="this.form.submit()" class="filter-field" style="flex: 1;">
                    <option value="">-- All Tags --</option>
                    <?php foreach ($my_tags as $t) echo "<option value='{$t['tag_id']}' ".($filter_tag_id == $t['tag_id']?'selected':'').">".htmlspecialchars($t['tag_name'])."</option>"; ?>
                </select>
            </div>

            <div class="filter-row">
                <select name="matrix_filter" onchange="this.form.submit()" class="filter-field" style="flex: 1.2;">
                    <option value="">-- All Priorities --</option>
                    <option value="do_first" <?php echo $filter_matrix === 'do_first' ? 'selected' : ''; ?>>🔴 Do First</option>
                    <option value="schedule" <?php echo $filter_matrix === 'schedule' ? 'selected' : ''; ?>>🔵 Schedule</option>
                    <option value="delegate" <?php echo $filter_matrix === 'delegate' ? 'selected' : ''; ?>>🟡 Delegate</option>
                    <option value="dont_do" <?php echo $filter_matrix === 'dont_do' ? 'selected' : ''; ?>>⚪ Don't Do</option>
                </select>

                <div class="filter-date-group" style="display: flex; align-items: center; gap: 8px; flex: 1.5;">
                    <input type="date" name="start_date" value="<?php echo htmlspecialchars($filter_start_date); ?>" class="filter-field">
                    <span>to</span>
                    <input type="date" name="end_date" value="<?php echo htmlspecialchars($filter_end_date); ?>" class="filter-field">
                </div>

                <button type="submit" class="btn-apply">
                    <i class="fas fa-search"></i>
                </button>
                
                <a href="tasks.php" class="btn-clear" title="Clear Filters">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>

    <div class="filter-status-bar" style="margin-bottom: 20px;">
        <div><strong>Filtered by</strong> <?php echo $current_filter_text; ?></div>
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
                    <a href="toggle_complete.php?id=<?php echo $task['task_id']; ?>" class="task-toggle" style="text-decoration: none; margin-right: 20px">
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
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
