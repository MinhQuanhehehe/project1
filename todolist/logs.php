<?php
session_start();
global $conn;
include 'db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: home.php");
    exit;
}

$filter_user = $_GET['user'] ?? '';
$filter_action = $_GET['action'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';

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

$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

$sql_count = "SELECT COUNT(*) as total FROM ActivityLogs l LEFT JOIN Users u ON l.user_id = u.user_id WHERE $where_sql";
$stmt_count = $conn->prepare($sql_count);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_rows = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);
$stmt_count->close();

$sql = "SELECT l.*, u.username 
        FROM ActivityLogs l 
        LEFT JOIN Users u ON l.user_id = u.user_id 
        WHERE $where_sql 
        ORDER BY l.created_at DESC 
        LIMIT ?, ?";

$params[] = $start;
$params[] = $limit;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$logs_result = $stmt->get_result();

function get_query_url($new_page) {
    $query = $_GET;
    $query['page'] = $new_page;
    return http_build_query($query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Logs - Todo App Pro</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-container {
            width: 95%; max-width: 1600px; margin: 40px auto;
            background: #fff; padding: 30px; border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .filter-bar {
            background: #fff;
            padding: 20px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            display: flex;
            align-items: flex-end;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 25px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1;
            min-width: 180px;
        }

        .filter-group label {
            font-size: 0.9em;
            font-weight: 700;
            color: #555;
        }

        .filter-input {
            padding: 10px 12px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            width: 100%;
            font-size: 0.95em;
            color: #495057;
            height: 42px;
        }

        .filter-input:focus {
            border-color: #007bff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            padding-bottom: 0;
        }

        .btn-apply {
            height: 42px;
            padding: 0 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-apply:hover { background-color: #0056b3; }

        .btn-clear {
            height: 42px;
            padding: 0 20px;
            background-color: #e9ecef;
            color: #333;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            transition: background 0.2s;
        }
        .btn-clear:hover { background-color: #dde2e6; }

        .pagination { display: flex; justify-content: center; margin-top: 20px; gap: 5px; }
        .page-link { padding: 8px 12px; border: 1px solid #dee2e6; color: #007bff; text-decoration: none; border-radius: 4px; }
        .page-link.active { background: #007bff; color: #fff; border-color: #007bff; }
        .page-link:hover:not(.active) { background: #e9ecef; }
        .page-link.disabled { color: #ccc; pointer-events: none; }

        .badge-log { padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; width: 80px; text-align: center; display: inline-block; }
        .c-create   { background: #d4edda; color: #155724; }
        .c-update   { background: #cce5ff; color: #004085; }
        .c-delete   { background: #f8d7da; color: #721c24; }
        .c-login    { background: #fff3cd; color: #856404; }
        .c-register { background: #e8daef; color: #6f42c1; border: 1px solid #d2b4de; }
    </style>
</head>
<body style="background-color: #f4f6f9;">

<div class="admin-container">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #f4f6f9; padding-bottom: 15px;">
        <div>
            <h2 style="color: #ffc107; margin-bottom: 5px;"><i class="fas fa-history"></i> System Logs</h2>
            <span style="color: #888;">Total: <strong><?php echo number_format($total_rows); ?></strong> records found</span>
        </div>
        <div>
            <a href="admin_actions.php?action=clean_logs&redirect=logs.php"
               class="btn btn-secondary"
               style="background-color: #ffc107; color: #333;"
               onclick="return confirm('Delete logs older than 30 days?')">
                <i class="fas fa-broom"></i> Clean Logs
            </a>
            <a href="admin.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Dashboard</a>
        </div>
    </div>

    <form action="logs.php" method="GET" class="filter-bar">
        <div class="filter-group">
            <label>Username</label>
            <input type="text" name="user" value="<?php echo htmlspecialchars($filter_user); ?>" placeholder="Enter username..." class="filter-input">
        </div>

        <div class="filter-group">
            <label>Action Type</label>
            <select name="action" class="filter-input">
                <option value="">-- All Actions --</option>
                <option value="LOGIN"    <?php echo $filter_action=='LOGIN'?'selected':''; ?>>Login</option>
                <option value="REGISTER" <?php echo $filter_action=='REGISTER'?'selected':''; ?>>Register</option>
                <option value="CREATE"   <?php echo $filter_action=='CREATE'?'selected':''; ?>>Create</option>
                <option value="UPDATE"   <?php echo $filter_action=='UPDATE'?'selected':''; ?>>Update</option>
                <option value="DELETE"   <?php echo $filter_action=='DELETE'?'selected':''; ?>>Delete</option>
            </select>
        </div>

        <div class="filter-group">
            <label>From Date</label>
            <input type="date" name="date_from" value="<?php echo htmlspecialchars($filter_date_from); ?>" class="filter-input">
        </div>

        <div class="filter-group">
            <label>To Date</label>
            <input type="date" name="date_to" value="<?php echo htmlspecialchars($filter_date_to); ?>" class="filter-input">
        </div>

        <div class="filter-actions">
            <button type="submit" class="btn-apply"><i class="fas fa-filter"></i> Apply</button>
            <?php if(!empty($filter_user) || !empty($filter_action) || !empty($filter_date_from) || !empty($filter_date_to)): ?>
                <a href="logs.php" class="btn-clear">Clear</a>
            <?php else: ?>
                <a href="logs.php" class="btn-clear" style="pointer-events: none; opacity: 0.6;">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <div style="overflow-x: auto; min-height: 400px;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
            <tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                <th style="padding: 12px; width: 150px;">Time</th>
                <th style="padding: 12px; width: 150px;">User</th>
                <th style="padding: 12px; width: 100px;">Action</th>
                <th style="padding: 12px; width: 120px;">Target</th>
                <th style="padding: 12px;">Details</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($logs_result->num_rows > 0): ?>
                <?php while($log = $logs_result->fetch_assoc()):
                    $badge = '';
                    switch($log['action_type']) {
                        case 'CREATE':   $badge = 'c-create'; break;
                        case 'UPDATE':   $badge = 'c-update'; break;
                        case 'DELETE':   $badge = 'c-delete'; break;
                        case 'LOGIN':    $badge = 'c-login'; break;
                        case 'REGISTER': $badge = 'c-register'; break;
                        default:         $badge = 'c-update';
                    }
                    ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 10px; color: #666; font-size: 0.9em;">
                            <?php echo date("Y-m-d H:i:s", strtotime($log['created_at'])); ?>
                        </td>
                        <td style="padding: 10px; font-weight: 600;">
                            <?php echo htmlspecialchars($log['username'] ?? 'System'); ?>
                        </td>
                        <td style="padding: 10px;">
                            <span class="badge-log <?php echo $badge; ?>"><?php echo $log['action_type']; ?></span>
                        </td>
                        <td style="padding: 10px; color: #555;">
                            <?php echo htmlspecialchars($log['target_table']); ?>
                            <small style="color: #999;">#<?php echo $log['target_id']; ?></small>
                        </td>
                        <td style="padding: 10px; color: #333;">
                            <?php echo htmlspecialchars($log['details']); ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" style="padding: 30px; text-align: center; color: #888;">No logs found matching your filters.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <a href="?<?php echo get_query_url($page - 1); ?>" class="page-link <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                &laquo; Prev
            </a>
            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?<?php echo get_query_url($i); ?>" class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            <a href="?<?php echo get_query_url($page + 1); ?>" class="page-link <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                Next &raquo;
            </a>
        </div>
    <?php endif; ?>

</div>

</body>
</html>