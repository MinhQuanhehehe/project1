<?php
session_start();
global $conn;
include 'db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: home.php");
    exit;
}

$search = $_GET['search'] ?? '';
$filter_role = $_GET['role'] ?? '';

$where_clauses = ["1=1"];
$params = [];
$types = "";

if (!empty($search)) {
    $where_clauses[] = "(username LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

if (!empty($filter_role)) {
    $where_clauses[] = "role = ?";
    $params[] = $filter_role;
    $types .= "s";
}

$where_sql = implode(" AND ", $where_clauses);
$sql = "SELECT * FROM Users WHERE $where_sql ORDER BY user_id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users_result = $stmt->get_result();
$total_users = $users_result->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users - Todo App Pro</title>
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
            align-items: flex-end; /* Căn đáy */
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
            min-width: 200px;
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

        .role-select {
            padding: 5px; border-radius: 4px; border: 1px solid #ced4da;
            font-size: 0.85rem; font-weight: bold; cursor: pointer;
        }
        .role-admin { background: #e8daef; color: #8e44ad; border-color: #d2b4de; }
        .role-user { background: #d1ecf1; color: #0c5460; border-color: #bee5eb; }

        .badge-pill { padding: 5px 10px; border-radius: 12px; font-size: 0.8em; font-weight: bold; }
    </style>
</head>
<body style="background-color: #f4f6f9;">

<div class="admin-container">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #f4f6f9; padding-bottom: 15px;">
        <div>
            <h2 style="color: #007bff; margin-bottom: 5px;"><i class="fas fa-users-cog"></i> Manage Users</h2>
            <span style="color: #888;">Found: <strong><?php echo $total_users; ?></strong> users</span>
        </div>
        <a href="admin.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Dashboard</a>
    </div>

    <form action="manage_users.php" method="GET" class="filter-bar">

        <div class="filter-group" style="flex: 2;">
            <label>Search (Username / Email)</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                   placeholder="Enter username or email..." class="filter-input">
        </div>

        <div class="filter-group">
            <label>Filter by Role</label>
            <select name="role" class="filter-input">
                <option value="">-- All Roles --</option>
                <option value="user" <?php echo $filter_role==='user'?'selected':''; ?>>User</option>
                <option value="admin" <?php echo $filter_role==='admin'?'selected':''; ?>>Admin</option>
            </select>
        </div>

        <div class="filter-actions">
            <button type="submit" class="btn-apply"><i class="fas fa-search"></i> Search</button>

            <?php if(!empty($search) || !empty($filter_role)): ?>
                <a href="manage_users.php" class="btn-clear">Clear</a>
            <?php else: ?>
                <a href="manage_users.php" class="btn-clear" style="pointer-events: none; opacity: 0.6;">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <div style="overflow-x: auto; min-height: 400px;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
            <tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                <th style="padding: 15px;">ID</th>
                <th style="padding: 15px;">User Info</th>
                <th style="padding: 15px;">Role (Click to Change)</th>
                <th style="padding: 15px;">Joined Date</th>
                <th style="padding: 15px; text-align: right;">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($users_result->num_rows > 0): ?>
                <?php while($u = $users_result->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 15px; color: #888;">#<?php echo $u['user_id']; ?></td>

                        <td style="padding: 15px;">
                            <div style="font-weight: bold; font-size: 1.05em; color: #333;"><?php echo htmlspecialchars($u['username']); ?></div>
                            <div style="font-size: 0.9em; color: #6c757d;"><?php echo htmlspecialchars($u['email'] ?? 'No Email'); ?></div>
                        </td>

                        <td style="padding: 15px;">
                            <?php if ($u['user_id'] != $_SESSION['user_id']): ?>
                                <form action="admin_actions.php" method="POST" style="margin: 0;">
                                    <input type="hidden" name="action" value="update_role">
                                    <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                                    <input type="hidden" name="redirect" value="manage_users.php"> <select name="role" onchange="this.form.submit()"
                                                                                                           class="role-select <?php echo ($u['role']=='admin')?'role-admin':'role-user'; ?>">
                                        <option value="user" <?php echo ($u['role']=='user')?'selected':''; ?>>User</option>
                                        <option value="admin" <?php echo ($u['role']=='admin')?'selected':''; ?>>Admin</option>
                                    </select>
                                </form>
                            <?php else: ?>
                                <span class="badge-pill role-admin" style="background: #e8daef; color: #8e44ad; padding: 6px 12px; cursor: default;">YOU (ADMIN)</span>
                            <?php endif; ?>
                        </td>

                        <td style="padding: 15px; color: #555;"><?php echo date("d/m/Y", strtotime($u['created_at'])); ?></td>

                        <td style="padding: 15px; text-align: right;">
                            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                                <a href="admin_actions.php?action=reset_pass&id=<?php echo $u['user_id']; ?>&redirect=manage_users.php"
                                   class="btn-icon" style="background: #e2e6ea; color: #333; width: 36px; height: 36px; display: inline-flex; justify-content: center; align-items: center; border-radius: 6px;"
                                   title="Reset Pass to '123456'"
                                   onclick="return confirm('Reset password for <?php echo $u['username']; ?>?');">
                                    <i class="fas fa-key"></i>
                                </a>

                                <?php if($u['user_id'] != $_SESSION['user_id']): ?>
                                    <a href="admin_actions.php?action=delete_user&id=<?php echo $u['user_id']; ?>&redirect=manage_users.php"
                                       class="btn-icon" style="background: #ffebee; color: #dc3545; width: 36px; height: 36px; display: inline-flex; justify-content: center; align-items: center; border-radius: 6px;"
                                       title="Delete User"
                                       onclick="return confirm('Delete user <?php echo $u['username']; ?> and ALL their tasks?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" style="padding: 30px; text-align: center; color: #888;">No users found matching your search.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
</body>
</html>