<?php
global $conn;
include 'db_connect.php'; // Đã bao gồm session_start()


// Bảo vệ trang: Nếu chưa đăng nhập, chuyển về trang login
if (!isset($_SESSION['UserID'])) {
   header("Location: login.php");
   exit;
}


// Lấy thông tin user
$current_user_id = $_SESSION['UserID'];
$username = $_SESSION['Username'];


// LẤY DỮ LIỆU BỘ LỌC (LISTS)
$stmt_lists = $conn->prepare("SELECT ListID, ListName FROM List WHERE UserID = ? ORDER BY ListName");
$stmt_lists->bind_param("i", $current_user_id);
$stmt_lists->execute();
$lists_data = $stmt_lists->get_result();


// XỬ LÝ LOGIC LỌC VÀ TÌM KIẾM
$page_title = "Your Tasks";
$search_query = isset($_GET['search_query']) ? trim($_GET['search_query']) : '';
$list_filter_id = isset($_GET['list_id']) ? $_GET['list_id'] : null;


// Mảng cho các tham số bind_param (để xử lý động)
$params = [$current_user_id]; // 'i'
$types = "i";
$where_clauses = "WHERE t.UserID = ?";


// 1. Xử lý Lọc theo List
if ($list_filter_id !== null) {
   if ($list_filter_id == 'none') {
       $where_clauses .= " AND t.ListID IS NULL";
       $page_title = "Tasks (No List)";
   } else {
       $where_clauses .= " AND t.ListID = ?";
       $params[] = $list_filter_id; // 'i'
       $types .= "i";
       // Lấy tên List để làm tiêu đề
       $stmt_title = $conn->prepare("SELECT ListName FROM List WHERE ListID = ? AND UserID = ?");
       $stmt_title->bind_param("ii", $list_filter_id, $current_user_id);
       $stmt_title->execute();
       $list_title_result = $stmt_title->get_result();
       if($list_title_row = $list_title_result->fetch_assoc()) {
           $page_title = "Tasks: " . htmlspecialchars($list_title_row['ListName']);
       }
       $stmt_title->close();
   }
}


// 2. Xử lý Tìm kiếm
if (!empty($search_query)) {
   $where_clauses .= " AND (t.Title LIKE ? OR t.Description LIKE ?)";
   $like_query = "%" . $search_query . "%";
   $params[] = $like_query; // 's'
   $params[] = $like_query; // 's'
   $types .= "ss";
   $page_title = "Search Results";
}


// 3. Xây dựng câu SQL cuối cùng
// Dùng LEFT JOIN để lấy tên List (nếu có)
$sql = "SELECT t.*, l.ListName
       FROM Task t
       LEFT JOIN List l ON t.ListID = l.ListID
       $where_clauses
       ORDER BY t.CreatedAt DESC";


$stmt_tasks = $conn->prepare($sql);


// 4. Bind các tham số đã thu thập
if (!empty($params)) {
   $stmt_tasks->bind_param($types, ...$params);
}


// 5. Lấy kết quả Task
$stmt_tasks->execute();
$tasks_result = $stmt_tasks->get_result();
?>


   <!DOCTYPE html>
   <html lang="en">
   <head>
       <meta charset="UTF-8">
       <meta name="viewport" content="width=device-width, initial-scale=1.0">
       <title>Home - Todo App</title>
       <link rel="stylesheet" href="style.css">
   </head>
   <body>
   <div class="container">
       <!-- PHẦN HEADER CHUNG -->
       <div class="header">
           <h2>Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
           <a href="logout.php" class="btn btn-secondary">Logout</a>
       </div>


       <!-- PHẦN ĐIỀU KHIỂN (TẠO MỚI) -->
       <div class="task-controls">
           <a href="create_task.php" class="btn">Create New Task</a>
           <a href="create_list.php" class="btn btn-secondary">Create New List</a>
       </div>


       <!-- THANH TÌM KIẾM -->
       <div class="search-bar">
           <form action="home.php" method="GET">
               <!-- Giữ lại list_id nếu đang lọc -->
               <?php if ($list_filter_id !== null): ?>
                   <input type="hidden" name="list_id" value="<?php echo htmlspecialchars($list_filter_id); ?>">
               <?php endif; ?>


               <input type="text" name="search_query" placeholder="Search tasks..." value="<?php echo htmlspecialchars($search_query); ?>">
               <button type="submit" class="btn">Search</button>
           </form>
       </div>


       <!-- BỘ LỌC LIST -->
       <div class="list-filter-container">
           <h4 class="list-filter-title">My Lists</h4>
           <div class="list-filter-links">
               <a href="home.php" class="list-filter-item <?php echo ($list_filter_id === null) ? 'active' : ''; ?>">All Tasks</a>
               <a href="home.php?list_id=none" class="list-filter-item <?php echo ($list_filter_id === 'none') ? 'active' : ''; ?>">Tasks (No List)</a>


               <?php while($list = $lists_data->fetch_assoc()): ?>
                   <a href="home.php?list_id=<?php echo $list['ListID']; ?>"
                      class="list-filter-item <?php echo ($list_filter_id == $list['ListID']) ? 'active' : ''; ?>">
                       <?php echo htmlspecialchars($list['ListName']); ?>
                   </a>
               <?php endwhile; ?>
           </div>
       </div>


       <!-- DANH SÁCH TASK -->
       <h3><?php echo $page_title; ?></h3>
       <div class="task-list">
           <?php if ($tasks_result->num_rows == 0): ?>
               <?php if(!empty($search_query)): ?>
                   <p>No tasks found matching your search.</p>
               <?php else: ?>
                   <p>You have no tasks here. Click "Create New Task" to add one!</p>
               <?php endif; ?>
           <?php else: ?>
               <?php while($task = $tasks_result->fetch_assoc()): ?>
                   <div class="task-item priority-<?php echo htmlspecialchars($task['Priority']); ?>">


                       <a href="task_detail.php?id=<?php echo $task['TaskID']; ?>" class="task-title">
                           <?php echo htmlspecialchars($task['Title']); ?>
                       </a>


                       <div class="task-meta">
                           <!-- Hiển thị tên List nếu Task có List -->
                           <?php if(!empty($task['ListName'])): ?>
                               <span class="task-list-badge" title="List: <?php echo htmlspecialchars($task['ListName']); ?>">
                                   <?php echo htmlspecialchars($task['ListName']); ?>
                               </span>
                           <?php endif; ?>


                           <!-- Hiển thị ngày hết hạn nếu có -->
                           <?php if(!empty($task['DueDate'])): ?>
                               <span class="task-due-date">
                                   Due: <?php echo date("d/m/Y", strtotime($task['DueDate'])); ?>
                               </span>
                           <?php endif; ?>
                       </div>


                       <div class="task-actions">
                           <a href="edit_task.php?id=<?php echo $task['TaskID']; ?>" class="btn btn-edit">Edit</a>
                           <a href="delete_task.php?id=<?php echo $task['TaskID']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this task?');">Delete</a>
                       </div>
                   </div>
               <?php endwhile; ?>
           <?php endif; ?>
       </div>
   </div>
   </body>
   </html>
<?php
// Đóng các kết nối
$stmt_lists->close();
$stmt_tasks->close();
$conn->close();
?>

