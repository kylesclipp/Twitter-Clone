<?php
require 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];


    if (empty($username) || empty($password) || empty($confirm_password)) {
        echo "All fields are required!";
    } elseif ($password !== $confirm_password) {
        echo "Passwords do not match!";
    } else {

        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            echo "Username already taken!";
        } else {

            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $username, $hashed_password);

            if ($stmt->execute()) {
                echo "Registration successful!";
                header("Location: login.php"); 
                exit();
            } else {
                echo "Error: " . $stmt->error;
            }
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
</head>
<body>
    <h2>Register</h2>
    <form method="POST" action="register.php">
        Username: <input type="text" name="username" required><br>
        Password: <input type="password" name="password" required><br>
        Confirm Password: <input type="password" name="confirm_password" required><br>
        <button type="submit">Register</button>
    </form>
</body>
</html>
