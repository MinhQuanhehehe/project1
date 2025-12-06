CREATE TABLE ListShare (
    ShareID INT PRIMARY KEY AUTO_INCREMENT,
    ListID INT NOT NULL,
    SharedWithUserID INT NOT NULL,
    Permission ENUM('view', 'edit') NOT NULL,
    SharedAt DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (ListID) REFERENCES List(ListID) ON DELETE CASCADE, 
    FOREIGN KEY (SharedWithUserID) REFERENCES User(UserID) ON DELETE CASCADE, 

    UNIQUE KEY unique_list_user (ListID, SharedWithUserID)
);
