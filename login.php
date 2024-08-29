<?php
require 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];


    if (empty($username) || empty($password)) {
        echo "All fields are required!";
    } else {

        $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($id, $hashed_password);
        $stmt->fetch();

        if ($stmt->num_rows > 0 && password_verify($password, $hashed_password)) {
            session_start();
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $username;
            header("Location: index.php"); 
            exit();
        } else {
            echo "Invalid username or password";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>
    <h2>Login</h2>
    <form method="POST" action="login.php">
        Username: <input type="text" name="username" required><br>
        Password: <input type="password" name="password" required><br>
        <button type="submit">Login</button>
    </form>
    <p>Don't have an account? <a href='register.php'>Register Here!</a>
</body>
</html>
