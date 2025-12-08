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
$result_get = $stmt_get->get_result();

if ($result_get->num_rows != 1) {
    header("Location: manage_tags.php");
    exit;
}
$tag = $result_get->fetch_assoc();
$stmt_get->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Tag - Todo App Pro</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<div class="container auth-container">
    <h2><i class="fas fa-tag" style="color: <?php echo htmlspecialchars($tag['color_code']); ?>;"></i> Edit Tag</h2>

    <?php if (!empty($error_message)): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <form action="edit_tag.php?id=<?php echo $tag_id; ?>" method="POST">
        <div>
            <label for="tag_name">Tag Name</label>
            <input type="text" id="tag_name" name="tag_name" value="<?php echo htmlspecialchars($tag['tag_name']); ?>" required>
        </div>
        <div>
            <label for="color_code">Color</label>
            <input type="color" id="color_code" name="color_code" value="<?php echo htmlspecialchars($tag['color_code'] ?? '#17a2b8'); ?>" style="height: 40px; width: 100%; padding: 2px;">
        </div>
        <div class="button-group" style="display: flex; gap: 10px; margin-top: 20px;">
            <button type="submit" class="btn">Save Changes</button>
            <a href="manage_tags.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
</body>
</html>
