<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
global $conn;
include '../../config/db_connect.php';

$user_id = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;
if (!$user_id) {
    header("Location: ../../auth/login.php");
    exit;
}

$redirect_url = '../../home.php';

if (isset($_GET['redirect_url']) && !empty($_GET['redirect_url'])) {
    $redirect_url = $_GET['redirect_url'];
}
elseif (isset($_POST['redirect_url']) && !empty($_POST['redirect_url'])) {
    $redirect_url = $_POST['redirect_url'];
}
elseif (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
    if (strpos($_SERVER['HTTP_REFERER'], 'manage_tags.php') === false) {
        $redirect_url = $_SERVER['HTTP_REFERER'];
    }
}
// -----------------------------------------

$error = ''; $success = '';

// ADD TAG
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_tag'])) {
    $tag_name = trim($_POST['tag_name']);
    $color_code = $_POST['color_code'] ?? '#17a2b8';

    if (empty($tag_name)) {
        $error = "Tag name is required.";
    } else {
        $stmt_insert = $conn->prepare("INSERT INTO Tags (user_id, tag_name, color_code) VALUES (?, ?, ?)");
        $stmt_insert->bind_param("iss", $user_id, $tag_name, $color_code);

        if ($stmt_insert->execute()) {
            // Log hoạt động
            $tid = $conn->insert_id;
            $conn->query("INSERT INTO ActivityLogs (user_id, action_type, target_table, target_id, details) VALUES ($user_id, 'CREATE', 'Tags', $tid, 'Created tag: $tag_name')");
            $success = "Tag created successfully!";
        } else {
            $error = "Error creating tag.";
        }
        $stmt_insert->close();
    }
}

// DELETE TAG
if (isset($_GET['delete'])) {
    $tag_id = intval($_GET['delete']);

    // Lấy tên tag để ghi log trước khi xóa
    $stmt_get = $conn->prepare("SELECT tag_name FROM Tags WHERE tag_id = ? AND user_id = ?");
    $stmt_get->bind_param("ii", $tag_id, $user_id);
    $stmt_get->execute();
    $res_get = $stmt_get->get_result();

    if ($res_get->num_rows > 0) {
        $tag_name_del = $res_get->fetch_assoc()['tag_name'];

        $stmt_del = $conn->prepare("DELETE FROM Tags WHERE tag_id = ? AND user_id = ?");
        $stmt_del->bind_param("ii", $tag_id, $user_id);

        if ($stmt_del->execute()) {
            // Log hoạt động
            $detail = "Deleted tag: " . $tag_name_del;
            $conn->query("INSERT INTO ActivityLogs (user_id, action_type, target_table, target_id, details) VALUES ($user_id, 'DELETE', 'Tags', $tag_id, '$detail')");

            // --- 2. REDIRECT SAU KHI XÓA (Kèm redirect_url) ---
            header("Location: manage_tags.php?msg=deleted&redirect_url=" . urlencode($redirect_url));
            exit;
        } else {
            $error = "Error deleting tag.";
        }
        $stmt_del->close();
    }
    $stmt_get->close();
}

if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') {
    $success = "Tag deleted successfully.";
}

$tags_result = $conn->query("SELECT * FROM Tags WHERE user_id = $user_id ORDER BY tag_name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Tags</title>
    <link rel="stylesheet" href="../../assets/css/style1.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .btn-back {
            display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px;
            background-color: transparent; color: #6c757d; border: 1px solid #dee2e6;
            border-radius: 6px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;
        }
        .btn-back:hover {
            transform: scale(1.1); background-color: #f8f9fa; color: #343a40;
            border-color: #adb5bd; box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .btn-create {
            background-color: #007bff !important; color: white !important; border: none;
            padding: 0 20px; border-radius: 6px; cursor: pointer; transition: all 0.3s ease;
            display: inline-flex; align-items: center; gap: 8px; height: 42px; font-weight: 600;
        }
        .btn-create:hover {
            background-color: #0056b3 !important; transform: translateY(-5px);
            box-shadow: 0 4px 6px rgba(0, 123, 255, 0.2);
        }
    </style>
</head>
<body>

<?php
$path_to_root = '../../';
include '../../includes/sidebar.php';
?>

<div class="main-content">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #e9ecef;">
        <h2 style="margin: 0; color: #2c3e50;"><i class="fas fa-tags"></i> Manage Tags</h2>
        <a href="<?php echo htmlspecialchars($redirect_url); ?>" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
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
        <h4 style="margin-top: 0; margin-bottom: 15px; color: #555;">Create New Tag</h4>
        <form action="manage_tags.php" method="POST" style="display: flex; gap: 15px; align-items: flex-end;">
            <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($redirect_url); ?>">
            <input type="hidden" name="add_tag" value="1">

            <div style="flex: 2;">
                <label style="font-weight: 600; display: block; margin-bottom: 5px;">Tag Name</label>
                <input type="text" name="tag_name" required placeholder="e.g. Urgent, Bug, Idea..." class="filter-field">
            </div>

            <div style="flex: 0 0 100px;">
                <label style="font-weight: 600; display: block; margin-bottom: 5px;">Color</label>
                <input type="color" name="color_code" value="#17a2b8" style="height: 42px; padding: 2px; width: 100%; border: 1px solid #ced4da; border-radius: 6px;">
            </div>

            <button type="submit" class="btn-create" style="height: 42px;"><i class="fas fa-plus"></i></button>
        </form>
    </div>

    <h3 style="color: #2c3e50; margin-bottom: 15px;">Your Tags</h3>
    <?php if ($tags_result->num_rows > 0): ?>
        <div class="list-container">
            <?php while($tag = $tags_result->fetch_assoc()): ?>
                <div class="manager-item">

                    <div class="manager-item-left">
                        <div class="icon-large-box" style="background-color: <?php echo htmlspecialchars($tag['color_code']); ?>;">
                            <i class="fas fa-tag"></i>
                        </div>

                        <div class="manager-info">
                            <div><?php echo htmlspecialchars($tag['tag_name']); ?></div>
                            <div style="color: <?php echo htmlspecialchars($tag['color_code']); ?>">
                                <?php echo htmlspecialchars($tag['color_code']); ?>
                            </div>
                        </div>
                    </div>

                    <div class="manager-actions">
                        <a href="edit_tag.php?id=<?php echo $tag['tag_id']; ?>&redirect_url=<?php echo urlencode($redirect_url); ?>" class="action-icon icon-edit" title="Edit">
                            <i class="fas fa-pen"></i>
                        </a>

                        <a href="manage_tags.php?delete=<?php echo $tag['tag_id']; ?>&redirect_url=<?php echo urlencode($redirect_url); ?>"
                           class="action-icon icon-delete"
                           title="Delete"
                           onclick="return confirm('Delete this tag? Tasks using this tag will NOT be deleted.');">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p style="text-align: center; color: #888; padding: 30px;">No tags found. Create one above!</p>
    <?php endif; ?>

</div>
</body>
</html>