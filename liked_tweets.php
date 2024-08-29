<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];


$tweets_stmt = $conn->prepare("
    SELECT t.id, t.content, t.image, t.created_at, COUNT(l.id) AS like_count
    FROM tweets t
    JOIN likes l ON t.id = l.tweet_id
    WHERE l.user_id = ?
    GROUP BY t.id
    ORDER BY t.created_at DESC
");
$tweets_stmt->bind_param("i", $user_id);
$tweets_stmt->execute();
$tweets_result = $tweets_stmt->get_result();
$tweets_stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Liked Tweets</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 0;
            background-color: #f4f4f4;
        }
        .tweet-box {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 10px;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .tweet-box img {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
        }
        .tweet-header {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .tweet-content {
            margin-bottom: 10px;
        }
        .tweet-footer {
            color: #777;
            font-size: 0.9em;
        }
        a {
            color: #3498db;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h2>Liked Tweets</h2>

    <?php if ($tweets_result->num_rows > 0): ?>
        <?php while ($tweet = $tweets_result->fetch_assoc()): ?>
            <div class="tweet-box">
                <div class="tweet-header">Tweet by User</div>
                <div class="tweet-content"><p><?php echo htmlspecialchars($tweet['content']); ?></p></div>
                <?php if ($tweet['image']): ?>
                    <img src="<?php echo htmlspecialchars($tweet['image']); ?>" alt="Tweet Image">
                <?php endif; ?>
                <div class="tweet-footer">
                    <small><?php echo htmlspecialchars($tweet['created_at']); ?></small> |
                    <span>Likes: <?php echo htmlspecialchars($tweet['like_count']); ?></span>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>You haven't liked any tweets yet.</p>
    <?php endif; ?>

    <a href="index.php">Back to Home</a>
</body>
</html>
