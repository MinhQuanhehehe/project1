<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
global $conn;
include 'config/db_connect.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) { header("Location: auth/login.php"); exit; }

//Filter due to current
$today_date = date("Y-m-d");

// --- 1. THỐNG KÊ ---
$sql_stats = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status != 'completed' AND status != 'canceled' AND due_date < NOW() THEN 1 ELSE 0 END) as overdue
    FROM Tasks WHERE user_id = $user_id";
$stats = $conn->query($sql_stats)->fetch_assoc();

// --- 2. BIỂU ĐỒ ---
$chart_data = [];
$res_chart = $conn->query("SELECT status, COUNT(*) as count FROM Tasks WHERE user_id = $user_id GROUP BY status");
while($row = $res_chart->fetch_assoc()) { $chart_data[$row['status']] = $row['count']; }
$json_labels = json_encode(array_keys($chart_data));
$json_values = json_encode(array_values($chart_data));

// --- 3. DO FIRST (URGENT & IMPORTANT) ---
$sql_urgent = "SELECT t.*, l.list_name, l.color_code,
               GROUP_CONCAT(CONCAT(tg.tag_name, '^', tg.color_code) SEPARATOR '|') as tag_data
               FROM Tasks t 
               LEFT JOIN Lists l ON t.list_id = l.list_id
               LEFT JOIN TaskTags tt ON t.task_id = tt.task_id
               LEFT JOIN Tags tg ON tt.tag_id = tg.tag_id
               WHERE t.user_id = $user_id 
               AND t.status != 'completed' AND t.status != 'canceled' 
               AND t.is_important = 1 AND t.is_urgent = 1
               GROUP BY t.task_id
               LIMIT 5";
$urgent_tasks = $conn->query($sql_urgent);

// --- 4. DUE TODAY ---
$sql_today = "SELECT t.*, l.list_name, l.color_code,
              GROUP_CONCAT(CONCAT(tg.tag_name, '^', tg.color_code) SEPARATOR '|') as tag_data
              FROM Tasks t 
              LEFT JOIN Lists l ON t.list_id = l.list_id
              LEFT JOIN TaskTags tt ON t.task_id = tt.task_id
              LEFT JOIN Tags tg ON tt.tag_id = tg.tag_id
              WHERE t.user_id = $user_id 
              AND t.status != 'completed' AND t.status != 'canceled' 
              AND DATE(t.due_date) = CURDATE()
              GROUP BY t.task_id
              ORDER BY t.due_date ASC
              LIMIT 5";
$today_tasks = $conn->query($sql_today);

$redirect_url_encoded = urlencode('../../home.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Todo Pro</title>
    <link rel="stylesheet" href="assets/css/style1.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* CSS Dashboard Cards */
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
        }
        .dash-card {
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            display: flex;
            justify-content: space-between;
            align-items: center;
            text-decoration: none;
            color: inherit;
            transition: transform 0.2s, box-shadow 0.2s;
            border-left: 5px solid transparent;
        }
        .dash-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.08);
        }
        .dash-card h2 { margin: 0; font-size: 2.2rem; color: #333; }
        .dash-card p { margin: 5px 0 0; color: #7f8c8d; font-size: 0.95rem; font-weight: 600; }
        .dash-icon { font-size: 2.5rem; opacity: 0.8; }

        /* Style cho Task Items */
        .task-row-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f1f1f1;
            gap: 15px;
        }
        .task-row-item:last-child { border-bottom: none; }

        .task-info-col {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .task-title-link {
            font-weight: 600;
            color: #333;
            text-decoration: none;
            font-size: 1rem;
        }
        .task-title-link:hover { color: #3498db; }

        .task-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85em;
            flex-wrap: wrap;
        }

        /* Nút View Filter ở Header */
        .btn-header-link {
            font-size: 0.9rem;
            color: #7f8c8d;
            text-decoration: none;
            border: 1px solid #eee;
            padding: 5px 10px;
            border-radius: 5px;
            transition: all 0.2s;
        }
        .btn-header-link:hover {
            background: #f8f9fa;
            color: #3498db;
            border-color: #3498db;
        }

        .fa-spin {
            animation: fa-spin 2s infinite linear;
        }
    </style>
</head>
<body>

<?php
$path_to_root = './';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div style="margin-bottom: 30px;">
        <h2 style="margin: 0; color: #2c3e50;">Hello, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
        <p style="color: #7f8c8d;">Here is your productivity overview.</p>
    </div>

    <div class="card-grid">
        <a href="modules/tasks/tasks.php" class="dash-card" style="border-left-color: #3498db;">
            <div><h2><?php echo $stats['total']; ?></h2><p>Total Tasks</p></div>
            <div class="dash-icon" style="color:#3498db;"><i class="fas fa-tasks"></i></div>
        </a>
        <a href="modules/tasks/tasks.php?status=completed" class="dash-card" style="border-left-color: #2ecc71;">
            <div><h2><?php echo $stats['completed']; ?></h2><p>Completed</p></div>
            <div class="dash-icon" style="color:#2ecc71;"><i class="fas fa-check-circle"></i></div>
        </a>
        <a href="modules/tasks/tasks.php?status=pending" class="dash-card" style="border-left-color: #f1c40f;">
            <div><h2><?php echo $stats['pending']; ?></h2><p>Pending</p></div>
            <div class="dash-icon" style="color:#f1c40f;"><i class="fas fa-clock"></i></div>
        </a>
        <a href="modules/tasks/tasks.php?status=overdue" class="dash-card" style="border-left-color: #e74c3c;">
            <div><h2><?php echo $stats['overdue']; ?></h2><p>Overdue</p></div>
            <div class="dash-icon" style="color:#e74c3c;"><i class="fas fa-exclamation-triangle"></i></div>
        </a>
    </div>

    <div class="dashboard-row" style="margin-top: 30px; display: flex; gap: 30px; flex-wrap: wrap;">

        <div class="dashboard-col" style="flex: 1; min-width: 300px; background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.03);">
            <h3 style="margin-top: 0; color: #2c3e50; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 15px;">Task Status</h3>
            <div style="height: 250px; position: relative;">
                <canvas id="statusChart"></canvas>
            </div>
        </div>

        <div class="dashboard-col" style="flex: 2; min-width: 400px; background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.03);">

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
                <h3 style="margin:0; color: #e74c3c;"><i class="fas fa-fire"></i> Do First</h3>
                <a href="modules/tasks/tasks.php?matrix_filter=do_first" class="btn-header-link">
                    View List <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <?php if ($urgent_tasks->num_rows > 0): ?>
                <div class="urgent-list">
                    <?php while($t = $urgent_tasks->fetch_assoc()):
                        $is_completed = ($t['status'] === 'completed');
                        $is_in_progress = ($t['status'] === 'in_progress');
                        ?>
                        <div class="task-row-item">
                            <a href="modules/tasks/toggle_complete.php?id=<?php echo $t['task_id']; ?>&redirect_url=<?php echo $redirect_url_encoded; ?>"
                               style="text-decoration: none; margin-right: 5px;">
                                <?php if ($is_completed): ?>
                                    <i class="fas fa-check-square" style="color: #28a745; font-size: 18px;"></i>
                                <?php elseif ($is_in_progress): ?>
                                    <i class="fas fa-spinner fa-spin" style="color: #007bff; font-size: 18px;"></i>
                                <?php else: ?>
                                    <i class="far fa-square" style="color: #adb5bd; font-size: 18px;"></i>
                                <?php endif; ?>
                            </a>
                            <div class="task-info-col">
                                <a href="modules/tasks/task_detail.php?id=<?php echo $t['task_id']; ?>&redirect_url=<?php echo $redirect_url_encoded; ?>" class="task-title-link">
                                    <?php echo htmlspecialchars($t['title']); ?>
                                </a>
                                <div class="task-meta">
                                    <?php if ($is_in_progress): ?>
                                        <span style="font-size: 0.7em; color: #007bff; background: #e7f1ff; padding: 1px 5px; border-radius: 4px; font-weight: bold;">DOING</span>
                                    <?php endif; ?>

                                    <?php if (!empty($t['list_name'])): ?>
                                        <span class="badge-pill" style="background-color: <?php echo $t['color_code']; ?>; font-size: 0.75em; padding: 2px 8px;">
                                            <i class="fas fa-folder"></i> <?php echo htmlspecialchars($t['list_name']); ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php
                                    if (!empty($t['tag_data'])) {
                                        $tags_array = explode('|', $t['tag_data']);
                                        foreach ($tags_array as $tag_str) {
                                            $parts = explode('^', $tag_str);
                                            if (count($parts) === 2) {
                                                echo "<span class='tag-pill' style='color: {$parts[1]}; border-color: {$parts[1]}; font-size: 0.75em; padding: 1px 6px;'>";
                                                echo "<i class='fas fa-tag'></i> " . htmlspecialchars($parts[0]);
                                                echo "</span>";
                                            }
                                        }
                                    }
                                    ?>
                                    <?php if (!empty($t['due_date'])): ?>
                                        <span style="color: #e74c3c; font-weight: bold; font-size: 0.85em;">
                                            <i class="far fa-clock"></i> <?php echo date("M d", strtotime($t['due_date'])); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 20px; color: #999;">
                    <i class="fas fa-check-circle" style="font-size: 1.5rem; color: #2ecc71; margin-bottom: 5px;"></i>
                    <p style="font-style: italic; margin: 0;">No urgent tasks right now.</p>
                </div>
            <?php endif; ?>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 30px; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
                <h3 style="margin:0; color: #3498db;"><i class="fas fa-calendar-day"></i> Due Today</h3>
                <a href="modules/tasks/tasks.php?start_date=<?php echo $today_date; ?>&end_date=<?php echo $today_date; ?>" class="btn-header-link">
                    View List <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <?php if ($today_tasks->num_rows > 0): ?>
                <div class="today-list">
                    <?php while($t = $today_tasks->fetch_assoc()):
                        $is_completed = ($t['status'] === 'completed');
                        $is_in_progress = ($t['status'] === 'in_progress');
                        ?>
                        <div class="task-row-item">
                            <a href="modules/tasks/toggle_complete.php?id=<?php echo $t['task_id']; ?>&redirect_url=<?php echo $redirect_url_encoded; ?>"
                               style="text-decoration: none; margin-right: 5px;">
                                <?php if ($is_completed): ?>
                                    <i class="fas fa-check-square" style="color: #28a745; font-size: 18px;"></i>
                                <?php elseif ($is_in_progress): ?>
                                    <i class="fas fa-spinner fa-spin" style="color: #007bff; font-size: 18px;"></i>
                                <?php else: ?>
                                    <i class="far fa-square" style="color: #adb5bd; font-size: 18px;"></i>
                                <?php endif; ?>
                            </a>
                            <div class="task-info-col">
                                <a href="modules/tasks/task_detail.php?id=<?php echo $t['task_id']; ?>&redirect_url=<?php echo $redirect_url_encoded; ?>" class="task-title-link">
                                    <?php echo htmlspecialchars($t['title']); ?>
                                </a>
                                <div class="task-meta">
                                    <?php if ($is_in_progress): ?>
                                        <span style="font-size: 0.7em; color: #007bff; background: #e7f1ff; padding: 1px 5px; border-radius: 4px; font-weight: bold;">DOING</span>
                                    <?php endif; ?>

                                    <?php if (!empty($t['list_name'])): ?>
                                        <span class="badge-pill" style="background-color: <?php echo $t['color_code']; ?>; font-size: 0.75em; padding: 2px 8px;">
                                            <i class="fas fa-folder"></i> <?php echo htmlspecialchars($t['list_name']); ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php
                                    if (!empty($t['tag_data'])) {
                                        $tags_array = explode('|', $t['tag_data']);
                                        foreach ($tags_array as $tag_str) {
                                            $parts = explode('^', $tag_str);
                                            if (count($parts) === 2) {
                                                echo "<span class='tag-pill' style='color: {$parts[1]}; border-color: {$parts[1]}; font-size: 0.75em; padding: 1px 6px;'>";
                                                echo "<i class='fas fa-tag'></i> " . htmlspecialchars($parts[0]);
                                                echo "</span>";
                                            }
                                        }
                                    }
                                    ?>

                                    <span style="color: #e74c3c; font-weight: bold; font-size: 0.85em;">
                                        <i class="far fa-clock"></i> <?php echo date("H:i", strtotime($t['due_date'])); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p style="color: #999; text-align: center; padding: 20px;">No tasks due today.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    const ctx = document.getElementById('statusChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?php echo $json_labels; ?>,
            datasets: [{
                data: <?php echo $json_values; ?>,
                backgroundColor: ['#f1c40f', '#3498db', '#2ecc71', '#e74c3c'],
                borderWidth: 0
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' } } }
    });
</script>
</body>
</html>