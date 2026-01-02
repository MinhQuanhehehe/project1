<?php
session_start();
global $conn;
include '../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../home.php");
    exit;
}

// Data Statistic
$stats = [];
$stats['users'] = $conn->query("SELECT COUNT(*) as total FROM Users")->fetch_assoc()['total'];
$stats['logs'] = $conn->query("SELECT COUNT(*) as total FROM ActivityLogs")->fetch_assoc()['total'];

// Data for chart
$chart_data = [];
$res_chart = $conn->query("SELECT status, COUNT(*) as count FROM Tasks GROUP BY status");
while($row = $res_chart->fetch_assoc()) {
    $chart_data[$row['status']] = $row['count'];
}

// Get total tasks
$total_system_tasks = array_sum($chart_data);

$json_chart_labels = json_encode(array_keys($chart_data));
$json_chart_values = json_encode(array_values($chart_data));

// Get Users
$users_result = $conn->query("SELECT * FROM Users ORDER BY user_id DESC LIMIT 5");

// Get Logs
$logs_result = $conn->query("SELECT l.*, u.username FROM ActivityLogs l LEFT JOIN Users u ON l.user_id = u.user_id ORDER BY l.created_at DESC LIMIT 10");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style1.css?v=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<?php
$path_to_root = '../';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div style="margin-bottom: 30px;">
        <h2 style="color: #2c3e50; margin: 0;"><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h2>
        <p style="color: #7f8c8d; margin-top: 5px;">Welcome back, <strong><?php echo $_SESSION['username']; ?></strong></p>
    </div>

    <div class="card-grid">
        <a href="manage_users.php" class="dash-card card-users">
            <div>
                <h2><?php echo $stats['users']; ?></h2>
                <p>Total Users</p>
            </div>
            <div class="dash-icon"><i class="fas fa-users"></i></div>
        </a>

        <a href="logs.php" class="dash-card card-logs">
            <div>
                <h2><?php echo $stats['logs']; ?></h2>
                <p>System Logs</p>
            </div>
            <div class="dash-icon"><i class="fas fa-file-alt"></i></div>
        </a>

        <div class="dash-card card-server">
            <div>
                <h2 style="font-size: 1.8rem;">PHP <?php echo phpversion(); ?></h2>
                <p>Server Info</p>
            </div>
            <div class="dash-icon"><i class="fas fa-server"></i></div>
        </div>
    </div>

    <div class="dashboard-row" style="margin-bottom: 30px;">
        <div class="dashboard-col">
            <h3 class="panel-title">System Tasks (<?php echo number_format($total_system_tasks); ?>)</h3>
            <div style="height: 250px; position: relative;">
                <canvas id="taskChart"></canvas>
            </div>
        </div>

        <div class="dashboard-col">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 class="panel-title" style="margin:0; border:none;">New Users</h3>
                <a href="manage_users.php" style="font-size: 0.9em; text-decoration: none; color: #3498db;">View All &rarr;</a>
            </div>
            <table class="table-clean">
                <thead>
                <tr><th>User</th><th>Role</th></tr>
                </thead>
                <tbody>
                <?php while($u = $users_result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($u['username']); ?></strong>
                            <div style="font-size: 0.8em; color: #888;"><?php echo htmlspecialchars($u['email'] ?? ''); ?></div>
                        </td>
                        <td>
                            <span class="role-badge <?php echo ($u['role']=='admin') ? 'badge-admin' : 'badge-user'; ?>">
                                <?php echo $u['role']; ?>
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="dashboard-col" style="width: 100%;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 class="panel-title" style="margin:0; border:none;">Recent Activity Logs</h3>
            <a href="logs.php" style="font-size: 0.9em; text-decoration: none; color: #3498db;">View All &rarr;</a>
        </div>

        <div style="overflow-x: auto;">
            <table class="table-clean">
                <thead>
                <tr>
                    <th>Time</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Target</th>
                    <th>Details</th>
                </tr>
                </thead>
                <tbody>
                <?php while($log = $logs_result->fetch_assoc()):
                    // Badge Logic
                    $bg = '#eee'; $color = '#333';
                    if($log['action_type'] == 'DELETE') { $bg = '#ffebee'; $color = '#c62828'; }
                    if($log['action_type'] == 'CREATE') { $bg = '#e8f5e9'; $color = '#2e7d32'; }
                    if($log['action_type'] == 'LOGIN')  { $bg = '#e3f2fd'; $color = '#1565c0'; }
                    ?>
                    <tr>
                        <td style="color: #999; font-size: 0.9em; white-space: nowrap;">
                            <?php echo date("M d, H:i", strtotime($log['created_at'])); ?>
                        </td>
                        <td style="font-weight: 600;"><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></td>
                        <td>
                            <span style="background: <?php echo $bg; ?>; color: <?php echo $color; ?>; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold;">
                                <?php echo $log['action_type']; ?>
                            </span>
                        </td>
                        <td style="color: #555;">
                            <?php echo htmlspecialchars($log['target_table']); ?>
                        </td>
                        <td style="color: #666; font-size: 0.95em;">
                            <?php echo htmlspecialchars($log['details']); ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
    const ctx = document.getElementById('taskChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?php echo $json_chart_labels; ?>,
            datasets: [{
                data: <?php echo $json_chart_values; ?>,
                backgroundColor: ['#f1c40f', '#3498db', '#2ecc71', '#e74c3c'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'right' } }
        }
    });
</script>

</body>
</html>
