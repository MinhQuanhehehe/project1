<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
global $conn;
include '../../config/db_connect.php';

$user_id = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;
if (!$user_id) {
    header("Location: ../../auth/login.php");
    exit;
}

$error = '';
$success = '';

// --- XỬ LÝ 1: THÊM LIST MỚI (CREATE) ---
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
                // Ghi Log
                $conn->query("INSERT INTO ActivityLogs (user_id, action_type, target_table, target_id, details) VALUES ($user_id, 'CREATE', 'Lists', $lid, 'Created list: $list_name')");
                $success = "List created successfully!";
            } else {
                $error = "Error creating list.";
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
}

if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);

    // Check
    $stmt_check = $conn->prepare("SELECT list_name FROM Lists WHERE list_id = ? AND user_id = ?");
    $stmt_check->bind_param("ii", $del_id, $user_id);
    $stmt_check->execute();
    $res = $stmt_check->get_result();

    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $del_name = $row['list_name'];

        // Change to Inbox
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
    } else {
        $error = "List not found or permission denied.";
    }
    $stmt_check->close();
}

if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') {
    $success = "List deleted successfully. Tasks moved to Inbox.";
}

// Get Lists
$stmt_list = $conn->prepare("SELECT * FROM Lists WHERE user_id = ? ORDER BY created_at DESC");
$stmt_list->bind_param("i", $user_id);
$stmt_list->execute();
$lists_result = $stmt_list->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Lists - Todo App Pro</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .list-manager-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #fff;
            border-radius: 8px;
            margin-bottom: 10px;
            border: 1px solid #eee;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
            transition: all 0.2s;
        }
        .list-manager-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .folder-icon-large {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            color: #fff;
            font-size: 1.2rem;
            margin-right: 15px;
        }
    </style>
</head>
<body>
<div class="container auth-container">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2><i class="fas fa-folder-open" style="color: #007bff;"></i> Manage Lists</h2>
        <a href="../../home.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <?php if ($error): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; border: 1px solid #e9ecef; margin-bottom: 30px;">
        <h4 style="margin-bottom: 15px;">Create New List</h4>
        <form action="manage_lists.php" method="POST" style="display: flex; gap: 10px; align-items: center;">
            <input type="hidden" name="add_list" value="1">

            <div style="flex: 2; margin-bottom: 0;">
                <input type="text" name="list_name" required placeholder="List Name (e.g. Work, Gym...)" style="margin-bottom: 0; width: 100%;">
            </div>

            <div style="flex: 0 0 50px; margin-bottom: 0;">
                <input type="color" name="color_code" value="#007bff" style="height: 42px; padding: 2px; margin-bottom: 0; width: 100%;">
            </div>

            <div style="flex: 0 0 50px; margin-bottom: 0;">
                <button type="submit" class="btn" style="width: 100%; height: 42px; padding: 0; border-radius: 8px;">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
        </form>
    </div>

    <h3>Your Lists</h3>
    <?php if ($lists_result->num_rows > 0): ?>
        <div class="list-container">
            <?php while($list = $lists_result->fetch_assoc()): ?>
                <div class="list-manager-item">
                    <div style="display: flex; align-items: center;">
                        <div class="folder-icon-large" style="background-color: <?php echo htmlspecialchars($list['color_code']); ?>;">
                            <i class="fas fa-folder"></i>
                        </div>

                        <div>
                            <div style="font-weight: 700; font-size: 1.1rem; color: #333;">
                                <?php echo htmlspecialchars($list['list_name']); ?>
                            </div>
                            <div style="font-size: 0.85rem; color: <?php echo htmlspecialchars($list['color_code']); ?>;">
                                <?php echo htmlspecialchars($list['color_code']); ?>
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; gap: 8px;">
                        <a href="edit_list.php?id=<?php echo $list['list_id']; ?>" class="btn-icon"
                           style="background: #e2e6ea; color: #495057; padding: 8px 12px; border-radius: 5px; text-decoration: none;" title="Edit Name/Color">
                            <i class="fas fa-pen"></i>
                        </a>

                        <a href="manage_lists.php?delete_id=<?php echo $list['list_id']; ?>" class="btn-icon"
                           style="background: #ffebee; color: #dc3545; padding: 8px 12px; border-radius: 5px; text-decoration: none;" title="Delete List"
                           onclick="return confirm('Are you sure you want to delete this list? Tasks inside will be moved to Inbox.');">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p style="text-align: center; color: #888;">No lists found. Create one above!</p>
    <?php endif; ?>

</div>
</body>
</html>