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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $list_name = trim($_POST['list_name']);
    $color_code = $_POST['color_code'] ?? '#007bff';

    if (empty($list_name)) {
        $error = "List name is required.";
    } else {
        // Kiểm tra trùng tên (Bảng Lists, user_id, list_name)
        $stmt_check = $conn->prepare("SELECT list_id FROM Lists WHERE user_id = ? AND list_name = ?");
        $stmt_check->bind_param("is", $user_id, $list_name);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $error = "A list with this name already exists.";
        } else {
            // Insert Lists
            $stmt_insert = $conn->prepare("INSERT INTO Lists (user_id, list_name, color_code) VALUES (?, ?, ?)");
            $stmt_insert->bind_param("iss", $user_id, $list_name, $color_code);

            if ($stmt_insert->execute()) {
                // Log
                $new_list_id = $conn->insert_id;
                $stmt_log = $conn->prepare("INSERT INTO ActivityLogs (user_id, action_type, target_table, target_id, details) VALUES (?, 'CREATE', 'Lists', ?, ?)");
                $detail = "Created list: " . $list_name;
                $stmt_log->bind_param("iis", $user_id, $new_list_id, $detail);
                $stmt_log->execute();
                $stmt_log->close();

                header("Location: home.php?list_created=1");
                exit;
            } else {
                $error = "Error creating list: " . $stmt_insert->error;
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New List - Todo App Pro</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container auth-container">
    <form action="create_list.php" method="POST">
        <h2>Create New List</h2>

        <?php if(!empty($error)): ?>
            <p style="color: red; text-align: center; background: #ffe6e6; padding: 10px; border-radius: 5px;"><?php echo $error; ?></p>
        <?php endif; ?>

        <div>
            <label for="list_name">List Name</label>
            <input type="text" id="list_name" name="list_name" required placeholder="e.g., Work, Personal, Shopping...">
        </div>

        <div>
            <label for="color_code">List Color</label>
            <input type="color" id="color_code" name="color_code" value="#007bff" style="height: 40px; padding: 2px;">
        </div>

        <div class="button-group" style="display: flex; gap: 10px; margin-top: 20px;">
            <button type="submit" class="btn">Create List</button>
            <a href="home.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
</body>
</html>