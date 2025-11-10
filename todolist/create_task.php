<?php
global $conn;
include 'db_connect.php';


if (!isset($_SESSION['UserID'])) {
   header("Location: login.php");
   exit;
}


$error = '';
$current_user_id = $_SESSION['UserID'];


$stmt_lists = $conn->prepare("SELECT ListID, ListName FROM List WHERE UserID = ? ORDER BY ListName");
$stmt_lists->bind_param("i", $current_user_id);
$stmt_lists->execute();
$lists_data = $stmt_lists->get_result();


if ($_SERVER["REQUEST_METHOD"] == "POST") {
   $title = $_POST['title'];
   $description = $_POST['description'];
   $due_date = $_POST['due_date'];
   $priority = $_POST['priority'];
   $list_id = !empty($_POST['list_id']) ? $_POST['list_id'] : NULL;


   if (empty($title)) {
       $error = "Title is required.";
   } else {
       $stmt = $conn->prepare("INSERT INTO Task (UserID, ListID, Title, Description, DueDate, Priority) VALUES (?, ?, ?, ?, ?, ?)");
       $stmt->bind_param("iissss", $current_user_id, $list_id, $title, $description, $due_date, $priority);
       if ($stmt->execute()) {
           header("Location: home.php");
           exit;
       } else {
           $error = "Error: " . $stmt->error;
       }
       $stmt->close();
   }
   $conn->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Create Task - Todo App</title>
   <link rel="stylesheet" href="style.css">
   <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<div class="container">
   <h2>Create New Task</h2>


   <?php if(!empty($error)): ?>
       <p style="color: red; text-align: center;"><?php echo $error; ?></p>
   <?php endif; ?>


   <form action="create_task.php" method="POST">
       <div>
           <label for="title">Title</label>
           <input type="text" id="title" name="title" required>
       </div>
       <div>
           <label for="description">Description</label>
           <textarea id="description" name="description"></textarea>
       </div>
       <div>
           <label for="list_id">Assign to List</label>
           <select id="list_id" name="list_id">
               <!-- Tùy chọn mặc định, value rỗng sẽ được PHP xử lý thành NULL -->
               <option value="">-- No List --</option>


               <?php while($list = $lists_data->fetch_assoc()): ?>
                   <option value="<?php echo $list['ListID']; ?>">
                       <?php echo htmlspecialchars($list['ListName']); ?>
                   </option>
               <?php endwhile; ?>
           </select>
       </div>
       <div>
           <label for="due_date">Due Date</label>
           <input type="date" id="due_date" name="due_date">
       </div>
       <div>
           <label for="priority">Priority</label>
           <select id="priority" name="priority">
               <option value="low">Low</option>
               <option value="medium" selected>Medium</option>
               <option value="high">High</option>
           </select>
       </div>
       <div style="display: flex; gap: 10px;">
           <button type="submit" class="btn">Create Task</button>
           <a href="home.php" class="btn btn-secondary">Cancel</a>
       </div>
   </form>
</div>
</body>
</html>

