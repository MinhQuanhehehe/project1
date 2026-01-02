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
<style>
    /* Tổng thể Sidebar tông Xanh Dương Sáng - Băng Giá */
    .sidebar {
        background: linear-gradient(180deg, #004e92 0%, #000428 100%) !important;
        color: #ffffff;
        position: relative;
        overflow-x: hidden;
    }

    /* Hiệu ứng tuyết rơi toàn Sidebar */
    .sidebar::after {
        content: "❄";
        position: absolute;
        top: -10%;
        left: 50%;
        font-size: 20px;
        color: rgba(255, 255, 255, 0.3);
        text-shadow: 40px 100px 0 rgba(255,255,255,0.2), -60px 250px 0 rgba(255,255,255,0.2), 30px 400px 0 rgba(255,255,255,0.2);
        animation: snow-global 10s linear infinite;
        pointer-events: none;
    }

    @keyframes snow-global {
        0% { transform: translateY(0) rotate(0deg); }
        100% { transform: translateY(800px) rotate(360deg); }
    }

    /* Tiêu đề */
    .sidebar-header h3 {
        color: #00d4ff !important;
        text-shadow: 0 0 10px rgba(0, 212, 255, 0.6);
    }
    
    .sidebar-header p {
        color: #b3e5fc;
        font-size: 0.9em;
    }

    /* Nút NEW TASK - Gradient Xanh Băng */
    .btn-new-task {
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        background: linear-gradient(135deg, #00d2ff 0%, #3a7bd5 100%);
        color: #ffffff !important;
        padding: 16px 20px;
        margin-bottom: 15px;
        border-radius: 12px;
        font-weight: bold;
        text-decoration: none;
        transition: all 0.4s ease;
        box-shadow: 0 4px 15px rgba(0, 210, 255, 0.4);
        border: 2px solid #e1f5fe;
        width: 100%;
        z-index: 1;
    }

    /* Băng rôn 2026 màu Đỏ rực rỡ */
    .btn-new-task::after {
        content: "❄ 2026";
        position: absolute;
        top: 5px;
        right: -20px;
        background: #ff3d00;
        color: white;
        font-size: 10px;
        padding: 2px 25px;
        transform: rotate(45deg);
        font-weight: 800;
        box-shadow: 0 2px 5px rgba(0,0,0,0.3);
    }

    .btn-new-task:hover {
        transform: scale(1.03);
        box-shadow: 0 0 25px rgba(0, 212, 255, 0.8);
    }

    /* Mũ Noel */
    .santa-hat {
        position: absolute;
        top: -10px;
        left: 12px;
        font-size: 20px;
        z-index: 2;
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
    }

    /* Menu Sidebar */
    .sidebar-menu li a {
        color: #e1f5fe !important;
        transition: 0.3s;
        border-radius: 8px;
        margin: 2px 10px;
        display: block;
        padding: 10px 15px;
    }

    .sidebar-menu li.active a, 
    .sidebar-menu li a:hover {
        background: rgba(255, 255, 255, 0.15) !important;
        color: #00e5ff !important;
    }

    .menu-label {
        color: #4fc3f7 !important;
        font-weight: bold;
        padding: 15px 20px 5px !important;
        font-size: 0.75rem;
        letter-spacing: 1px;
    }

    /* Icon trang trí */
    .dot-icon {
        width: 10px; height: 10px;
        display: inline-block;
        border-radius: 50%;
        margin-right: 8px;
        border: 1.5px solid #fff;
    }

    .logout-link {
        color: #ff8a80 !important;
    }
    .sidebar {
        background: linear-gradient(180deg, #004e92 0%, #000428 100%) !important;
        color: #ffffff;
        position: relative;
        overflow-x: hidden;
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: rgba(255, 255, 255, 0.3) transparent;
    }
    .sidebar::-webkit-scrollbar {
        width: 6px;
    }
    .sidebar::-webkit-scrollbar-track {
        background: transparent;
        margin-top: 10px;
        margin-bottom: 10px;
    }
    .sidebar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 10px;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .sidebar::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.5);
        cursor: pointer;
    }
</style>

<div class="sidebar">
    <div class="sidebar-header" style="padding: 20px;">
        <h3><i class="fas fa-snowflake"></i> Todo Pro</h3>
        <p>Merry Christmas, <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></strong> 🎅</p>
    </div>

    <div style="padding: 20px 20px 10px 20px; position: relative;">
        <span class="santa-hat">🎅</span> 
        <a href="<?php echo $path; ?>modules/tasks/create_task.php" class="btn-new-task">
            <i class="fas fa-plus-circle"></i>
            <span>CREATE NEW TASK</span>
        </a>
    </div>

    <ul class="sidebar-menu">
        <li class="<?php echo ($current_page == 'home.php' && $active_list_id == '') ? 'active' : ''; ?>">
            <a href="<?php echo $path; ?>home.php">
                <i class="fas fa-igloo"></i> Dashboard
            </a>
        </li>

        <li class="<?php echo ($current_page == 'tasks.php' && $active_list_id == '') ? 'active' : ''; ?>">
            <a href="<?php echo $path; ?>modules/tasks/tasks.php">
                <i class="fas fa-skating"></i> Tasks
            </a>
        </li>

        <li class="menu-label">MY LISTS</li>

        <li class="<?php echo ($active_list_id == 'inbox') ? 'active' : ''; ?>">
            <a href="<?php echo $path; ?>modules/lists/view_list.php?id=inbox">
                <i class="fas fa-gift"></i> Inbox
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
                <i class="fas fa-sleigh"></i> Manage Lists
            </a>
        </li>
        <li class="<?php echo ($current_page == 'manage_tags.php') ? 'active' : ''; ?>">
            <a href="<?php echo $path; ?>modules/tags/manage_tags.php">
                <i class="fas fa-candy-cane"></i> Manage Tags
            </a>
        </li>

        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <li class="menu-label">ADMINISTRATOR</li>
            <li class="<?php echo ($current_page == 'admin.php') ? 'active' : ''; ?>">
                <a href="<?php echo $path; ?>admin/admin.php"><i class="fas fa-user-shield"></i> Admin Panel</a>
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
