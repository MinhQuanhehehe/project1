<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
global $conn;
include '../../config/db_connect.php';

$user_id = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;
if (!$user_id) { header("Location: ../../auth/login.php"); exit; }

$error = ''; $success = '';

// CREATE LIST
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_list'])) {
    $list_name = trim($_POST['list_name']);
    $color_code = $_POST['color_code'] ?? '#007bff';

    if (empty($list_name)) {
        $error = "List name is required.";
    } else {
        $stmt_check = $conn->prepare("SELECT list_id FROM Lists WHERE user_id = ? AND list_name = ?");
        $stmt_check->bind_param("is", $user_id, $list_name);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            $error = "List name already exists.";
        } else {
            $stmt_insert = $conn->prepare("INSERT INTO Lists (user_id, list_name, color_code) VALUES (?, ?, ?)");
            $stmt_insert->bind_param("iss", $user_id, $list_name, $color_code);
            if ($stmt_insert->execute()) {
                $lid = $conn->insert_id;
                $conn->query("INSERT INTO ActivityLogs (user_id, action_type, target_table, target_id, details) VALUES ($user_id, 'CREATE', 'Lists', $lid, 'Created list: $list_name')");
                $success = "List created successfully!";
            } else { $error = "Error creating list."; }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
}

// DELETE LIST
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    $stmt_check = $conn->prepare("SELECT list_name FROM Lists WHERE list_id = ? AND user_id = ?");
    $stmt_check->bind_param("ii", $del_id, $user_id);
    $stmt_check->execute();
    $res = $stmt_check->get_result();

    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $del_name = $row['list_name'];
        // Move tasks to Inbox
        $stmt_move = $conn->prepare("UPDATE Tasks SET list_id = NULL WHERE list_id = ? AND user_id = ?");
        $stmt_move->bind_param("ii", $del_id, $user_id);
        $stmt_move->execute();
        $stmt_move->close();
        // Delete List
        $stmt_delete = $conn->prepare("DELETE FROM Lists WHERE list_id = ? AND user_id = ?");
        $stmt_delete->bind_param("ii", $del_id, $user_id);
        if ($stmt_delete->execute()) {
            // Log
            $stmt_log = $conn->prepare("INSERT INTO ActivityLogs (user_id, action_type, target_table, target_id, details) VALUES (?, 'DELETE', 'Lists', ?, ?)");
            $detail = "Deleted list: " . $del_name . " (Tasks moved to Inbox)";
            $stmt_log->bind_param("iis", $user_id, $del_id, $detail);
            $stmt_log->execute();

            header("Location: manage_lists.php?msg=deleted");
            exit;
        } else {
            $error = "Error deleting list.";
        }
        $stmt_delete->close();
    }
    $stmt_check->close();
}

if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') {
    $success = "List deleted successfully. Tasks moved to Inbox.";
}

// GET LISTS
$stmt_list = $conn->prepare("SELECT * FROM Lists WHERE user_id = ? ORDER BY created_at DESC");
$stmt_list->bind_param("i", $user_id);
$stmt_list->execute();
$lists_result = $stmt_list->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Lists</title>
    <link rel="stylesheet" href="../../assets/css/style1.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<?php
$path_to_root = '../../';
include '../../includes/sidebar.php';
?>

<div class="main-content">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #e9ecef;">
        <h2 style="margin: 0; color: #2c3e50;"><i class="fas fa-folder-open"></i> Manage Lists</h2>
        <a href="../../home.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <?php if ($error): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <div style="background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 30px;">
        <h4 style="margin-top: 0; margin-bottom: 15px; color: #555;">Create New List</h4>
        <form action="manage_lists.php" method="POST" style="display: flex; gap: 15px; align-items: flex-end;">
            <input type="hidden" name="add_list" value="1">

            <div style="flex: 2;">
                <label style="font-weight: 600; display: block; margin-bottom: 5px;">List Name</label>
                <input type="text" name="list_name" required placeholder="e.g. Work, Personal, Shopping..." class="filter-field">
            </div>

            <div style="flex: 0 0 100px;">
                <label style="font-weight: 600; display: block; margin-bottom: 5px;">Color</label>
                <input type="color" name="color_code" value="#007bff" style="height: 42px; padding: 2px; width: 100%; border: 1px solid #ced4da; border-radius: 6px;">
            </div>

            <button type="submit" class="btn" style="height: 42px;"><i class="fas fa-plus"></i> Create</button>
        </form>
    </div>

    <h3 style="color: #2c3e50; margin-bottom: 15px;">Your Lists</h3>
    <?php if ($lists_result->num_rows > 0): ?>
        <div class="list-container">
            <?php while($list = $lists_result->fetch_assoc()): ?>
                <div class="manager-item">

                    <div class="manager-item-left">
                        <div class="icon-large-box" style="background-color: <?php echo htmlspecialchars($list['color_code']); ?>;">
                            <i class="fas fa-folder"></i>
                        </div>

                        <div class="manager-info">
                            <div><?php echo htmlspecialchars($list['list_name']); ?></div>
                            <div style="color: <?php echo htmlspecialchars($list['color_code']); ?>">
                                <?php echo htmlspecialchars($list['color_code']); ?>
                            </div>
                        </div>
                    </div>

                    <div class="manager-actions">
                        <a href="edit_list.php?id=<?php echo $list['list_id']; ?>" class="action-icon icon-edit" title="Edit">
                            <i class="fas fa-pen"></i>
                        </a>
                        <a href="manage_lists.php?delete_id=<?php echo $list['list_id']; ?>" class="action-icon icon-delete" title="Delete"
                           onclick="return confirm('Delete list \'<?php echo $list['list_name']; ?>\'? Tasks will be moved to Inbox.');">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p style="text-align: center; color: #888; padding: 30px;">No lists found. Create one above!</p>
    <?php endif; ?>

</div>
</body>
</html>