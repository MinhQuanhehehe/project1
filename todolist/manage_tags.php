<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
global $conn;
include 'db_connect.php';

$user_id = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

// Add tag (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_tag'])) {
    $tag_name = trim($_POST['tag_name']);
    $color_code = $_POST['color_code'] ?? '#17a2b8';

    if (empty($tag_name)) {
        $error = "Tag name is required.";
    } else {
        // Insert (Tags có thể trùng tên nếu muốn)
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

// Delete Tag (GET)
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

// Query Tags
$tags_result = $conn->query("SELECT * FROM Tags WHERE user_id = $user_id ORDER BY tag_name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Tags - Todo App Pro</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .manager-item {
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
        .manager-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .icon-large-box {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            color: #fff;
            font-size: 1.2rem;
            margin-right: 15px;
            box-shadow: inset 0 0 0 1px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
<div class="container auth-container">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2><i class="fas fa-tags" style="color: #17a2b8;"></i> Manage Tags</h2>
        <a href="home.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
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
        <h4 style="margin-bottom: 15px;">Create New Tag</h4>
        <form action="manage_tags.php" method="POST" style="display: flex; gap: 10px; align-items: center;">
            <input type="hidden" name="add_tag" value="1">

            <div style="flex: 2; margin-bottom: 0;">
                <input type="text" name="tag_name" required placeholder="Tag Name (e.g. Urgent, Bug...)" style="margin-bottom: 0;">
            </div>

            <div style="flex: 0 0 50px; margin-bottom: 0;">
                <input type="color" name="color_code" value="#17a2b8" style="height: 42px; padding: 2px; margin-bottom: 0; width: 100%;">
            </div>

            <div style="flex: 0 0 50px; margin-bottom: 0;">
                <button type="submit" class="btn" style="width: 100%; height: 42px; padding: 0; border-radius: 8px;">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
        </form>
    </div>

    <h3>Your Tags</h3>
    <?php if ($tags_result->num_rows > 0): ?>
        <div class="list-container">
            <?php while($tag = $tags_result->fetch_assoc()): ?>
                <div class="manager-item">
                    <div style="display: flex; align-items: center;">
                        <div class="icon-large-box" style="background-color: <?php echo $tag['color_code']; ?>;">
                            <i class="fas fa-tag"></i>
                        </div>

                        <div>
                            <div style="font-weight: 700; font-size: 1.1rem; color: #333;">
                                <?php echo htmlspecialchars($tag['tag_name']); ?>
                            </div>
                            <div style="font-size: 0.85rem; color: <?php echo $tag['color_code']; ?>;">
                                <?php echo htmlspecialchars($tag['color_code']); ?>
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; gap: 8px;">
                        <a href="edit_tag.php?id=<?php echo $tag['tag_id']; ?>" class="btn-icon"
                           style="background: #e2e6ea; color: #495057;" title="Edit Tag">
                            <i class="fas fa-pen"></i>
                        </a>

                        <a href="manage_tags.php?delete=<?php echo $tag['tag_id']; ?>" class="btn-icon"
                           style="background: #ffebee; color: #dc3545;" title="Delete Tag"
                           onclick="return confirm('Delete this tag? Tasks using this tag will not be deleted.');">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p style="text-align: center; color: #888;">No tags found. Create one above!</p>
    <?php endif; ?>

</div>
</body>
</html>