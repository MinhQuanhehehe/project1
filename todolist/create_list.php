<?php
global $conn;
include 'db_connect.php'; // Đã bao gồm session_start()


// Bảo vệ trang: Nếu chưa đăng nhập, chuyển về trang login
if (!isset($_SESSION['UserID'])) {
   header("Location: login.php");
   exit;
}


$current_user_id = $_SESSION['UserID'];
$error = '';


// Xử lý khi user submit form (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
   $list_name = trim($_POST['list_name']); // Dùng trim() để bỏ dấu cách thừa


   if (empty($list_name)) {
       $error = "List name is required.";
   } else {
       // 1. Kiểm tra xem tên list này đã tồn tại cho user này chưa
       $stmt_check = $conn->prepare("SELECT ListID FROM List WHERE UserID = ? AND ListName = ?");
       $stmt_check->bind_param("is", $current_user_id, $list_name);
       $stmt_check->execute();
       $result_check = $stmt_check->get_result();


       if ($result_check->num_rows > 0) {
           $error = "A list with this name already exists.";
       } else {
           // 2. Thêm list mới vào CSDL
           $stmt_insert = $conn->prepare("INSERT INTO List (UserID, ListName) VALUES (?, ?)");
           $stmt_insert->bind_param("is", $current_user_id, $list_name);


           if ($stmt_insert->execute()) {
               // Tạo xong, quay về trang chủ (có thể thêm ?list_created=1 để báo thành công)
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
<!-- Dùng class 'auth-container' cho form nhỏ và đẹp -->
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

