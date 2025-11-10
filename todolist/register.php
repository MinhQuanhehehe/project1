<?php
global $conn;
include 'db_connect.php';
$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Username and password are required.";
    } else {
        $stmt_check = $conn->prepare("SELECT UserID FROM User WHERE Username = ?");
        $stmt_check->bind_param("s", $username);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $error = "Username already exists. Please choose another one.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt_insert = $conn->prepare("INSERT INTO User (Username, Password) VALUES (?, ?)");
            $stmt_insert->bind_param("ss", $username, $hashed_password);

            if ($stmt_insert->execute()) {
                echo "<script>
                        alert('Registration successful! You will be redirected to the login page.');
                        window.location.href = 'login.php';
                      </script>";
                exit;
            } else {
                $error = "Error: " . $stmt_insert->error;
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
    $conn->close();
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
        <h2>Register</h2>

        <?php if(!empty($error)): ?>
            <p style="color: red; text-align: center;"><?php echo $error; ?></p>
        <?php endif; ?>

        <div>
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>
        </div>
        <div>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn">Register</button>
        <p class="text-center mt-1">
            Already have an account? <a href="login.php">Login here</a>
        </p>
    </form>
</div>
</body>
</html>
