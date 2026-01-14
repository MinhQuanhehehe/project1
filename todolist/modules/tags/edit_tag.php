<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
global $conn;
include '../../config/db_connect.php';

$user_id = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;
if (!$user_id) { header("Location: ../../auth/login.php"); exit; }

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_tags.php");
    exit;
}
$tag_id = $_GET['id'];

$redirect_url = 'manage_tags.php';

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

            // Redirect về trang cũ
            $connector = (strpos($redirect_url, '?') !== false) ? '&' : '?';
            header("Location: " . $redirect_url . $connector . "msg=edited");
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
    <title>Edit Tag - Todo Pro</title>
    <link rel="stylesheet" href="../../assets/css/style1.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Đồng nhất style nút Back */
        .btn-back {
            display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px;
            background-color: transparent; color: #6c757d; border: 1px solid #dee2e6;
            border-radius: 6px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;
        }
        .btn-back:hover {
            transform: scale(1.1); background-color: #f8f9fa; color: #343a40;
            border-color: #adb5bd; box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        /* Form Card Layout */
        .form-card {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            max-width: 500px;
            margin: 0 auto;
        }

        .btn-primary-large {
            background-color: #007bff !important; color: white !important;
            width: 100%; padding: 15px; border: none; border-radius: 8px;
            font-weight: 700; font-size: 1.1em; cursor: pointer; transition: all 0.3s ease;
        }
        .btn-primary-large:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 123, 255, 0.3);
            background-color: #0056b3 !important;
        }

        .form-label { font-weight: 600; display: block; margin-bottom: 8px; color: #2c3e50; }
        .form-input {
            width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 6px;
            font-size: 1rem; box-sizing: border-box;
        }
        .form-input:focus { border-color: #007bff; outline: none; }
    </style>
</head>
<body>

<?php
$path_to_root = '../../';
include '../../includes/sidebar.php';
?>

<div class="main-content">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #e9ecef;">
        <h2 style="margin: 0; color: #2c3e50;">
            <i class="fas fa-tag" style="color: <?php echo htmlspecialchars($tag['color_code']); ?>;"></i> Edit Tag
        </h2>

        <a href="<?php echo htmlspecialchars($redirect_url); ?>" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    <div class="form-card">
        <?php if (!empty($error_message)): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form action="edit_tag.php?id=<?php echo $tag_id; ?>" method="POST">
            <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($redirect_url); ?>">

            <div style="margin-bottom: 20px;">
                <label class="form-label">Tag Name</label>
                <input type="text" name="tag_name" value="<?php echo htmlspecialchars($tag['tag_name']); ?>" required class="form-input" placeholder="Enter tag name...">
            </div>

            <div style="margin-bottom: 30px;">
                <label class="form-label">Color Label</label>
                <input type="color" name="color_code" value="<?php echo htmlspecialchars($tag['color_code'] ?? '#17a2b8'); ?>" style="height: 50px; width: 100%; padding: 2px; border: 1px solid #ced4da; border-radius: 6px; cursor: pointer;">
            </div>

            <div class="form-actions" style="align-items: center; justify-content: center">
                <button type="submit" class="btn btn-primary-large" style="width: 20%"><i class="fas fa-save"></i></button>
            </div>
        </form>
    </div>

</div>
</body>
</html>