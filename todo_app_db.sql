-- Nên chạy lại CSDL từ đầu
DROP DATABASE IF EXISTS todo_app_db;
CREATE DATABASE IF NOT EXISTS todo_app_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE todo_app_db;


-- 1. Bảng User (Không thay đổi)
CREATE TABLE IF NOT EXISTS User (
                                   UserID INT AUTO_INCREMENT PRIMARY KEY,
                                   Username VARCHAR(50) NOT NULL UNIQUE,
                                   Password VARCHAR(255) NOT NULL
);


-- 2. Bảng List (Bảng MỚI)
-- Mỗi List cũng phải thuộc về một User
CREATE TABLE IF NOT EXISTS List (
                                   ListID INT AUTO_INCREMENT PRIMARY KEY,
                                   UserID INT NOT NULL,
                                   ListName VARCHAR(100) NOT NULL,
                                   CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,


   -- Khóa ngoại: Liên kết List với User sở hữu nó
   -- ON DELETE CASCADE: Nếu User bị xóa, tất cả List của họ cũng bị xóa.
                                   FOREIGN KEY (UserID) REFERENCES User(UserID) ON DELETE CASCADE
);


-- 3. Bảng Task (Đã SỬA ĐỔI)
CREATE TABLE IF NOT EXISTS Task (
                                   TaskID INT AUTO_INCREMENT PRIMARY KEY,
                                   UserID INT NOT NULL,


   -- CỘT MỚI: Cho phép giá trị NULL
   -- Đây là cột liên kết Task với List
                                   ListID INT NULL DEFAULT NULL,


                                   Title VARCHAR(255) NOT NULL,
                                   Description TEXT,
                                   DueDate DATE,
                                   Priority ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
                                   CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,


   -- Khóa ngoại: Liên kết Task với User (giữ nguyên)
   -- ON DELETE CASCADE: Nếu User bị xóa, tất cả Task của họ cũng bị xóa.
                                   FOREIGN KEY (UserID) REFERENCES User(UserID) ON DELETE CASCADE,


   -- Khóa ngoại MỚI: Liên kết Task với List
   -- ON DELETE SET NULL: Nếu List bị xóa, Task không bị xóa,
   --                    chỉ cột ListID của Task sẽ bị set thành NULL.
                                   FOREIGN KEY (ListID) REFERENCES List(ListID) ON DELETE SET NULL
);

