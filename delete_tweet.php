<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$tweet_id = isset($_POST['tweet_id']) ? intval($_POST['tweet_id']) : 0;

if ($tweet_id > 0) {

    $check_stmt = $conn->prepare("SELECT user_id FROM tweets WHERE id = ?");
    $check_stmt->bind_param("i", $tweet_id);
    $check_stmt->execute();
    $check_stmt->bind_result($tweet_user_id);
    $check_stmt->fetch();
    $check_stmt->close();

    if ($tweet_user_id === $user_id) {

        $delete_stmt = $conn->prepare("DELETE FROM tweets WHERE id = ?");
        $delete_stmt->bind_param("i", $tweet_id);
        $delete_stmt->execute();
        $delete_stmt->close();
    }
}

header("Location: profile.php?username=" . urlencode($_SESSION['username']));
exit();
?>
