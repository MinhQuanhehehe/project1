DROP DATABASE IF EXISTS todo_app_db;
CREATE DATABASE IF NOT EXISTS todo_app_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE todo_app_db;

CREATE TABLE Users (
                       user_id INT AUTO_INCREMENT PRIMARY KEY,
                       username VARCHAR(50) NOT NULL UNIQUE,
                       email VARCHAR(100) NULL UNIQUE,
                       password_hash VARCHAR(255) NOT NULL,
                       role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
                       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Lists (
                       list_id INT AUTO_INCREMENT PRIMARY KEY,
                       user_id INT NOT NULL,
                       list_name VARCHAR(100) NOT NULL,
                       color_code VARCHAR(7) DEFAULT '#007bff',
                       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                       FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

CREATE TABLE Tags (
                      tag_id INT AUTO_INCREMENT PRIMARY KEY,
                      user_id INT NOT NULL,
                      tag_name VARCHAR(50) NOT NULL,
                      color_code VARCHAR(7) DEFAULT '#6c757d',
                      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                      FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

CREATE TABLE Tasks (
                       task_id INT AUTO_INCREMENT PRIMARY KEY,
                       user_id INT NOT NULL,
                       list_id INT NULL,
                       title VARCHAR(255) NOT NULL,
                       description TEXT,
                       due_date DATETIME NULL,

                       is_important TINYINT(1) DEFAULT 0,
                       is_urgent TINYINT(1) DEFAULT 0,

                       status ENUM('pending', 'in_progress', 'completed', 'canceled') DEFAULT 'pending',
                       completed_at DATETIME NULL,

                       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                       updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                       FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
                       FOREIGN KEY (list_id) REFERENCES Lists(list_id) ON DELETE SET NULL
);

CREATE TABLE TaskTags (
                          task_id INT NOT NULL,
                          tag_id INT NOT NULL,
                          PRIMARY KEY (task_id, tag_id),
                          FOREIGN KEY (task_id) REFERENCES Tasks(task_id) ON DELETE CASCADE,
                          FOREIGN KEY (tag_id) REFERENCES Tags(tag_id) ON DELETE CASCADE
);

CREATE TABLE SubTasks (
                          subtask_id INT AUTO_INCREMENT PRIMARY KEY,
                          task_id INT NOT NULL,
                          title VARCHAR(255) NOT NULL,
                          is_completed TINYINT(1) DEFAULT 0,
                          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                          FOREIGN KEY (task_id) REFERENCES Tasks(task_id) ON DELETE CASCADE
);

CREATE TABLE ActivityLogs (
                              log_id INT AUTO_INCREMENT PRIMARY KEY,
                              user_id INT NOT NULL,
                              action_type VARCHAR(50) NOT NULL,
                              target_table VARCHAR(50) NOT NULL,
                              target_id INT NOT NULL,
                              details TEXT,
                              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                              FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
);
INSERT INTO Users (username, password_hash, role)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');