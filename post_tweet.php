<?php
session_start();
require 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $content = $_POST['content'];
    $image = $_FILES['image'];


    $image_path = null;
    if ($image['error'] == UPLOAD_ERR_OK) {
        $target_dir = "uploads/";
        $image_path = $target_dir . basename($image["name"]);
        move_uploaded_file($image["tmp_name"], $image_path);
    }


    $stmt = $conn->prepare("INSERT INTO tweets (user_id, content, image) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $content, $image_path);

    if ($stmt->execute()) {
        header("Location: index.php"); 
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}
?>
