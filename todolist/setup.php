<?php
include 'config/db_connect.php';

echo "<h2>System Setup...</h2>";

$default_user = 'admin';
$default_pass = '123456';
$default_email = 'admin@todoapp.local';
$role = 'admin';

$check = $conn->prepare("SELECT user_id FROM Users WHERE username = ?");
$check->bind_param("s", $default_user);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    echo "<p style='color:orange;'>Admin account exist ('$default_user') skip creating admin account step.</p>";
} else {
    $hashed_pass = password_hash($default_pass, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO Users (username, password_hash, role, email) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $default_user, $hashed_pass, $role, $default_email);

    if ($stmt->execute()) {
        echo "<p style='color:green;'>Admin account was created.</p>";
        echo "<ul>
                <li>Username: <strong>$default_user</strong></li>
                <li>Password: <strong>$default_pass</strong></li>
              </ul>";

        // Log
        $new_id = $conn->insert_id;
        $conn->query("INSERT INTO ActivityLogs (user_id, action_type, target_table, target_id, details) 
                      VALUES ($new_id, 'CREATE', 'Users', $new_id, 'System auto-generated Admin account')");
    } else {
        echo "<p style='color:red;'> Error occur: " . $stmt->error . "</p>";
    }
    $stmt->close();
}

$check->close();
$conn->close();

echo "<br><a href='auth/login.php'>Go to Login Page</a>";
?>