<?php
global $conn;
include 'db_connect.php';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $fullname = trim($_POST['fullname']);
    $age = !empty($_POST['age']) ? (int)$_POST['age'] : NULL;

    if (empty($username) || empty($password)) {
        $error = "Username and Password are required.";
    } else {
        $stmt_check = $conn->prepare("SELECT UserID FROM User WHERE Username = ?");
        $stmt_check->bind_param("s", $username);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $error = "Username already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'user'; 
            $status = 'active'; 

            $stmt_insert = $conn->prepare("INSERT INTO User (Username, Password, FullName, Age, Role, Status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_insert->bind_param("ssssss", $username, $hashed_password, $fullname, $age, $role, $status);

            if ($stmt_insert->execute()) {
                header("Location: login.php?registered=1");
                exit;
            } else {
                $error = "Error creating account: " . $stmt_insert->error;
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Todo App</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container auth-container">
    <form action="register.php" method="POST">
        <h2>Register Account</h2>

        <?php if(!empty($error)): ?>
            <p style="color: red; text-align: center;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <div>
            <label for="username">Username *</label>
            <input type="text" id="username" name="username" required>
        </div>
        <div>
            <label for="password">Password *</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div>
            <label for="fullname">Full Name</label>
            <input type="text" id="fullname" name="fullname">
        </div>
        <div>
            <label for="age">Age</label>
            <input type="number" id="age" name="age">
        </div>
        <button type="submit" class="btn">Register</button>
        <p class="text-center mt-1">
            Already have an account? <a href="login.php">Login here</a>
        </p>
    </form>
</div>
</body>
</html>
