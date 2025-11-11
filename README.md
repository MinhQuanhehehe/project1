# Project 1: Ứng dụng Quản lý Công việc (version 2)

## 1. Tổng quan
Một ứng dụng web cho phép người dùng đăng ký, đăng nhập và quản lý các công việc cá nhân thông qua các danh sách công việc (Task List).

**Công nghệ sử dụng:**
- **PHP:** Xử lý logic phía server  
- **MySQL:** Lưu trữ dữ liệu

---

## 2. Cơ sở dữ liệu: `todo_app_db`

### Cấu trúc bảng

#### **Bảng `User`**
| Trường | Kiểu dữ liệu | Mô tả |
|--------|--------------|------|
| `UserID` | INT (PK) | ID định danh duy nhất |
| `Username` | VARCHAR (UNIQUE) | Tên đăng nhập |
| `Password` | VARCHAR | Mật khẩu (được lưu dưới dạng hash) |

---

#### **Bảng `List`**
| Trường | Kiểu dữ liệu | Mô tả |
|--------|--------------|------|
| `ListID` | INT (PK) | ID định danh danh sách |
| `UserID` | INT (FK) | Liên kết đến `User(UserID)` |
| `ListName` | VARCHAR | Tên danh sách công việc (VD: “Công việc học tập”, “Công việc cá nhân”) |
| `CreatedAt` | DATETIME | Ngày tạo danh sách |

---

#### **Bảng `Task`**
| Trường | Kiểu dữ liệu | Mô tả |
|--------|--------------|------|
| `TaskID` | INT (PK) | ID định danh duy nhất |
| `UserID` | INT (FK) | Liên kết đến `User(UserID)` |
| `ListID` | INT (FK) | Liên kết đến `List(ListID)` |
| `Title` | VARCHAR | Tiêu đề công việc |
| `Description` | TEXT | Mô tả chi tiết |
| `DueDate` | DATE | Ngày hết hạn |
| `Priority` | ENUM('low','medium','high') | Mức độ ưu tiên |
| `IsCompleted` | TINYINT(1) DEFAULT 0 | Status hoàn thành |

---

### Quan hệ giữa các bảng
- Một **User** có thể có nhiều **List**  
- Mỗi **List** chứa nhiều **Task**    
- Nếu **List** bị xóa → tất cả **Task** trong danh sách đó cũng bị xóa (`ON DELETE CASCADE`)

---

## 3. Chức năng

### a. Nhóm Chức Năng Xác Thực (Authentication)

#### **Đăng ký (`register.php`)**
- Cho phép người dùng mới tạo tài khoản  
- Yêu cầu **Username** (duy nhất) và **Password**  
- Mật khẩu được mã hóa bằng `password_hash()` trước khi lưu vào cơ sở dữ liệu  

#### **Đăng nhập (`login.php`)**
- Người dùng nhập thông tin đăng nhập  
- Xác thực bằng `password_verify()`  
- Khi đăng nhập thành công, lưu `UserID` và `Username` vào `$_SESSION` để duy trì trạng thái  

#### **Đăng xuất (`logout.php`)**
- Xóa toàn bộ dữ liệu trong `$_SESSION`  
- Chuyển hướng người dùng về trang `login.php`

---

### b. Nhóm Chức Năng Quản lý Danh sách & Công việc

#### **Quản lý Danh sách (List Management)**
- **Hiển thị danh sách các List (`home.php`)**  
  - Sau khi đăng nhập, hiển thị tất cả danh sách mà người dùng đã tạo  
  - Mỗi danh sách hiển thị tên và số lượng task trong đó  

- **Tạo danh sách mới (`create_list.php`)**  
  - Form nhập tên danh sách  

- **Sửa danh sách (`edit_list.php`)**  
  - Form nhập tên danh sách để sửa
 
- **Xóa danh sách (`delete_list.php`)**  
  - Window alert để xóa list
    
---

#### **Quản lý Công việc (Task Management)**
- **Hiển thị danh sách Task (`list_detail.php`)**  
  - Hiển thị tất cả task trong danh sách được chọn  
  - Mỗi task có các nút **Edit**, **Delete**, **View Detail** và 1 `checkbox`

- **Đánh dấu đã hoàn thành (`list_detail.php`)**  
  - Cho phép người dùng hoàn thành công việc khi bấm vào checkbox

- **Tạo Task mới (`create_task.php`)**  
  - Cho phép người dùng nhập **Title**, **Description**, **DueDate**, **Priority** và chọn danh sách chứa task  

- **Chỉnh sửa Task (`edit_task.php`)**  
  - Cập nhật thông tin task (form có dữ liệu cũ)  

- **Xóa Task (`delete_task.php`)**  
  - Xóa task khỏi cơ sở dữ liệu (yêu cầu xác nhận trước khi xóa)  

- **Xem chi tiết Task (`task_detail.php`)**  
  - Hiển thị đầy đủ thông tin của một task, bao gồm mô tả, hạn và mức ưu tiên  

- **Tìm kiếm Task (`home.php`)**  
  - Cho phép tìm kiếm task theo **Title** hoặc **Description**
  - Lọc các task theo mức độ ưu tiên.
  - Lọc các task theo khoảng thời gian.

---

### c. Nhóm Chức Năng Bảo Mật (Security)
- **Chống SQL Injection:**  
  - Mọi truy vấn sử dụng **Prepared Statements** (`mysqli_prepare`, `bind_param`, `execute`)  

- **Bảo vệ Phiên (Session Protection):**  
  - Các trang quản lý Task/List yêu cầu `isset($_SESSION['UserID'])`  
  - Nếu chưa đăng nhập → chuyển hướng về `login.php`  

- **Bảo vệ Quyền sở hữu (Authorization):**  
  - Chỉ chủ sở hữu List/Task mới có quyền sửa hoặc xóa  
  - Khi thao tác, hệ thống kiểm tra `UserID` hiện tại có trùng với `UserID` của List/Task hay không  

---





