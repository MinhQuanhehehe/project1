<?php
session_start();
global $conn;
include '../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../home.php");
    exit;
}

$search = $_GET['search'] ?? '';
$filter_role = $_GET['role'] ?? '';
$where_clauses = ["1=1"]; $params = []; $types = "";

// Filter by name or email
if (!empty($search)) {
    $where_clauses[] = "(username LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

// Filter by Role
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
    <link rel="stylesheet" href="../assets/css/style1.css?v=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<?php
$path_to_root = '../';
include '../includes/sidebar.php';
?>

<div class="main-content">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #e9ecef;">
        <div>
            <h2 style="margin: 0; color: #2c3e50;"><i class="fas fa-users-cog"></i> Manage Users</h2>
            <span style="color: #7f8c8d; font-size: 0.9em;">Found <?php echo $users_result->num_rows; ?> users</span>
        </div>
        <a href="admin.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <form action="manage_users.php" method="GET" class="filter-wrapper" style="display: flex; gap: 15px; align-items: flex-end;">
        <div style="flex: 2;">
            <label style="font-weight: 600; font-size: 0.9em; display: block; margin-bottom: 5px;">Search</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Username or Email..." class="filter-field">
        </div>
        <div style="flex: 1;">
            <label style="font-weight: 600; font-size: 0.9em; display: block; margin-bottom: 5px;">Role</label>
            <select name="role" class="filter-field">
                <option value="">-- All Roles --</option>
                <option value="user" <?php echo $filter_role==='user'?'selected':''; ?>>User</option>
                <option value="admin" <?php echo $filter_role==='admin'?'selected':''; ?>>Admin</option>
            </select>
        </div>
        <button type="submit" class="btn"><i class="fas fa-search"></i> Search</button>
        <?php if(!empty($search) || !empty($filter_role)): ?>
            <a href="manage_users.php" class="btn btn-secondary">Clear</a>
        <?php endif; ?>
    </form>

    <div style="background: #fff; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); overflow: hidden;">
        <table class="table-clean" style="margin: 0;">
            <thead style="background: #f8f9fa;">
            <tr>
                <th style="padding: 15px;">ID</th>
                <th style="padding: 15px;">User Info</th>
                <th style="padding: 15px;">Role</th>
                <th style="padding: 15px;">Joined Date</th>
                <th style="padding: 15px; text-align: right;">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php while($u = $users_result->fetch_assoc()): ?>
                <tr>
                    <td style="padding: 15px; color: #999;">#<?php echo $u['user_id']; ?></td>
                    <td style="padding: 15px;">
                        <div style="font-weight: bold; color: #2c3e50;"><?php echo htmlspecialchars($u['username']); ?></div>
                        <div style="font-size: 0.85em; color: #7f8c8d;"><?php echo htmlspecialchars($u['email'] ?? 'No Email'); ?></div>
                    </td>
                    <td style="padding: 15px;">
                        <?php if ($u['user_id'] != $_SESSION['user_id']): ?>
                            <form action="admin_actions.php" method="POST" style="margin: 0;">
                                <input type="hidden" name="action" value="update_role">
                                <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                                <input type="hidden" name="redirect" value="manage_users.php">
                                <select name="role" onchange="this.form.submit()" class="role-select" style="padding: 5px; border-radius: 4px; border: 1px solid #ccc;">
                                    <option value="user" <?php echo ($u['role']=='user')?'selected':''; ?>>User</option>
                                    <option value="admin" <?php echo ($u['role']=='admin')?'selected':''; ?>>Admin</option>
                                </select>
                            </form>
                        <?php else: ?>
                            <span class="role-badge badge-admin">YOU (ADMIN)</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 15px; color: #7f8c8d;"><?php echo date("M d, Y", strtotime($u['created_at'])); ?></td>
                    <td style="padding: 15px; text-align: right;">
                        <div style="display: flex; justify-content: flex-end; gap: 8px;">
                            <a href="admin_actions.php?action=reset_pass&id=<?php echo $u['user_id']; ?>&redirect=manage_users.php"
                               class="action-icon" onclick="return confirm('Reset password to 123456?');" title="Reset Password">
                                <i class="fas fa-key"></i>
                            </a>
                            <?php if($u['user_id'] != $_SESSION['user_id']): ?>
                                <a href="admin_actions.php?action=delete_user&id=<?php echo $u['user_id']; ?>&redirect=manage_users.php"
                                   class="action-icon icon-delete" onclick="return confirm('Delete this user?');" title="Delete User">
                                    <i class="fas fa-trash"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
