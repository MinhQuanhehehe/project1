# Project 1: Ứng dụng Quản lý Công việc (Todo App)

## 1. Tổng quan

Một ứng dụng web cho phép người dùng đăng ký, đăng nhập và quản lý các công việc (task) cá nhân.
**Công nghệ:** PHP (cho logic server) và MySQL (cho lưu trữ dữ liệu).

---

## 2. Database: `todo_app_db`

### Cấu trúc bảng

* **Bảng `User`**
    * `UserID` (INT, PK): ID định danh duy nhất.
    * `Username` (VARCHAR, UNIQUE): Tên đăng nhập.
    * `Password` (VARCHAR): Mật khẩu (sẽ được lưu dưới dạng hash).

* **Bảng `Task`**
    * `TaskID` (INT, PK): ID định danh duy nhất.
    * `UserID` (INT, FK): Liên kết đến `User(UserID)`.
    * `Title` (VARCHAR): Tiêu đề công việc.
    * `Description` (TEXT): Mô tả chi tiết.
    * `DueDate` (DATE): Ngày hết hạn.
    * `Priority` (ENUM): Mức độ ưu tiên ('low', 'medium', 'high').

**Quan hệ:** Một `User` có nhiều `Task`. Nếu `User` bị xóa, `Task` của họ cũng bị xóa (`ON DELETE CASCADE`).

---

## 3. Chức Năng

### a. Nhóm Chức Năng Xác Thực (Authentication)

* **Đăng ký (`register.php`):**
    * Cho phép người dùng mới tạo tài khoản.
    * Yêu cầu `Username` (phải là duy nhất) và `Password`.
    * Mật khẩu phải được băm (hash) bằng `password_hash()` trước khi lưu.

* **Đăng nhập (`login.php`):**
    * Cho phép người dùng đã có tài khoản đăng nhập.
    * Xác thực mật khẩu bằng `password_verify()`.
    * Khi thành công, `UserID` và `Username` được lưu vào `$_SESSION` để duy trì trạng thái đăng nhập.

* **Đăng xuất (`logout.php`):**
    * Xóa toàn bộ dữ liệu trong `$_SESSION` của người dùng.
    * Chuyển hướng người dùng về trang đăng nhập.

### b. Nhóm Chức Năng Quản lý Công việc (Task Management)

* **Hiển thị Danh sách Task (`home.php`):**
    * Trang chính sau khi đăng nhập.
    * Hiển thị tất cả các `Task` mà người dùng hiện tại đã tạo.
    * Mỗi `Task` hiển thị `Title` và các nút **Edit/Delete**.

* **Tạo Task mới (`create_task.php`):**
    * Cung cấp một form để người dùng nhập thông tin `Task` mới.
    * Các trường bao gồm: `Title`, `Description`, `DueDate`, `Priority`.

* **Chỉnh sửa Task (`edit_task.php`):**
    * Cho phép người dùng cập nhật thông tin của một `Task` đã tồn tại.
    * Form sẽ được điền sẵn thông tin cũ của `Task`.

* **Xóa Task (`delete_task.php`):**
    * Xóa một `Task` khỏi cơ sở dữ liệu.
    * **Yêu cầu (UX):** Cần có một bước xác nhận (ví dụ: "Bạn có chắc chắn muốn xóa?") trước khi thực sự xóa.

* **Xem Chi tiết Task (`task_detail.php`):**
    * Hiển thị đầy đủ thông tin của một `Task` (bao gồm cả `Description` và `DueDate`).
    * Đây là trang mà người dùng được chuyển đến khi nhấp vào `Title` của một `Task`.

* **Tìm kiếm Task (`home.php`):**
    * Một thanh tìm kiếm cho phép người dùng lọc danh sách `Task` dựa trên `Title` hoặc `Description`.

### c. Nhóm Chức Năng Bảo Mật (Security)

* **Chống SQL Injection:**
    * Tất cả các truy vấn CSDL (`SELECT`, `INSERT`, `UPDATE`, `DELETE`) bắt buộc phải sử dụng **Prepared Statements** (`mysqli_prepare`, `bind_param`, `execute`).

* **Bảo vệ Phiên (Session Protection):**
    * Tất cả các trang quản lý `Task` (ví dụ: `home.php`, `create_task.php`...) phải kiểm tra `isset($_SESSION['UserID'])` ở đầu tệp.
    * Nếu người dùng chưa đăng nhập, họ sẽ bị chuyển hướng về `login.php`.

* **Bảo vệ Quyền sở hữu (Authorization):**
    * Khi Sửa (`edit_task.php`) hoặc Xóa (`delete_task.php`) một `Task`, hệ thống phải kiểm tra xem `Task` đó có thực sự thuộc về `UserID` đang đăng nhập hay không.
