<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
global $conn;
include '../../config/db_connect.php';

$user_id = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;
if (!$user_id) {
    header("Location: ../../home.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_tags.php");
    exit;
}
$tag_id = $_GET['id'];
$error_message = '';

// UPDATE
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_tag_name = trim($_POST['tag_name']);
    $new_color = $_POST['color_code'];

    if (empty($new_tag_name)) {
        $error_message = "Tag name cannot be empty.";
    } else {
        // Update Tag
        $stmt_update = $conn->prepare("UPDATE Tags SET tag_name = ?, color_code = ? WHERE tag_id = ? AND user_id = ?");
        $stmt_update->bind_param("ssii", $new_tag_name, $new_color, $tag_id, $user_id);

        if ($stmt_update->execute()) {
            // Log
            $detail = "Updated tag: " . $new_tag_name;
            $stmt_log = $conn->prepare("INSERT INTO ActivityLogs (user_id, action_type, target_table, target_id, details) VALUES (?, 'UPDATE', 'Tags', ?, ?)");
            $stmt_log->bind_param("iis", $user_id, $tag_id, $detail);
            $stmt_log->execute();

            header("Location: manage_tags.php?status=edited");
            exit;
        } else {
            $error_message = "Failed to update tag.";
        }
        $stmt_update->close();
    }
}

// Old Info
$stmt_get = $conn->prepare("SELECT tag_name, color_code FROM Tags WHERE tag_id = ? AND user_id = ?");
$stmt_get->bind_param("ii", $tag_id, $user_id);
$stmt_get->execute();
$tag = $stmt_get->get_result()->fetch_assoc();
if (!$tag) { header("Location: manage_tags.php"); exit; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Tag</title>
    <link rel="stylesheet" href="../../assets/css/style1.css?v=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<?php
$path_to_root = '../../';
include '../../includes/sidebar.php';
?>

<div class="main-content">
    <div style="max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
        <h2 style="margin-top: 0; color: #2c3e50; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 20px;">
            <i class="fas fa-tag" style="color: <?php echo htmlspecialchars($tag['color_code']); ?>;"></i> Edit Tag
        </h2>

        <?php if (!empty($error_message)): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form action="edit_tag.php?id=<?php echo $tag_id; ?>" method="POST">
            <div style="margin-bottom: 15px;">
                <label style="font-weight: 600; display: block; margin-bottom: 5px;">Tag Name</label>
                <input type="text" name="tag_name" value="<?php echo htmlspecialchars($tag['tag_name']); ?>" required class="filter-field">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="font-weight: 600; display: block; margin-bottom: 5px;">Color</label>
                <input type="color" name="color_code" value="<?php echo htmlspecialchars($tag['color_code'] ?? '#17a2b8'); ?>" style="height: 45px; width: 100%; padding: 2px; border: 1px solid #ced4da; border-radius: 6px;">
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn">Save Changes</button>
                <a href="manage_tags.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
