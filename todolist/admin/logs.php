<?php
session_start();
global $conn;
include '../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../home.php");
    exit;
}

// 1. Get Params
$filter_user = $_GET['user'] ?? '';
$filter_action = $_GET['action'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';

$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// 2. Build Query
$where_clauses = ["1=1"];
$params = [];
$types = "";

if (!empty($filter_user)) {
    $where_clauses[] = "u.username LIKE ?";
    $params[] = "%$filter_user%";
    $types .= "s";
}
if (!empty($filter_action)) {
    $where_clauses[] = "l.action_type = ?";
    $params[] = $filter_action;
    $types .= "s";
}
if (!empty($filter_date_from)) {
    $where_clauses[] = "DATE(l.created_at) >= ?";
    $params[] = $filter_date_from;
    $types .= "s";
}
if (!empty($filter_date_to)) {
    $where_clauses[] = "DATE(l.created_at) <= ?";
    $params[] = $filter_date_to;
    $types .= "s";
}

$where_sql = implode(" AND ", $where_clauses);

// Count Total
$sql_count = "SELECT COUNT(*) as total FROM ActivityLogs l LEFT JOIN Users u ON l.user_id = u.user_id WHERE $where_sql";
$stmt_count = $conn->prepare($sql_count);
if(!empty($params)) $stmt_count->bind_param($types, ...$params);
$stmt_count->execute();
$total_rows = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Get Data
$sql = "SELECT l.*, u.username FROM ActivityLogs l LEFT JOIN Users u ON l.user_id = u.user_id WHERE $where_sql ORDER BY l.created_at DESC LIMIT ?, ?";
$params[] = $start; $params[] = $limit; $types .= "ii";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$logs_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Logs</title>
    <link rel="stylesheet" href="../assets/css/style1.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<?php
$path_to_root = '../';
include '../includes/sidebar.php';
?>

<div class="main-content">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #e9ecef;">
        <h2 style="margin: 0; color: #2c3e50;"><i class="fas fa-history"></i> System Logs</h2>
        <div style="display: flex; gap: 10px;">
            <a href="admin_actions.php?action=clean_logs&redirect=logs.php" class="btn btn-secondary" style="background-color: #f1c40f; color: #333;" onclick="return confirm('Delete logs older than 30 days?')">
                <i class="fas fa-broom"></i> Clean Logs
            </a>
            <a href="admin.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Dashboard
            </a>
        </div>
    </div>

    <form action="logs.php" method="GET" class="filter-wrapper">
        <div class="filter-row">

            <div class="filter-column">
                <label style="font-weight: 600; display: block; margin-bottom: 5px;">Username</label>
                <input type="text" name="user" value="<?php echo htmlspecialchars($filter_user); ?>" placeholder="Enter username..." class="filter-field">
            </div>

            <div class="filter-column">
                <label style="font-weight: 600; display: block; margin-bottom: 5px;">Action Type</label>
                <select name="action" class="filter-field">
                    <option value="">-- All Actions --</option>
                    <option value="LOGIN" <?php echo $filter_action=='LOGIN'?'selected':''; ?>>Login</option>
                    <option value="CREATE" <?php echo $filter_action=='CREATE'?'selected':''; ?>>Create</option>
                    <option value="UPDATE" <?php echo $filter_action=='UPDATE'?'selected':''; ?>>Update</option>
                    <option value="DELETE" <?php echo $filter_action=='DELETE'?'selected':''; ?>>Delete</option>
                </select>
            </div>

            <div class="filter-column filter-date-group" style="flex: 2; align-items: flex-end;">
                <div style="width: 100%;">
                    <label style="font-weight: 600; display: block; margin-bottom: 5px;">Date From</label>
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($filter_date_from); ?>" class="filter-field">
                </div>
                <div style="padding-bottom: 10px; font-weight: bold; color: #888;">to</div>
                <div style="width: 100%;">
                    <label style="font-weight: 600; display: block; margin-bottom: 5px;">Date To</label>
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($filter_date_to); ?>" class="filter-field">
                </div>
            </div>

            <div class="filter-column" style="flex: 0; display: flex; align-items: flex-end;">
                <button type="submit" class="btn" style="height: 42px;"><i class="fas fa-filter"></i> Filter</button>
            </div>

            <?php if(!empty($filter_user) || !empty($filter_action) || !empty($filter_date_from)): ?>
                <div class="filter-column" style="flex: 0; display: flex; align-items: flex-end;">
                    <a href="logs.php" class="btn btn-secondary" style="height: 42px; display: inline-flex; align-items: center;">Clear</a>
                </div>
            <?php endif; ?>
        </div>
    </form>

    <div style="background: #fff; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); overflow: hidden;">
        <table class="table-clean" style="margin: 0;">
            <thead style="background: #f8f9fa;">
            <tr>
                <th style="padding: 12px;">Time</th>
                <th style="padding: 12px;">User</th>
                <th style="padding: 12px;">Action</th>
                <th style="padding: 12px;">Target</th>
                <th style="padding: 12px;">Details</th>
            </tr>
            </thead>
            <tbody>
            <?php if($logs_result->num_rows > 0): ?>
                <?php while($log = $logs_result->fetch_assoc()):
                    $bg = '#eee'; $color = '#333';
                    if($log['action_type'] == 'DELETE') { $bg = '#ffebee'; $color = '#c62828'; }
                    if($log['action_type'] == 'CREATE') { $bg = '#e8f5e9'; $color = '#2e7d32'; }
                    if($log['action_type'] == 'LOGIN')  { $bg = '#e3f2fd'; $color = '#1565c0'; }
                    ?>
                    <tr>
                        <td style="padding: 12px; color: #888; font-size: 0.9em; white-space: nowrap;">
                            <?php echo date("Y-m-d H:i", strtotime($log['created_at'])); ?>
                        </td>
                        <td style="padding: 12px; font-weight: bold;"><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></td>
                        <td style="padding: 12px;">
                            <span style="background: <?php echo $bg; ?>; color: <?php echo $color; ?>; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold;">
                                <?php echo $log['action_type']; ?>
                            </span>
                        </td>
                        <td style="padding: 12px; color: #555;">
                            <?php echo htmlspecialchars($log['target_table']); ?> <small>#<?php echo $log['target_id']; ?></small>
                        </td>
                        <td style="padding: 12px; color: #333; font-size: 0.95em;">
                            <?php echo htmlspecialchars($log['details']); ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 30px; color: #999;">No logs found matching your criteria.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if($total_pages > 1): ?>
        <div style="margin-top: 20px; text-align: center;">
            <?php for($i=1; $i<=$total_pages; $i++):
                // Build query params for pagination links
                $query_params = $_GET;
                $query_params['page'] = $i;
                $link = '?' . http_build_query($query_params);
                ?>
                <a href="<?php echo $link; ?>" class="btn btn-secondary" style="padding: 5px 12px; margin: 0 2px; opacity: <?php echo ($i==$page)?'1':'0.6'; ?>;">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>