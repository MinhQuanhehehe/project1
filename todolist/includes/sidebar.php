<?php
// Define path if not set
$path = isset($path_to_root) ? $path_to_root : './';
$current_page = basename($_SERVER['PHP_SELF']);

// Get active list from URL
$active_list_id = isset($_GET['list_id']) ? $_GET['list_id'] : '';

// --- LOGIC: FETCH LISTS ---
if (isset($conn) && isset($_SESSION['user_id'])) {
    $uid_sidebar = $_SESSION['user_id'];
    $sql_sb = "SELECT list_id, list_name, color_code FROM Lists WHERE user_id = $uid_sidebar ORDER BY list_name ASC";
    $res_sb = $conn->query($sql_sb);

    $sidebar_lists = [];
    if ($res_sb) {
        while($row = $res_sb->fetch_assoc()) {
            $sidebar_lists[] = $row;
        }
    }
} else {
    $sidebar_lists = [];
}
?>

<div class="sidebar">
    <div class="sidebar-header">
        <h3><i class="fas fa-check-double"></i> Todo Pro</h3>
        <p>Welcome, <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></strong></p>
    </div>

    <div style="padding: 20px 20px 10px 20px;">
        <a href="<?php echo $path; ?>modules/tasks/create_task.php" class="btn"
           style="display: block; text-align: center; background: #007bff; color: white; width: 100%;">
            <i class="fas fa-plus"></i> New Task
        </a>
    </div>

    <ul class="sidebar-menu">
        <li class="<?php echo ($current_page == 'home.php' && $active_list_id == '') ? 'active' : ''; ?>">
            <a href="<?php echo $path; ?>home.php">
                <i class="fas fa-home"></i> Dashboard
            </a>
        </li>

        <li class="<?php echo ($current_page == 'tasks.php' && $active_list_id == '') ? 'active' : ''; ?>">
            <a href="<?php echo $path; ?>modules/tasks/tasks.php">
                <i class="fas fa-tasks"></i> Tasks
            </a>
        </li>

        <li class="menu-label">
            MY LISTS
        </li>

        <li class="<?php echo ($active_list_id == 'inbox') ? 'active' : ''; ?>">
            <a href="<?php echo $path; ?>modules/lists/view_list.php?id=inbox">
                Inbox
            </a>
        </li>

        <?php if (!empty($sidebar_lists)): ?>
            <?php foreach ($sidebar_lists as $s_list):
                $is_active = ($active_list_id == $s_list['list_id']);
                ?>
                <li class="<?php echo $is_active ? 'active' : ''; ?>">
                    <a href="<?php echo $path; ?>modules/lists/view_list.php?id=<?php echo $s_list['list_id']; ?>">
                        <span class="dot-icon" style="background-color: <?php echo $s_list['color_code']; ?>;"></span>
                        <?php echo htmlspecialchars($s_list['list_name']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>

        <li class="menu-label">MANAGEMENT</li>
        <li class="<?php echo ($current_page == 'manage_lists.php') ? 'active' : ''; ?>">
            <a href="<?php echo $path; ?>modules/lists/manage_lists.php">
                <i class="fas fa-folder"></i> Manage Lists
            </a>
        </li>
        <li class="<?php echo ($current_page == 'manage_tags.php') ? 'active' : ''; ?>">
            <a href="<?php echo $path; ?>modules/tags/manage_tags.php">
                <i class="fas fa-tags"></i> Manage Tags
            </a>
        </li>

        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <li class="menu-label">ADMINISTRATOR</li>
            <li class="<?php echo ($current_page == 'admin.php') ? 'active' : ''; ?>">
                <a href="<?php echo $path; ?>admin/admin.php"><i class="fas fa-tachometer-alt"></i> Admin Dashboard</a>
            </li>
            <li class="<?php echo ($current_page == 'manage_users.php') ? 'active' : ''; ?>">
                <a href="<?php echo $path; ?>admin/manage_users.php"><i class="fas fa-users-cog"></i> Users</a>
            </li>
            <li class="<?php echo ($current_page == 'logs.php') ? 'active' : ''; ?>">
                <a href="<?php echo $path; ?>admin/logs.php"><i class="fas fa-history"></i> System Logs</a>
            </li>
        <?php endif; ?>

        <li class="menu-label">SETTINGS</li>
        <li class="<?php echo ($current_page == 'change_password.php') ? 'active' : ''; ?>">
            <a href="<?php echo $path; ?>auth/change_password.php"><i class="fas fa-key"></i> Change Password</a>
        </li>
        <li>
            <a href="<?php echo $path; ?>auth/logout.php" class="logout-link">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </li>
    </ul>
</div>
