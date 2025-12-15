<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
global $conn;
include '../../config/db_connect.php';

$user_id = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

// Add Tag
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_tag'])) {
    $tag_name = trim($_POST['tag_name']);
    $color_code = $_POST['color_code'] ?? '#17a2b8';

    if (empty($tag_name)) {
        $error = "Tag name is required.";
    } else {
        // Insert
        $stmt_insert = $conn->prepare("INSERT INTO Tags (user_id, tag_name, color_code) VALUES (?, ?, ?)");
        $stmt_insert->bind_param("iss", $user_id, $tag_name, $color_code);

        if ($stmt_insert->execute()) {
            $success = "Tag created successfully!";
        } else {
            $error = "Error creating tag.";
        }
        $stmt_insert->close();
    }
}

// Delete Tag
if (isset($_GET['delete'])) {
    $tag_id = $_GET['delete'];
    $stmt_del = $conn->prepare("DELETE FROM Tags WHERE tag_id = ? AND user_id = ?");
    $stmt_del->bind_param("ii", $tag_id, $user_id);
    if ($stmt_del->execute()) {
        $success = "Tag deleted successfully.";
    } else {
        $error = "Error deleting tag.";
    }
    $stmt_del->close();
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
</head>
<body>

<?php
$path_to_root = '../../';
include '../../includes/sidebar.php';
?>

<div class="main-content">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #e9ecef;">
        <h2 style="margin: 0; color: #2c3e50;"><i class="fas fa-tags"></i> Manage Tags</h2>
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
        <h4 style="margin-top: 0; margin-bottom: 15px; color: #555;">Create New Tag</h4>
        <form action="manage_tags.php" method="POST" style="display: flex; gap: 15px; align-items: flex-end;">
            <input type="hidden" name="add_tag" value="1">

            <div style="flex: 2;">
                <label style="font-weight: 600; display: block; margin-bottom: 5px;">Tag Name</label>
                <input type="text" name="tag_name" required placeholder="e.g. Urgent, Bug, Idea..." class="filter-field">
            </div>

            <div style="flex: 0 0 100px;">
                <label style="font-weight: 600; display: block; margin-bottom: 5px;">Color</label>
                <input type="color" name="color_code" value="#17a2b8" style="height: 42px; padding: 2px; width: 100%; border: 1px solid #ced4da; border-radius: 6px;">
            </div>

            <button type="submit" class="btn" style="height: 42px;"><i class="fas fa-plus"></i> Create</button>
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
                        <a href="edit_tag.php?id=<?php echo $tag['tag_id']; ?>" class="action-icon icon-edit" title="Edit">
                            <i class="fas fa-pen"></i>
                        </a>
                        <a href="manage_tags.php?delete=<?php echo $tag['tag_id']; ?>" class="action-icon icon-delete" title="Delete"
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