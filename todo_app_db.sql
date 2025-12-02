DROP DATABASE IF EXISTS todo_app_db;
CREATE DATABASE IF NOT EXISTS todo_app_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE todo_app_db;

CREATE TABLE IF NOT EXISTS User (
                                   UserID INT AUTO_INCREMENT PRIMARY KEY,
                                   Username VARCHAR(50) NOT NULL UNIQUE,
                                   Password VARCHAR(255) NOT NULL,
                                   FullName VARCHAR(100) NULL,
                                   Age INT NULL,
                                   Role  ENUM('user', 'admin') NOT NULL DEFAULT 'user',
                                   Status ENUM('active', 'pending_delete', 'deleted') NOT NULL DEFAULT 'active',
                                   CreateAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
);

CREATE TABLE IF NOT EXISTS List (
                                   ListID INT AUTO_INCREMENT PRIMARY KEY,
                                   UserID INT NOT NULL,
                                   ListName VARCHAR(100) NOT NULL,
                                   CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                   FOREIGN KEY (UserID) REFERENCES User(UserID) ON DELETE CASCADE
);


CREATE TABLE IF NOT EXISTS Task (
                                   TaskID INT AUTO_INCREMENT PRIMARY KEY,
                                   UserID INT NOT NULL,
                                   ListID INT NULL DEFAULT NULL,
                                   Title VARCHAR(255) NOT NULL,
                                   Description TEXT,
                                   DueDate DATE,
                                   Priority ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
                                   IsCompleted TINYINT(1) NOT NULL DEFAULT 0,
                                   CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                   FOREIGN KEY (UserID) REFERENCES User(UserID) ON DELETE CASCADE,
                                   FOREIGN KEY (ListID) REFERENCES List(ListID) ON DELETE SET NULL
);
INSERT INTO user
(Username, Password, FullName, Age, Role, Status)
VALUES
    ('ADMIN', '$2y$10$/OTlGPL.OHpNNgyrBjHeQOc1Pbr.nYFEEr6f4/ZBMelYd887UQXDG', 'administrator', 20, 'admin', 'active')

