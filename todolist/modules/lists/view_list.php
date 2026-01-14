<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
global $conn;
include '../../config/db_connect.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) { header("Location: ../../auth/login.php"); exit; }

if (!isset($_GET['id'])) { header("Location: ../../home.php"); exit; }
$list_id = $_GET['id'];

$redirect_url = '../../home.php';
if (isset($_GET['redirect_url']) && !empty($_GET['redirect_url'])) {
    $redirect_url = $_GET['redirect_url'];
} elseif (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
    if (strpos($_SERVER['HTTP_REFERER'], 'view_list.php') === false) {
        $redirect_url = $_SERVER['HTTP_REFERER'];
    }
}

$current_url = "view_list.php?id=" . $list_id . "&redirect_url=" . urlencode($redirect_url);

$is_inbox = ($list_id === 'inbox');

// 1. LẤY THÔNG TIN LIST
if ($is_inbox) {
    $list_info = [
        'list_name' => 'Inbox',
        'color_code' => '#6c757d' // Màu xám mặc định
    ];
} else {
    if (!is_numeric($list_id)) { header("Location: ../../home.php"); exit; }
    $stmt_l = $conn->prepare("SELECT * FROM Lists WHERE list_id = ? AND user_id = ?");
    $stmt_l->bind_param("ii", $list_id, $user_id);
    $stmt_l->execute();
    $list_info = $stmt_l->get_result()->fetch_assoc();
    if (!$list_info) { header("Location: ../../home.php"); exit; }
}

// 2. LẤY TASKS (Logic Query khác nhau)
$sql = "SELECT t.*, 
               GROUP_CONCAT(CONCAT(tg.tag_name, '^', tg.color_code) SEPARATOR '|') as tag_data
        FROM Tasks t 
        LEFT JOIN TaskTags tt ON t.task_id = tt.task_id
        LEFT JOIN Tags tg ON tt.tag_id = tg.tag_id";

if ($is_inbox) {
    $sql .= " WHERE t.user_id = ? AND t.list_id IS NULL";
} else {
    $sql .= " WHERE t.user_id = ? AND t.list_id = ?";
}

$sql .= " GROUP BY t.task_id
          ORDER BY (t.status = 'completed') ASC, (t.status = 'canceled') ASC, t.is_important DESC, t.is_urgent DESC, t.due_date ASC";

$stmt = $conn->prepare($sql);

if ($is_inbox) {
    $stmt->bind_param("i", $user_id);
} else {
    $stmt->bind_param("ii", $user_id, $list_id);
}

$stmt->execute();
$tasks_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($list_info['list_name']); ?> - Todo Pro</title>
    <link rel="stylesheet" href="../../assets/css/style1.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    /* Sử dụng lại btn-back như trên */
    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        background-color: transparent;
        color: #6c757d;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    .btn-back:hover {
        transform: scale(1.1);
        background-color: #f8f9fa;
        border-color: #adb5bd;
    }
    .btn-add {
        background-color: #007bff !important;
        color: white !important;
        border: none;
        padding: 10px 15px;
        border-radius: 6px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        font-weight: 600;
    }
    .btn-add:hover {
        background-color: #0056b3 !important;
        transform: translateY(-5px);
        box-shadow: 0 4px 6px rgba(0, 123, 255, 0.2);
    }
</style>
</head>
<body>

<?php
$path_to_root = '../../';
include '../../includes/sidebar.php';
?>

<div class="main-content">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div style="color: #7f8c8d; font-size: 0.9em;">
            <i class="fas fa-home"></i> <a href="../../home.php" style="text-decoration: none; color: inherit;">Dashboard</a>
            <span style="margin: 0 5px;">&gt;</span>
            <?php echo $is_inbox ? 'System' : 'Lists'; ?>
            <span style="margin: 0 5px;">&gt;</span>
            <strong><?php echo htmlspecialchars($list_info['list_name']); ?></strong>
        </div>

        <a href="<?php echo htmlspecialchars($redirect_url); ?>" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; padding-bottom: 20px; margin-bottom: 30px;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <div style="width: 50px; height: 50px; background: <?php echo $list_info['color_code']; ?>; color: #fff; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; box-shadow: 0 5px 15px <?php echo $list_info['color_code']; ?>66;">
                <i class="fas <?php echo $is_inbox ? 'fa-inbox' : 'fa-folder'; ?>"></i>
            </div>
            <div>
                <h1 style="margin: 0; color: #2c3e50; font-size: 1.8rem;"><?php echo htmlspecialchars($list_info['list_name']); ?></h1>
                <div style="color: #7f8c8d; font-size: 0.9em; margin-top: 5px;">
                    <?php echo $tasks_result->num_rows; ?> tasks found
                </div>
            </div>
        </div>

        <div class="manager-actions">
            <a href="../tasks/create_task.php?<?php echo $is_inbox ? '' : 'list_id='.$list_id.'&'; ?>redirect_url=<?php echo urlencode($current_url); ?>"
               class="btn-add">
                <i class="fas fa-plus"></i>
            </a>

            <?php if (!$is_inbox): ?>
                <a href="edit_list.php?id=<?php echo $list_id; ?>&redirect_url=<?php echo urlencode($current_url); ?>" class="action-icon icon-edit" title="Edit List">
                    <i class="fas fa-pen"></i>
                </a>
                <a href="manage_lists.php?delete_id=<?php echo $list_id; ?>&redirect_url=<?php echo urlencode($redirect_url); ?>" class="action-icon icon-delete" title="Delete List" onclick="return confirm('Delete this list? Tasks will be moved to Inbox.');">
                    <i class="fas fa-trash"></i>
                </a>
            <?php endif; ?>
        </div>
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

                    <a href="../tasks/toggle_complete.php?id=<?php echo $task['task_id']; ?>&redirect_url=<?php echo urlencode($current_url); ?>"
                       class="task-toggle" style="text-decoration: none; margin-right: 20px">
                        <?php if ($is_completed): ?>
                            <i class="fas fa-check-square" style="color: #28a745; font-size: 24px;"></i>
                        <?php elseif ($is_doing): ?>
                            <i class="fas fa-spinner fa-spin" style="color: #007bff; font-size: 24px;"></i>
                        <?php elseif ($is_canceled): ?>
                            <i class="fas fa-ban" style="color: #dc3545; font-size: 24px; opacity: 0.5;"></i>
                        <?php else: ?>
                            <i class="far fa-square" style="color: #adb5bd; font-size: 24px;"></i>
                        <?php endif; ?>
                    </a>

                    <div style="flex-grow: 1;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <a href="../tasks/task_detail.php?id=<?php echo $task['task_id']; ?>&redirect_url=<?php echo urlencode($current_url); ?>"
                               class="task-title <?php echo $is_canceled ? 'status-canceled-text' : ''; ?>">
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
                                        echo "<span class='tag-pill' style='color: {$parts[1]}; border-color: {$parts[1]};'>";
                                        echo "<i class='fas fa-tag'></i> " . htmlspecialchars($parts[0]);
                                        echo "</span>";
                                    }
                                }
                            }
                            ?>
                        </div>

                        <div style="margin-top: 6px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <?php if ($task['is_important']): ?><span class="matrix-badge matrix-imp"><i class="fas fa-star"></i> Important</span><?php endif; ?>
                            <?php if ($task['is_urgent']): ?><span class="matrix-badge matrix-urg"><i class="fas fa-fire"></i> Urgent</span><?php endif; ?>

                            <?php if (!empty($task['due_date'])): ?>
                                <span style="font-size: 0.8em; color: #888; margin-left: auto;">
                                    <i class="far fa-clock"></i> <?php echo date("d/m H:i", strtotime($task['due_date'])); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="task-actions">
                        <a href="../tasks/edit_task.php?id=<?php echo $task['task_id']; ?>&redirect_url=<?php echo urlencode($current_url); ?>" class="action-icon icon-edit"><i class="fas fa-pen"></i></a>
                        <a href="../tasks/delete_task.php?id=<?php echo $task['task_id']; ?>&redirect_url=<?php echo urlencode($current_url); ?>" class="action-icon icon-delete" onclick="return confirm('Delete task?');"><i class="fas fa-trash"></i></a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="text-align: center; color: #888; margin-top: 50px;">
                <div style="width: 80px; height: 80px; background: #f8f9fa; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                    <i class="fas fa-check" style="font-size: 2rem; color: #dee2e6;"></i>
                </div>
                <h3 style="margin: 0; color: #333;">It's empty!</h3>
                <p>No tasks found in <?php echo htmlspecialchars($list_info['list_name']); ?>.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>