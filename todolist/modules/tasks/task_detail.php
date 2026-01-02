<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
global $conn;
include '../../config/db_connect.php';

$user_id = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;
if (!$user_id) {
    header("Location: ../../auth/login.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../../home.php");
    exit;
}
$task_id = $_GET['id'];

// Query main Task data
$sql = "SELECT t.*, l.list_name, l.color_code 
        FROM Tasks t 
        LEFT JOIN Lists l ON t.list_id = l.list_id 
        WHERE t.task_id = ? AND t.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $task_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: ../../home.php");
    exit;
}
$task = $result->fetch_assoc();

// Get Tags
$task_tags = [];
$sql_tags = "SELECT t.tag_name, t.color_code 
             FROM Tags t 
             JOIN TaskTags tt ON t.tag_id = tt.tag_id 
             WHERE tt.task_id = ?";
$stmt_tags = $conn->prepare($sql_tags);
$stmt_tags->bind_param("i", $task_id);
$stmt_tags->execute();
$result_tags = $stmt_tags->get_result();
while ($row = $result_tags->fetch_assoc()) {
    $task_tags[] = $row;
}
$stmt_tags->close();

// Query subtasks
$subtasks = []; $total_sub = 0; $completed_sub = 0;
$res_sub = $conn->query("SELECT * FROM SubTasks WHERE task_id = $task_id ORDER BY created_at ASC");
while ($sub = $res_sub->fetch_assoc()) {
    $subtasks[] = $sub;
    $total_sub++;
    if ($sub['is_completed']) $completed_sub++;
}
$progress_percent = ($total_sub > 0) ? round(($completed_sub / $total_sub) * 100) : 0;

$is_completed = ($task['status'] === 'completed');
$is_canceled = ($task['status'] === 'canceled');
$is_in_progress = ($task['status'] === 'in_progress');
$is_overdue = (!$is_completed && !$is_canceled && !empty($task['due_date']) && strtotime($task['due_date']) < time());

$list_name_display = !empty($task['list_name']) ? htmlspecialchars($task['list_name']) : 'Inbox';
$list_link = !empty($task['list_id']) ? "../lists/view_list.php?id=".$task['list_id'] : "../../tasks.php?list_id=inbox";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Task Detail - Todo Pro</title>
    <link rel="stylesheet" href="../../assets/css/style1.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Nút Dashboard (Back) - Viền mỏng, phóng to và bay lên */
        .btn-back-custom {
            display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px;
            background-color: transparent; color: #6c757d; border: 1px solid #dee2e6;
            border-radius: 6px; text-decoration: none; transition: all 0.3s ease;
            font-weight: 500;
        }
        .btn-back-custom:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            background-color: #f8f9fa;
            color: #333;
        }

        /* Container hành động */
        .task-detail-actions {
            display: flex; gap: 12px; align-items: center;
        }

        /* Style chung cho nút Action */
        .btn-act {
            padding: 10px 22px; border-radius: 8px; border: none;
            font-weight: 600; cursor: pointer; transition: all 0.3s ease;
            text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
        }

        .btn-act:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        /* 1. Nút Complete: Màu xanh dương */
        .btn-complete { background-color: #007bff !important; color: white !important; }
        .btn-complete:hover { background-color: #0056b3 !important; }

        /* 2. Nút Edit & Cancel: Viền đen (Outline) */
        .btn-outline-dark { 
            background-color: transparent !important; 
            color: #333 !important; 
            border: 1px solid #333 !important; 
        }
        .btn-outline-dark:hover { 
            background-color: #333 !important; 
            color: #fff !important; 
        }

        /* 3. Nút Delete: Màu đỏ */
        .btn-delete-red { background-color: #e74c3c !important; color: white !important; }
        .btn-delete-red:hover { background-color: #c0392b !important; }

        /* Sửa lỗi khung trắng Date Filter (Nếu trang này có hiển thị lại filter) */
        .filter-wrapper { background-color: #f8f9fa !important; border: none !important; }
        .filter-date-group { background: transparent !important; border: none !important; }
        .btn-add-subtask {
            background-color: #007bff !important;
            color: white !important;
            border: none !important;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-add-subtask:hover {
            transform: translateY(-5px);
            background-color: #0056b3 !important;
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
        }

        /* Đảm bảo ô input subtask cũng đồng bộ chiều cao */
        .subtask-input {
            height: 40px;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 0 15px;
            flex-grow: 1;
    </style>
</head>
<body>

<?php
$path_to_root = '../../';
include '../../includes/sidebar.php';
?>

<div class="main-content">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div style="color: #7f8c8d; font-size: 0.9em; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-home"></i> 
            <a href="../../home.php" style="text-decoration: none; color: inherit;">Dashboard</a>
            <span>&gt;</span>
            <a href="<?php echo $list_link; ?>" style="text-decoration: none; color: inherit;"><?php echo $list_name_display; ?></a>
            <span>&gt;</span>
            <strong>Task #<?php echo $task_id; ?></strong>
        </div>

        <a href="../../home.php" class="btn-back-custom">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <div class="task-detail-card">
        <div class="task-detail-header">
            <h2 class="<?php echo $is_canceled ? 'text-canceled' : ''; ?>" style="margin-top: 0;">
                <?php if($is_completed): ?>
                    <i class="fas fa-check-circle" style="color: #2ecc71;"></i>
                <?php elseif($is_in_progress): ?>
                    <i class="fas fa-spinner fa-spin" style="color: #3498db;"></i>
                <?php endif; ?>
                <?php echo htmlspecialchars($task['title']); ?>
            </h2>

            <div class="task-badges-row">
                <?php if ($is_overdue): ?>
                    <span class="badge-pill" style="background: #e74c3c;"><i class="fas fa-exclamation-circle"></i> OVERDUE</span>
                <?php endif; ?>
                <span class="badge-pill" style="background-color: <?php echo !empty($task['color_code']) ? $task['color_code'] : '#95a5a6'; ?>;">
                    <i class="fas fa-folder"></i> <?php echo $list_name_display; ?>
                </span>
                <?php if ($task['is_important']): ?><span class="matrix-badge matrix-imp"><i class="fas fa-star"></i> IMPORTANT</span><?php endif; ?>
                <?php if ($task['is_urgent']): ?><span class="matrix-badge matrix-urg"><i class="fas fa-fire"></i> URGENT</span><?php endif; ?>
            </div>

            <?php if (!empty($task_tags)): ?>
                <div class="task-tags-row">
                    <?php foreach ($task_tags as $tag): ?>
                        <span class="tag-pill" style="color: <?php echo $tag['color_code']; ?>; border-color: <?php echo $tag['color_code']; ?>;">
                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($tag['tag_name']); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="task-description">
            <?php echo !empty($task['description']) ? nl2br(htmlspecialchars($task['description'])) : "<em style='color:#ccc;'>No description provided.</em>"; ?>
        </div>

        <div class="subtask-section">
            <h3><i class="fas fa-tasks"></i> Checklist</h3>
            <?php if ($total_sub > 0): ?>
                <div class="progress-bar-container">
                    <div class="progress-bar-fill" style="width: <?php echo $progress_percent; ?>%;"></div>
                </div>
                <div class="progress-text"><?php echo $progress_percent; ?>% Completed</div>
            <?php endif; ?>

            <div class="subtask-list">
                <?php foreach ($subtasks as $st): $done = $st['is_completed']; ?>
                    <div class="subtask-item <?php echo $done ? 'done' : ''; ?>">
                        <a href="process_subtask.php?action=toggle&id=<?php echo $st['subtask_id']; ?>" class="check-box">
                            <i class="<?php echo $done ? 'fas fa-check-square' : 'far fa-square'; ?>"></i>
                        </a>
                        <span class="subtask-text"><?php echo htmlspecialchars($st['title']); ?></span>
                        <a href="process_subtask.php?action=delete&id=<?php echo $st['subtask_id']; ?>" class="delete-sub" onclick="return confirm('Delete this item?');">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (!$is_completed && !$is_canceled): ?>
                <form action="process_subtask.php" method="POST" class="subtask-form">
                    <input type="hidden" name="task_id" value="<?php echo $task_id; ?>">
                    <input type="hidden" name="add_subtask" value="1">
                    <input type="text" name="subtask_title" required placeholder="Add a checklist item..." class="subtask-input">
                    <button type="submit" class="btn-add-subtask"><i class="fas fa-plus"></i></button>
                </form>
            <?php endif; ?>
        </div>

        <div class="task-detail-footer" style="display: flex; justify-content: space-between; align-items: flex-end; border-top: 1px solid #eee; padding-top: 20px;">
            <div class="task-meta">
                <div style="margin-bottom: 5px;"><i class="far fa-calendar-alt"></i> Created: <?php echo date("M d, Y", strtotime($task['created_at'])); ?></div>
                <?php if ($task['due_date']): ?>
                    <div style="color: #c0392b;"><i class="far fa-clock"></i> Due: <strong><?php echo date("M d, H:i", strtotime($task['due_date'])); ?></strong></div>
                <?php endif; ?>
            </div>

            <div class="task-detail-actions">
                <a href="toggle_complete.php?id=<?php echo $task_id; ?>" class="btn-act btn-complete">
                    <?php if ($is_completed): ?><i class="fas fa-undo"></i> Re-open
                    <?php elseif ($is_canceled): ?><i class="fas fa-trash-restore"></i> Restore
                    <?php else: ?><i class="fas fa-check"></i> Complete
                    <?php endif; ?>
                </a>

                <a href="edit_task.php?id=<?php echo $task_id; ?>" class="btn-act btn-outline-dark">
                    <i class="fas fa-edit"></i> Edit
                </a>

                <?php if (!$is_completed && !$is_canceled): ?>
                    <a href="cancel_task.php?id=<?php echo $task_id; ?>" class="btn-act btn-outline-dark" onclick="return confirm('Cancel this task?');">
                        <i class="fas fa-ban"></i> Cancel
                    </a>
                <?php endif; ?>

                <a href="delete_task.php?id=<?php echo $task_id; ?>" class="btn-act btn-delete-red" onclick="return confirm('Delete this task permanently?');">
                    <i class="fas fa-trash-alt"></i> Delete
                </a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
