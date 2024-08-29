<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$followee_id = isset($_POST['followee_id']) ? $_POST['followee_id'] : '';

if (!$followee_id) {
    echo "No user specified.";
    exit();
}


$stmt = $conn->prepare("DELETE FROM follows WHERE follower_id = ? AND followee_id = ?");
$stmt->bind_param("ii", $user_id, $followee_id);
$stmt->execute();
$stmt->close();


header("Location: profile.php?username=" . urlencode(get_username_by_id($followee_id)));
exit();

function get_username_by_id($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($username);
    $stmt->fetch();
    $stmt->close();
    return $username;
}
?>
