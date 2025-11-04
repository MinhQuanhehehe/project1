<?php
global $conn;
include 'db_connect.php';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT UserID, Password FROM User WHERE Username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['Password'])) {
            $_SESSION['UserID'] = $user['UserID'];
            $_SESSION['Username'] = $username;

            header("Location: home.php");
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Invalid username or password.";
    }
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Todo App</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container auth-container">
    <form action="login.php" method="POST">
        <h2>Login</h2>

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
        <button type="submit" class="btn">Login</button>
        <p class="text-center mt-1">
            Don't have an account? <a href="register.php">Register here</a>
        </p>
    </form>
</div>
</body>
</html>
