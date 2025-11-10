<?php
global $conn;
include 'db_connect.php';

if (!isset($_SESSION['UserID'])) {
   header("Location: login.php");
   exit;
}

$current_user_id = $_SESSION['UserID'];
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
   $list_name = trim($_POST['list_name']);

   if (empty($list_name)) {
       $error = "List name is required.";
   } else {
       $stmt_check = $conn->prepare("SELECT ListID FROM List WHERE UserID = ? AND ListName = ?");
       $stmt_check->bind_param("is", $current_user_id, $list_name);
       $stmt_check->execute();
       $result_check = $stmt_check->get_result();

       if ($result_check->num_rows > 0) {
           $error = "A list with this name already exists.";
       } else {
           $stmt_insert = $conn->prepare("INSERT INTO List (UserID, ListName) VALUES (?, ?)");
           $stmt_insert->bind_param("is", $current_user_id, $list_name);

           if ($stmt_insert->execute()) {
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
   <title>Create New List - Todo App</title>
   <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container auth-container">
   <form action="create_list.php" method="POST">
       <h2>Create New List</h2>

       <?php if(!empty($error)): ?>
           <p style="color: red; text-align: center;"><?php echo $error; ?></p>
       <?php endif; ?>

       <div>
           <label for="list_name">List Name</label>
           <input type="text" id="list_name" name="list_name" required>
       </div>

       <div class="button-group">
           <button type="submit" class="btn">Create List</button>
           <a href="home.php" class="btn btn-secondary">Cancel</a>
       </div>
   </form>
</div>
</body>
</html>

