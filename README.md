# Project 1: Todo App (version 3)

## 1. Tổng quan
Một ứng dụng web quản lý công việc toàn diện, hỗ trợ phân quyền (Admin/User), quản lý thời gian theo Ma trận Eisenhower, theo dõi tiến độ chi tiết và nhật ký hoạt động hệ thống.

**Công nghệ sử dụng:**
- **Backend:** PHP (Native)
- **Database:** MySQL
- **Frontend:** HTML5, CSS3 (Variables, Flexbox), JavaScript
- **Thư viện:** Chart.js (Biểu đồ thống kê), FontAwesome (Icons)

---

## 2. Cơ sở dữ liệu: `todo_app_db`

### Cấu trúc bảng

#### **Bảng `Users`**
| Trường | Kiểu dữ liệu | Mô tả |
|--------|--------------|------|
| `user_id` | INT (PK) | ID định danh duy nhất |
| `username` | VARCHAR (UNIQUE) | Tên đăng nhập |
| `email` | VARCHAR (UNIQUE) | Email người dùng |
| `password_hash` | VARCHAR | Mật khẩu (đã mã hóa) |
| `role` | ENUM('user', 'admin') | Phân quyền hệ thống |
| `created_at` | TIMESTAMP | Ngày tạo tài khoản |

#### **Bảng `Lists` (Danh mục)**
| Trường | Kiểu dữ liệu | Mô tả |
|--------|--------------|------|
| `list_id` | INT (PK) | ID danh sách |
| `user_id` | INT (FK) | Liên kết `Users(user_id)` |
| `list_name` | VARCHAR | Tên danh sách (VD: "Work", "Personal") |
| `color_code` | VARCHAR | Mã màu hiển thị (Hex code) |

#### **Bảng `Tags` (Nhãn)**
| Trường | Kiểu dữ liệu | Mô tả |
|--------|--------------|------|
| `tag_id` | INT (PK) | ID nhãn |
| `user_id` | INT (FK) | Liên kết `Users(user_id)` |
| `tag_name` | VARCHAR | Tên nhãn (VD: "Bug", "Feature") |
| `color_code` | VARCHAR | Mã màu nhãn |

#### **Bảng `Tasks` (Công việc)**
| Trường | Kiểu dữ liệu | Mô tả |
|--------|--------------|------|
| `task_id` | INT (PK) | ID công việc |
| `user_id` | INT (FK) | Chủ sở hữu |
| `list_id` | INT (FK) | Thuộc danh sách nào |
| `title` | VARCHAR | Tiêu đề công việc |
| `description` | TEXT | Mô tả chi tiết |
| `due_date` | DATETIME | Hạn hoàn thành |
| `is_important` | TINYINT(1) | Ma trận Eisenhower: Quan trọng |
| `is_urgent` | TINYINT(1) | Ma trận Eisenhower: Khẩn cấp |
| `status` | ENUM | Trạng thái: `pending`, `in_progress`, `completed`, `canceled` |
| `completed_at` | DATETIME | Thời gian hoàn thành |

#### **Bảng `SubTasks` (Checklist)**
| Trường | Kiểu dữ liệu | Mô tả |
|--------|--------------|------|
| `subtask_id` | INT (PK) | ID bước nhỏ |
| `task_id` | INT (FK) | Thuộc Task nào |
| `title` | VARCHAR | Nội dung bước thực hiện |
| `is_completed` | TINYINT(1) | Trạng thái hoàn thành (0/1) |

#### **Bảng `ActivityLogs` (Nhật ký hệ thống)**
| Trường | Kiểu dữ liệu | Mô tả |
|--------|--------------|------|
| `log_id` | INT (PK) | ID log |
| `user_id` | INT (FK) | Người thực hiện hành động |
| `action_type` | VARCHAR | Loại hành động (CREATE, UPDATE, DELETE, LOGIN...) |
| `target_table` | VARCHAR | Bảng bị tác động |
| `details` | TEXT | Chi tiết hành động |

---

### Quan hệ dữ liệu
- **Users - Lists/Tags:** 1-n (Một user tạo nhiều list/tag).
- **Users - ActivityLogs:** 1-n (Ghi lại mọi hành động của user).
- **Tasks - Tags:** n-n (Thông qua bảng trung gian `TaskTags`).
- **Tasks - SubTasks:** 1-n (Một task có nhiều bước thực hiện).

---

## 3. Chức năng chi tiết

### a. Phân hệ Admin (Quản trị viên)
- **Dashboard Thống kê:**
    - Biểu đồ tròn (Chart.js) thống kê trạng thái công việc toàn hệ thống.
    - Xem tổng số Users, Logs và phiên bản PHP server.
    - Xem danh sách người dùng mới nhất.
- **Quản lý Người dùng:**
    - Tìm kiếm user theo tên/email.
    - Thay đổi quyền (User ↔ Admin).
    - Reset mật khẩu user về mặc định.
    - Xóa user (Kéo theo xóa toàn bộ dữ liệu task liên quan).
- **Nhật ký Hoạt động:**
    - Xem lịch sử thao tác hệ thống (Ai, làm gì, lúc nào).
    - Bộ lọc Logs theo: User, Loại hành động, Ngày tháng.
    - Chức năng dọn dẹp logs cũ (>30 ngày).

### b. Phân hệ User (Người dùng)

#### **1. Authentication (Xác thực)**
- Đăng ký/Đăng nhập bảo mật (Password Hashing).
- Đổi mật khẩu.
- Ghi log khi đăng nhập/đăng ký thành công.

#### **2. Dashboard & Bộ lọc Nâng cao (`home.php`)**
- **Hiển thị thông minh:**
    - Cảnh báo task quá hạn (Overdue).
    - Phân biệt các status khác nhau của task.
- **Bộ lọc đa tiêu chí (Filter Bar):**
    - Tìm kiếm theo từ khóa.
    - Lọc theo List (Danh sách) hoặc Inbox.
    - Lọc theo Tag (Nhãn).
    - Lọc theo **Ma trận Eisenhower** (Do First, Schedule, Delegate, Don't Do).
    - Lọc theo khoảng thời gian (From Date - To Date).

#### **3. Quản lý Công việc (Tasks)**
- **CRUD Task:** Tạo, Sửa, Xóa, Xem chi tiết.
- **Trạng thái động:**
    - Chuyển trạng thái nhanh: Pending ↔ In Progress ↔ Completed.
    - Logic tự động:
      - Tích vào Subtask → Task cha chuyển sang `In Progress`.
      - Tích vào Complete Task → Tất cả Subtask chuyển thành Completed
      - Khi Complete hoặc Cancel → Không sửa được Subtask
- **Subtasks (Checklist):**
    - Chia nhỏ công việc.
    - Thanh tiến độ (Progress Bar) % hoàn thành.
- **Gắn Tags & List:** Phân loại công việc bằng màu sắc trực quan.

#### **4. Cá nhân hóa**
- **Quản lý Lists:** Tạo/Sửa/Xóa danh mục công việc với màu tùy chọn.
- **Quản lý Tags:** Tạo bộ nhãn dán riêng (Bug, Feature, Urgent...) với mã màu Hex.

---

## 4. Bảo mật & Kỹ thuật
- **Chống SQL Injection:** Sử dụng 100% `Prepared Statements` cho mọi truy vấn database.
- **Phân quyền truy cập:**
    - User thường không thể truy cập trang Admin.
    - User A không thể xem/sửa/xóa Task của User B.
- **Audit Trail:** Mọi hành động quan trọng (Thêm/Sửa/Xóa dữ liệu) đều được ghi lại vào bảng `ActivityLogs` để tra cứu.
- **Database Integrity:** Sử dụng khóa ngoại (`Foreign Key`) với cơ chế `ON DELETE CASCADE` để đảm bảo dữ liệu không bị rác.
