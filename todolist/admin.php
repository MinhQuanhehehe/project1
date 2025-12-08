<?php
session_start();
global $conn;
include 'db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: home.php");
    exit;
}

$stats = [];
$stats['users'] = $conn->query("SELECT COUNT(*) as total FROM Users")->fetch_assoc()['total'];
$stats['logs'] = $conn->query("SELECT COUNT(*) as total FROM ActivityLogs")->fetch_assoc()['total'];

$chart_data = [];
$res_chart = $conn->query("SELECT status, COUNT(*) as count FROM Tasks GROUP BY status");
while($row = $res_chart->fetch_assoc()) {
    $chart_data[$row['status']] = $row['count'];
}

$total_system_tasks = array_sum($chart_data);

$json_chart_labels = json_encode(array_keys($chart_data));
$json_chart_values = json_encode(array_values($chart_data));

$users_result = $conn->query("SELECT * FROM Users ORDER BY user_id DESC LIMIT 5");

$logs_result = $conn->query("SELECT l.*, u.username FROM ActivityLogs l LEFT JOIN Users u ON l.user_id = u.user_id ORDER BY l.created_at DESC LIMIT 10");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Todo App Pro</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        .admin-container {
            width: 95%; max-width: 1600px;
            margin: 30px auto;
            background: #fff; padding: 30px; border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .card-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; margin-bottom: 30px; }
        .dash-card { background: #f8f9fa; padding: 25px; border-radius: 10px; border: 1px solid #e9ecef; display: flex; justify-content: space-between; align-items: center; transition: transform 0.2s; }
        .dash-card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .dash-card h2 { font-size: 2.2rem; margin: 0; color: #333; }
        .dash-card p { color: #666; margin: 5px 0 0; font-size: 1.1em; }
        .dash-icon { font-size: 3rem; opacity: 0.8; }

        .dashboard-row { display: flex; gap: 30px; flex-wrap: wrap; margin-bottom: 30px; }
        .dashboard-col-chart { flex: 1; min-width: 350px; background: #fff; border: 1px solid #eee; border-radius: 10px; padding: 25px; }
        .dashboard-col-users { flex: 2; min-width: 500px; background: #fff; border: 1px solid #eee; border-radius: 10px; padding: 25px; }

        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: 600; color: #495057; }

        .role-badge { padding: 5px 10px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; text-transform: uppercase; }
        .badge-admin { background: #e8daef; color: #8e44ad; }
        .badge-user { background: #d1ecf1; color: #0c5460; }

        .badge-log { padding: 4px 10px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; display: inline-block; width: 90px; text-align: center; }
        .c-create   { background: #d4edda; color: #155724; }
        .c-update   { background: #cce5ff; color: #004085; }
        .c-delete   { background: #f8d7da; color: #721c24; }
        .c-login    { background: #fff3cd; color: #856404; }
        .c-register { background: #e8daef; color: #6f42c1; border: 1px solid #d2b4de; }

        .header-actions { display: flex; gap: 10px; }
    </style>
</head>
<body style="background-color: #f4f6f9;">

<div class="admin-container">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #f4f6f9; padding-bottom: 20px;">
        <div>
            <h2 style="color: #007bff; margin-bottom: 5px;"><i class="fas fa-shield-alt"></i> Admin Dashboard</h2>
            <span style="color: #888; font-size: 1rem;">Welcome back, <strong><?php echo $_SESSION['username']; ?></strong></span>
        </div>

        <div class="header-actions">
            <a href="home.php" class="btn btn-secondary"><i class="fas fa-home"></i> App Home</a>
            <a href="logout.php" class="btn btn-secondary"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="card-grid">
        <a href="manage_users.php" style="text-decoration: none; color: inherit;">
            <div class="dash-card" style="border-left: 5px solid #007bff; cursor: pointer;">
                <div><h2><?php echo $stats['users']; ?></h2><p>Total Users</p></div>
                <div class="dash-icon" style="color: #007bff;"><i class="fas fa-users"></i></div>
            </div>
        </a>

        <a href="logs.php" style="text-decoration: none; color: inherit;">
            <div class="dash-card" style="border-left: 5px solid #ffc107; cursor: pointer;">
                <div><h2><?php echo $stats['logs']; ?></h2><p>System Logs</p></div>
                <div class="dash-icon" style="color: #ffc107;"><i class="fas fa-file-alt"></i></div>
            </div>
        </a>

        <div class="dash-card" style="border-left: 5px solid #28a745;">
            <div><h4 style="margin:0; font-size: 1.8rem;">PHP <?php echo phpversion(); ?></h4><p>Server Info</p></div>
            <div class="dash-icon" style="color: #28a745;"><i class="fas fa-server"></i></div>
        </div>
    </div>

    <div class="dashboard-row">

        <div class="dashboard-col-chart">
            <h3 style="margin-top: 0; color: #333;">
                System Tasks: <strong><?php echo number_format($total_system_tasks); ?> Task(s)</strong>
            </h3>

            <div style="height: 300px; position: relative;">
                <canvas id="taskChart"></canvas>
            </div>
        </div>

        <div class="dashboard-col-users">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin-top: 0; color: #333;">Latest Users (5)</h3>
                <a href="manage_users.php" style="font-size: 0.9em; text-decoration: none; color: #007bff; font-weight: bold;">Manage Users &rarr;</a>
            </div>

            <div style="overflow-x: auto;">
                <table>
                    <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                    </tr>
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
    </div>

    <div class="dashboard-row">
        <div style="width: 100%; background: #fff; border: 1px solid #eee; border-radius: 10px; padding: 25px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin-top: 0; color: #333;">Recent Activity Logs</h3>
                <a href="logs.php" style="font-size: 0.9em; text-decoration: none; color: #007bff; font-weight: bold;">View All Logs &rarr;</a>
            </div>

            <div style="overflow-x: auto;">
                <table style="font-size: 0.95em;">
                    <thead>
                    <tr>
                        <th style="width: 15%;">Time</th>
                        <th style="width: 15%;">User</th>
                        <th style="width: 10%;">Action</th>
                        <th style="width: 15%;">Target</th>
                        <th>Details</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php while($log = $logs_result->fetch_assoc()):
                        $badge = 'c-update';
                        switch($log['action_type']) {
                            case 'CREATE':   $badge = 'c-create'; break;
                            case 'UPDATE':   $badge = 'c-update'; break;
                            case 'DELETE':   $badge = 'c-delete'; break;
                            case 'LOGIN':    $badge = 'c-login'; break;
                            case 'REGISTER': $badge = 'c-register'; break;
                        }
                        ?>
                        <tr>
                            <td style="color:#888; white-space:nowrap;"><?php echo date("d/m H:i", strtotime($log['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></td>
                            <td>
                                <span class="badge-log <?php echo $badge; ?>"><?php echo $log['action_type']; ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($log['target_table']); ?></td>
                            <td><?php echo htmlspecialchars($log['details']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
    const ctx = document.getElementById('taskChart').getContext('2d');
    const taskChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?php echo $json_chart_labels; ?>,
            datasets: [{
                data: <?php echo $json_chart_values; ?>,
                backgroundColor: ['#ffc107', '#17a2b8', '#28a745', '#dc3545'],
                borderWidth: 1
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