<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
global $conn;
include '../../config/db_connect.php';

$user_id = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;
if (!$user_id) {
    // Go back 2 levels -> into auth
    header("Location: ../../auth/login.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Go back 2 levels -> home.php
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
$stmt->close();

// 4. Get Tags
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

// GET SUBTASKS
$subtasks = [];
$total_sub = 0;
$completed_sub = 0;

$stmt_sub = $conn->prepare("SELECT * FROM SubTasks WHERE task_id = ? ORDER BY created_at ASC");
$stmt_sub->bind_param("i", $task_id);
$stmt_sub->execute();
$res_sub = $stmt_sub->get_result();

while ($sub = $res_sub->fetch_assoc()) {
    $subtasks[] = $sub;
    $total_sub++;
    if ($sub['is_completed']) {
        $completed_sub++;
    }
}
$stmt_sub->close();
$conn->close();

// Calculate Progress %
$progress_percent = ($total_sub > 0) ? round(($completed_sub / $total_sub) * 100) : 0;

// Handle status display logic
$is_completed = ($task['status'] === 'completed');
$is_canceled = ($task['status'] === 'canceled');
$is_in_progress = ($task['status'] === 'in_progress');
$is_overdue = (!$is_completed && !$is_canceled && !empty($task['due_date']) && strtotime($task['due_date']) < time());
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Detail - Todo App Pro</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
    </style>
</head>
<body>
<div class="container">
    <div class="task-detail-card">

        <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 10px;">
            <h2 style="margin-bottom: 5px; flex-grow: 1; <?php echo $is_canceled ? 'text-decoration: line-through; color: #888;' : ''; ?>">
                <?php echo htmlspecialchars($task['title']); ?>
            </h2>
            <?php if ($is_overdue): ?>
                <div class="overdue-alert">
                    <i class="fas fa-exclamation-circle"></i> OVERDUE
                </div>
            <?php endif; ?>
            <?php if (!empty($task_tags)): ?>
                <div style="display: flex; gap: 5px; align-items: center; margin-top: 5px;">
                    <?php foreach ($task_tags as $tag): ?>
                        <span style="border: 1px solid <?php echo $tag['color_code']; ?>;
                                color: <?php echo $tag['color_code']; ?>;
                                padding: 2px 8px;
                                border-radius: 12px;
                                font-size: 0.85rem;
                                font-weight: 600;">
                            <?php
                            echo "<i class='fas fa-tag' style='margin-right: 5px;'></i>";
                            echo htmlspecialchars($tag['tag_name']);
                            ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div style="margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
            <?php if (!empty($task['list_name'])):
                $bg = $task['color_code'] ?? '#6c757d'; ?>
                <span class="badge-pill" style="background-color: <?php echo $bg; ?>;">
                    <i class="fas fa-folder-open"></i> <?php echo htmlspecialchars($task['list_name']); ?>
                </span>
            <?php else: ?>
                <span class="badge-pill" style="background-color: #e9ecef; color: #6c757d;">
                    <i class="fas fa-inbox"></i> Inbox
                </span>
            <?php endif; ?>

            <?php if ($task['is_important']): ?>
                <span class="matrix-badge matrix-imp"><i class="fas fa-star"></i> Important</span>
            <?php endif; ?>
            <?php if ($task['is_urgent']): ?>
                <span class="matrix-badge matrix-urg"><i class="fas fa-fire"></i> Urgent</span>
            <?php endif; ?>
        </div>

        <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #eee; min-height: 80px; color: #495057; line-height: 1.6;">
            <?php
            if (!empty($task['description'])) {
                echo nl2br(htmlspecialchars($task['description']));
            } else {
                echo "<em style='color:#aaa;'>No description provided.</em>";
            }
            ?>
        </div>

        <div class="subtask-section">
            <h3 style="margin-bottom: 15px; font-size: 1.2rem;"><i class="fas fa-tasks"></i> Checklist</h3>

            <?php if ($total_sub > 0): ?>
                <div class="progress-header">
                    <span>Progress</span>
                    <span><?php echo $progress_percent; ?>% (<?php echo $completed_sub . '/' . $total_sub; ?>)</span>
                </div>
                <div class="progress-bar-bg">
                    <div class="progress-bar-fill" style="width: <?php echo $progress_percent; ?>%;"></div>
                </div>
            <?php endif; ?>

            <div class="subtask-list">
                <?php if (count($subtasks) > 0): ?>
                    <?php foreach ($subtasks as $st):
                        $is_done = $st['is_completed'];

                        $text_style = ($is_done || $is_canceled) ? 'text-decoration: line-through; color: #adb5bd;' : '';

                        $row_style = $is_canceled ? 'opacity: 0.6;' : '';
                        ?>
                        <div class="subtask-item <?php echo $is_done ? 'subtask-done' : ''; ?>" style="<?php echo $row_style; ?>">

                            <?php if (!$is_completed && !$is_canceled): ?>
                                <a href="process_subtask.php?action=toggle&id=<?php echo $st['subtask_id']; ?>"
                                   class="subtask-check"
                                   title="Click to toggle status">
                                    <i class="<?php echo $is_done ? 'fas fa-check-square' : 'far fa-square'; ?>"></i>
                                </a>
                            <?php else: ?>
                                <span class="subtask-check" style="cursor: not-allowed; color: #adb5bd;">
                                    <?php
                                    // If Done show checkmark, If Canceled show square or ban icon depending on preference
                                    // Here keeping old logic: Done is check, not done (but canceled) is square
                                    echo $is_done ? '<i class="fas fa-check-square"></i>' : '<i class="far fa-square"></i>';
                                    ?>
                                </span>
                            <?php endif; ?>

                            <span class="subtask-title" style="<?php echo $text_style; ?>">
                                <?php echo htmlspecialchars($st['title']); ?>
                            </span>

                            <?php if (!$is_completed && !$is_canceled): ?>
                                <a href="process_subtask.php?action=delete&id=<?php echo $st['subtask_id']; ?>"
                                   class="subtask-delete"
                                   title="Delete this step"
                                   onclick="return confirm('Delete this step?');">
                                    <i class="fas fa-times"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="padding: 20px; text-align: center; color: #adb5bd; font-style: italic;">
                        No checklist items yet. Add one below!
                    </div>
                <?php endif; ?>
            </div>
            <?php if (!$is_completed && !$is_canceled): ?>
                <form action="process_subtask.php" method="POST" style="margin-top: 15px; display: flex; gap: 10px;">
                    <input type="hidden" name="task_id" value="<?php echo $task_id; ?>">
                    <input type="hidden" name="add_subtask" value="1">

                    <input type="text" name="subtask_title" required
                           placeholder="Add a new step..."
                           style="margin-bottom: 0; font-size: 0.9em;">

                    <button type="submit" class="btn btn-secondary" style="padding: 0 15px;">
                        <i class="fas fa-plus"></i>
                    </button>
                </form>
            <?php endif; ?>
        </div>
        <div class="status-box">
            <div>Status:</div>

            <?php if ($is_completed): ?>
                <div class="status-text st-completed"><i class="fas fa-check-circle"></i> Completed</div>
                <div style="font-size: 0.9em; color: #888; margin-left: 10px;">
                    (at <?php echo date("H:i, d/m/Y", strtotime($task['completed_at'])); ?>)
                </div>
            <?php elseif ($is_in_progress): ?>
                <div class="status-text st-doing"><i class="fas fa-spinner fa-spin"></i> In Progress</div>
            <?php elseif ($is_canceled): ?>
                <div class="status-text st-canceled"><i class="fas fa-ban"></i> Canceled</div>
            <?php else: ?>
                <div class="status-text st-pending"><i class="far fa-circle"></i> Pending</div>
            <?php endif; ?>

            <?php if (!empty($task['due_date'])): ?>
                <div style="margin-left: auto; color: #555;">
                    Due: <strong><?php echo date("F j, Y - H:i", strtotime($task['due_date'])); ?></strong>
                </div>
            <?php endif; ?>
        </div>

        <hr class="task-divider">

        <div class="button-group" style="display: flex; gap: 10px; flex-wrap: wrap;">

            <a href="toggle_complete.php?id=<?php echo $task['task_id']; ?>" class="btn" style="flex: 1; justify-content: center;">
                <?php if ($is_completed): ?>
                    <i class="fas fa-undo"></i> Re-open Task
                <?php elseif ($is_in_progress): ?>
                    <i class="fas fa-check"></i> Mark Completed
                <?php elseif ($is_canceled): ?>
                    <i class="fas fa-trash-restore"></i> Restore Task
                <?php else: ?>
                    <i class="fas fa-play"></i> Start Doing
                <?php endif; ?>
            </a>

            <a href="edit_task.php?id=<?php echo $task['task_id']; ?>" class="btn btn-secondary" title="Edit details">
                <i class="fas fa-edit"></i> Edit
            </a>

            <?php if (!$is_completed && !$is_canceled): ?>
                <a href="cancel_task.php?id=<?php echo $task['task_id']; ?>" class="btn btn-secondary"
                   style="background-color: #6c757d; color: white;"
                   title="Cancel Task" onclick="return confirm('Cancel this task?');">
                    <i class="fas fa-ban"></i> Cancel
                </a>
            <?php endif; ?>

            <a href="delete_task.php?id=<?php echo $task['task_id']; ?>" class="btn btn-danger"
               title="Delete Permanently" onclick="return confirm('Are you sure you want to delete this task?');">
                <i class="fas fa-trash-alt"></i> Delete
            </a>

            <a href="../../home.php" class="btn btn-secondary" style="background: #e9ecef; color: #333;" title="Back to Home">
                <i class="fas fa-arrow-left"></i>
            </a>
        </div>

    </div>
</div>
</body>
</html>