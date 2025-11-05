<?php
global $conn;
include 'db_connect.php'; // Đã bao gồm session_start()


// Bảo vệ trang: Nếu chưa đăng nhập, chuyển về trang login
if (!isset($_SESSION['UserID'])) {
   header("Location: login.php");
   exit;
}


// Kiểm tra xem ID task có được cung cấp không
if (!isset($_GET['id']) || empty($_GET['id'])) {
   header("Location: home.php");
   exit;
}


$task_id = $_GET['id'];
$current_user_id = $_SESSION['UserID'];


// CẬP NHẬT TRUY VẤN: Dùng LEFT JOIN để lấy ListName (nếu có)
$sql = "SELECT t.*, l.ListName
       FROM Task t
       LEFT JOIN List l ON t.ListID = l.ListID
       WHERE t.TaskID = ? AND t.UserID = ?";


$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $task_id, $current_user_id);
$stmt->execute();
$result = $stmt->get_result();


if ($result->num_rows == 0) {
   // Task không tồn tại hoặc không thuộc về user này
   header("Location: home.php");
   exit;
}


$task = $result->fetch_assoc();


$stmt->close();
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Task Detail - Todo App</title>
   <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
   <!-- Dùng class 'task-detail-card' để định dạng thẻ chi tiết -->
   <div class="task-detail-card">


       <!-- Tiêu đề Task -->
       <h2><?php echo htmlspecialchars($task['Title']); ?></h2>


       <!-- Mô tả -->
       <div class="task-description">
           <?php
           // nl2br() dùng để chuyển dấu xuống dòng (\n) thành thẻ <br>
           echo nl2br(htmlspecialchars($task['Description']));
           ?>
       </div>


       <hr class="task-divider">


       <!-- Thông tin meta (List, Priority, Due Date) -->
       <div class="task-meta-details">
           <!-- TÍNH NĂNG MỚI: Hiển thị List -->
           <div class="meta-item">
               <strong>List:</strong>
               <?php if (!empty($task['ListName'])): ?>
                   <span><?php echo htmlspecialchars($task['ListName']); ?></span>
               <?php else: ?>
                   <span class="meta-default"><em>(No List)</em></span>
               <?php endif; ?>
           </div>


           <div class="meta-item">
               <strong>Priority:</strong>
               <span class="priority-text-<?php echo htmlspecialchars($task['Priority']); ?>">
                       <?php echo ucfirst(htmlspecialchars($task['Priority'])); // ucfirst() viết hoa chữ cái đầu ?>
                   </span>
           </div>


           <div class="meta-item">
               <strong>Due Date:</strong>
               <?php if (!empty($task['DueDate'])): ?>
                   <span><?php echo date("F j, Y", strtotime($task['DueDate'])); // Format lại ngày cho đẹp ?></span>
               <?php else: ?>
                   <span class="meta-default"><em>(No Due Date)</em></span>
               <?php endif; ?>
           </div>
       </div>


       <!-- Nút hành động -->
       <div class="task-detail-actions">
           <a href="edit_task.php?id=<?php echo $task['TaskID']; ?>" class="btn btn-edit">Edit Task</a>
           <a href="delete_task.php?id=<?php echo $task['TaskID']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this task?');">Delete Task</a>
           <a href="home.php" class="btn btn-secondary">Back to Home</a>
       </div>
   </div>
</div>
</body>
</html>

