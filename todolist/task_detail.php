<?php
global $conn;
include 'db_connect.php'; 

if (!isset($_SESSION['UserID'])) {
   header("Location: login.php");
   exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
   header("Location: home.php");
   exit;
}

$task_id = $_GET['id'];
$current_user_id = $_SESSION['UserID'];

$sql = "SELECT t.*, l.ListName
       FROM Task t
       LEFT JOIN List l ON t.ListID = l.ListID
       WHERE t.TaskID = ? AND t.UserID = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $task_id, $current_user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
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
   <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<div class="container">
   <div class="task-detail-card">
       <h2><?php echo htmlspecialchars($task['Title']); ?></h2>
       <p class="text-secondary">
           <?php
           echo nl2br(htmlspecialchars($task['Description']));
           ?>
       </p>
       <hr class="task-divider">
       <div class="task-meta-details">
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
       <div class="button-group">
            <a href="edit_task.php?id=<?php echo $task['TaskID']; ?>" class="action-icon icon-edit" title="Edit Task">
                <i class="fas fa-edit"></i> 
            </a>
            <a href="delete_task.php?id=<?php echo $task['TaskID']; ?>" class="action-icon icon-delete" title="Delete Task" onclick="return confirm('Are you sure you want to delete this task?');">
                <i class="fas fa-trash-alt"></i> 
            </a>
            
            <a href="home.php" class="action-icon icon-secondary" title="Back to Home">
                <i class="fas fa-home"></i> 
            </a>
        </div>
   </div>
</div>
</body>
</html>

