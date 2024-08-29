<?php
session_start();
require 'config.php';

// redirect to login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// get username
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username);
$stmt->fetch();
$stmt->close();

// get tweets
$tweets_stmt = $conn->prepare("
    SELECT tweets.id, tweets.content, tweets.image, tweets.created_at, users.username, COUNT(l.id) AS like_count
    FROM tweets
    JOIN users ON tweets.user_id = users.id
    LEFT JOIN likes l ON tweets.id = l.tweet_id
    JOIN follows ON follows.followee_id = tweets.user_id
    WHERE follows.follower_id = ?
    GROUP BY tweets.id
    ORDER BY tweets.created_at DESC
");
$tweets_stmt->bind_param("i", $user_id);
$tweets_stmt->execute();
$tweets_result = $tweets_stmt->get_result();

// get followed
$followed_users_stmt = $conn->prepare("
    SELECT users.id, users.username
    FROM users
    JOIN follows ON follows.followee_id = users.id
    WHERE follows.follower_id = ?
");
$followed_users_stmt->bind_param("i", $user_id);
$followed_users_stmt->execute();
$followed_users_result = $followed_users_stmt->get_result();

// like and unlike feature
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tweet_id'])) {
    $tweet_id = $_POST['tweet_id'];

    // has user like?
    $like_check_stmt = $conn->prepare("SELECT * FROM likes WHERE user_id = ? AND tweet_id = ?");
    $like_check_stmt->bind_param("ii", $user_id, $tweet_id);
    $like_check_stmt->execute();
    $liked = $like_check_stmt->fetch();
    $like_check_stmt->close();

    // like or unlike!
    if ($liked) {
        $unlike_stmt = $conn->prepare("DELETE FROM likes WHERE user_id = ? AND tweet_id = ?");
        $unlike_stmt->bind_param("ii", $user_id, $tweet_id);
        $unlike_stmt->execute();
        $unlike_stmt->close();
    } else {
        $like_stmt = $conn->prepare("INSERT INTO likes (user_id, tweet_id) VALUES (?, ?)");
        $like_stmt->bind_param("ii", $user_id, $tweet_id);
        $like_stmt->execute();
        $like_stmt->close();
    }

    // back home!
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Home</title>
    <style>
        /* basic stuff */
        body {
            font-family: Arial, sans-serif;
            background-color: #e6ecf0;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 600px;
            margin: 0 auto;
            background-color: #fff;
            border: 1px solid #ccc;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 1px solid #e1e8ed;
            margin-bottom: 20px;
            position: relative;
        }

        header h1 {
            font-size: 24px;
            color: #1da1f2;
            margin: 0;
        }

        nav {
            position: absolute;
            top: 50%;
            left: 20px;
            transform: translateY(-50%);
            display: flex;
            flex-direction: column;
        }

        nav a,
        .logout a {
            text-decoration: none;
            color: #1da1f2;
            margin-bottom: 10px;
            font-size: 14px;
        }

        nav a:hover,
        .logout a:hover {
            text-decoration: underline;
        }

        .logout {
            position: absolute;
            top: 20px;
            left: 20px;
        }

        .search-bar {
            text-align: center;
            margin-bottom: 20px;
        }

        .search-bar input[type="text"] {
            width: 300px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 20px;
            margin-right: 10px;
        }

        .search-bar button {
            padding: 10px 20px;
            border: none;
            border-radius: 20px;
            background-color: #1da1f2;
            color: #fff;
            cursor: pointer;
        }

        .search-bar button:hover {
            background-color: #0d8deb;
        }

        /* tweet box */
        .tweet-box {
            border: 1px solid #e1e8ed;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 20px;
            background-color: #fff;
        }

        .tweet-box img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }

        .tweet-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .tweet-header a {
            font-weight: bold;
            color: #1da1f2;
            text-decoration: none;
        }

        .tweet-header small {
            color: #657786;
            font-size: 0.9em;
        }

        .tweet-content {
            font-size: 1em;
            color: #14171a;
            margin-bottom: 10px;
        }

        .tweet-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9em;
            color: #657786;
        }

        .like-button {
            background: none;
            border: none;
            color: #1da1f2;
            cursor: pointer;
            font-size: 0.9em;
        }

        .like-button:hover {
            text-decoration: underline;
        }

        .followed-user {
            margin-bottom: 10px;
        }

        .followed-user a {
            text-decoration: none;
            color: #1da1f2;
            font-size: 14px;
        }

        .followed-user a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <header> 
            <h1>Home</h1>
            <nav> 
                <!--liked tweets and logout button-->
                <a href="profile.php?username=<?php echo urlencode($username); ?>">Profile</a>
                <a href="liked_tweets.php">Liked Tweets</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>
<!--search bar-->
        <section class="search-bar">
            <form action="search.php" method="get">
                <input type="text" name="query" placeholder="Search for users..." required>
                <button type="submit">Search</button>
            </form>
        </section>
<!--tweet form-->
        <?php include 'tweet_form.php'; ?>
<!--tweet box-->
        <h3>Tweets</h3>
        <?php while ($row = $tweets_result->fetch_assoc()): ?>
            <div class="tweet-box">
                <div class="tweet-header">
                    <a href="profile.php?username=<?php echo urlencode($row['username']); ?>">
                        <?php echo htmlspecialchars($row['username']); ?>
                    </a>
                    <small><?php echo htmlspecialchars($row['created_at']); ?></small>
                </div>
                <div class="tweet-content"><?php echo htmlspecialchars($row['content']); ?></div>
                <?php if ($row['image']): ?>
                    <img src="<?php echo htmlspecialchars($row['image']); ?>" alt="Tweet Image">
                <?php endif; ?>
                <div class="tweet-footer">
                    <span>Likes: <?php echo htmlspecialchars($row['like_count']); ?></span>
                    <form action="index.php" method="post" class="form-inline">
                        <input type="hidden" name="tweet_id" value="<?php echo htmlspecialchars($row['id']); ?>">
                        <button type="submit" class="like-button">
                            <?php
                                $like_check_stmt = $conn->prepare("SELECT * FROM likes WHERE user_id = ? AND tweet_id = ?");
                                $like_check_stmt->bind_param("ii", $user_id, $row['id']);
                                $like_check_stmt->execute();
                                $liked = $like_check_stmt->fetch();
                                $like_check_stmt->close();
                                echo $liked ? 'Unlike' : 'Like';
                            ?>
                        </button>
                    </form>
                </div>
            </div>
        <?php endwhile; ?>
<!--followed users-->
        <h3>Followed Users</h3>
        <?php while ($user = $followed_users_result->fetch_assoc()): ?>
            <div class="followed-user">
                <a href="profile.php?username=<?php echo urlencode($user['username']); ?>">
                    <?php echo htmlspecialchars($user['username']); ?>
                </a>
            </div>
        <?php endwhile; ?>
        
        <?php 
        $tweets_stmt->close();
        $followed_users_stmt->close();
        ?>
    </div>
</body>
</html>
