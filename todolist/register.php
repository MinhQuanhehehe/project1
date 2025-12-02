<?php
include 'db_connect.php';
$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($username === '' || $password === '' || $confirm_password === '') {
        $error = "Please enter username and password.";
    } elseif ($password !== $confirm_password) {
        $error = "Password confirmation does not match.";
    } else {
        // Check username exists
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
                // Registration success -> redirect to login
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
    // close connection if you won't reuse it later on this request
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Register - Todo App</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container auth-container">
  <form action="register.php" method="POST" autocomplete="off">
    <h2>Register</h2>

    <?php if(!empty($error)): ?>
      <p style="color: red; text-align: center;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <div>
      <label for="username">Username</label>
      <input type="text" id="username" name="username"
             value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
             required>
    </div>

    <div>
      <label for="password">Password</label>
      <!-- password fields: do not repopulate for security -->
      <input type="password" id="password" name="password" required>
    </div>

    <div>
      <label for="confirm_password">Confirm Password</label>
      <input type="password" id="confirm_password" name="confirm_password" required>
    </div>
      <button type="submit" class="btn">Register</button>
      <p class="text-center mt-1">
          Already have an account? <a href="login.php">Login here</a>
      </p>
  </form>
</div>
</body>
</html>
