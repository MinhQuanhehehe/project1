<?php
global $conn;
include 'db_connect.php';

if (!isset($_SESSION['UserID'])) {
   header("Location: login.php");
   exit;
}
$current_user_id = $_SESSION['UserID'];

if (!isset($_GET['id']) || empty($_GET['id']) || !is_numeric($_GET['id'])) {
   header("Location: home.php");
   exit;
}
$task_id = $_GET['id'];
$error_message = '';
$success_message = '';

$stmt_lists = $conn->prepare("SELECT ListID, ListName FROM List WHERE UserID = ? ORDER BY ListName");
$stmt_lists->bind_param("i", $current_user_id);
$stmt_lists->execute();
$lists_result = $stmt_lists->get_result();
$user_lists = [];
while ($list = $lists_result->fetch_assoc()) {
   $user_lists[] = $list;
}
$stmt_lists->close();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

   $title = trim($_POST['title']);
   $description = trim($_POST['description']);
   $due_date = empty($_POST['due_date']) ? NULL : $_POST['due_date'];
   $priority = $_POST['priority'];
   $list_id = !empty($_POST['list_id']) ? $_POST['list_id'] : NULL;

   if (empty($title)) {
       $error_message = "Task Title cannot be empty.";
   } else {
       $sql_update = "UPDATE Task
                      SET Title = ?,
                          Description = ?,
                          DueDate = ?,
                          Priority = ?,
                          ListID = ?
                      WHERE TaskID = ? AND UserID = ?";

       $stmt_update = $conn->prepare($sql_update);
       $stmt_update->bind_param("ssssiii", $title, $description, $due_date, $priority, $list_id, $task_id, $current_user_id);

       if ($stmt_update->execute()) {
           header("Location: home.php?status=edited");
           exit;
       } else {
           $error_message = "Failed to update task. Please try again. " . $stmt_update->error;
       }
       $stmt_update->close();
   }
}

if ($_SERVER["REQUEST_METHOD"] != "POST" || !empty($error_message)) {
   if(isset($task) && !empty($error_message)) {
   } else {
       $stmt_get_task = $conn->prepare("SELECT * FROM Task WHERE TaskID = ? AND UserID = ?");
       $stmt_get_task->bind_param("ii", $task_id, $current_user_id);
       $stmt_get_task->execute();
       $task_result = $stmt_get_task->get_result();


       if ($task_result->num_rows == 1) {
           $task = $task_result->fetch_assoc();
       } else {
           echo "<!DOCTYPE html><html><head><title>Error</title><link rel='stylesheet' href='style.css'></head><body>";
           echo "<div class='container auth-container'><h2 class='text-danger'>Error</h2><p>Task not found or you do not have permission to edit it.</p><a href='home.php' class='btn btn-secondary'>Go Back Home</a></div>";
           echo "</body></html>";
           $stmt_get_task->close();
           exit;
       }
       $stmt_get_task->close();
   }
}
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Edit Task - Todo App</title>
   <link rel="stylesheet" href="style.css">
   <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

<div class="container">
   <h2>Edit Task</h2>

   <?php if (!empty($error_message)): ?>
       <div class="alert alert-danger" style="color: red; background: #ffebee; border: 1px solid #dc3545; padding: 10px 15px; border-radius: var(--border-radius); margin-bottom: 15px; text-align: center;">
           <?php echo htmlspecialchars($error_message); ?>
       </div>
   <?php endif; ?>

   <form action="edit_task.php?id=<?php echo $task_id; ?>" method="POST">
       <div>
           <label for="title">Task Title *</label>
           <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($task['Title']); ?>" required>
       </div>

       <div>
           <label for="description">Description</label>
           <textarea id="description" name="description"><?php echo htmlspecialchars($task['Description']); ?></textarea>
       </div>

       <div>
           <label for="list_id">Assign to List</label>
           <select id="list_id" name="list_id">
               <option value="">-- No List --</option>
               <?php
               foreach ($user_lists as $list):
                   $is_selected = ($task['ListID'] == $list['ListID']) ? 'selected' : '';
                   ?>
                   <option value="<?php echo $list['ListID']; ?>" <?php echo $is_selected; ?>>
                       <?php echo htmlspecialchars($list['ListName']); ?>
                   </option>
               <?php endforeach; ?>
           </select>
       </div>

       <div>
           <label for="due_date">Due Date</label>
           <input type="date" id="due_date" name="due_date" value="<?php echo htmlspecialchars($task['DueDate']); ?>">
       </div>

       <div>
           <label for="priority">Priority</label>
           <select id="priority" name="priority" required>
               <!-- Kiểm tra $task['Priority'] để 'selected' đúng option -->
               <option value="low" <?php echo ($task['Priority'] == 'low') ? 'selected' : ''; ?>>Low</option>
               <option value="medium" <?php echo ($task['Priority'] == 'medium') ? 'selected' : ''; ?>>Medium</option>
               <option value="high" <?php echo ($task['Priority'] == 'high') ? 'selected' : ''; ?>>High</option>
           </select>
       </div>

       <div class="button-group">
           <button type="submit" class="btn">Save Changes</button>
           <a href="home.php" class="btn btn-secondary">Cancel</a>
       </div>
   </form>
</div>

</body>
</html>



