<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$profile_username = isset($_GET['username']) ? $_GET['username'] : '';

if (!$profile_username) {
    echo "No user specified.";
    exit();
}

$stmt = $conn->prepare("SELECT id, username, profile_picture, bio FROM users WHERE username = ?");
$stmt->bind_param("s", $profile_username);
$stmt->execute();
$stmt->bind_result($profile_user_id, $profile_username, $profile_picture, $bio);
$stmt->fetch();
$stmt->close();

if (!$profile_user_id) {
    echo "User not found.";
    exit();
}

$tweets_stmt = $conn->prepare("
    SELECT t.id, t.content, t.image, t.created_at, t.user_id, COUNT(l.id) AS like_count
    FROM tweets t
    LEFT JOIN likes l ON t.id = l.tweet_id
    WHERE t.user_id = ?
    GROUP BY t.id, t.user_id
    ORDER BY t.created_at DESC
");
$tweets_stmt->bind_param("i", $profile_user_id);
$tweets_stmt->execute();
$tweets_result = $tweets_stmt->get_result();
$tweets_stmt->close();

$following_stmt = $conn->prepare("SELECT * FROM follows WHERE follower_id = ? AND followee_id = ?");
$following_stmt->bind_param("ii", $user_id, $profile_user_id);
$following_stmt->execute();
$following = $following_stmt->fetch();
$following_stmt->close();

$edit_mode = isset($_GET['edit']) && $_GET['edit'] == 'true' && $user_id == $profile_user_id;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_picture']) && $user_id == $profile_user_id) {
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["profile_picture"]["name"]);
    
    if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
        $new_profile_picture = basename($_FILES["profile_picture"]["name"]);

        $update_stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
        $update_stmt->bind_param("si", $new_profile_picture, $profile_user_id);
        $update_stmt->execute();
        $update_stmt->close();

        $profile_picture = $new_profile_picture;
    } else {
        echo "File upload failed.";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bio']) && $user_id == $profile_user_id) {
    $new_bio = $_POST['bio'];

    $update_stmt = $conn->prepare("UPDATE users SET bio = ? WHERE id = ?");
    $update_stmt->bind_param("si", $new_bio, $profile_user_id);
    $update_stmt->execute();
    $update_stmt->close();

    $bio = $new_bio;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tweet_id'])) {
    $tweet_id = $_POST['tweet_id'];

    $like_stmt = $conn->prepare("SELECT * FROM likes WHERE user_id = ? AND tweet_id = ?");
    $like_stmt->bind_param("ii", $user_id, $tweet_id);
    $like_stmt->execute();
    $liked = $like_stmt->fetch();
    $like_stmt->close();

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

    header("Location: profile.php?username=" . urlencode($profile_username));
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($profile_username); ?>'s Profile</title>
    <style>
        /* ... existing CSS ... */
        body {
            font-family: Arial, sans-serif;
            background-color: #e6ecf0;
            margin: 0;
            padding: 0;
        }

        .profile-header {
            display: flex;
            align-items: center;
            background-color: #fff;
            border: 1px solid #e1e8ed;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .profile-header img {
            border-radius: 50%;
            width: 120px;
            height: 120px;
            margin-right: 20px;
            border: 2px solid #1da1f2;
        }

        .profile-header h3 {
            margin: 0;
            font-size: 24px;
            color: #14171a;
        }

        .profile-header .bio {
            margin-top: 10px;
            font-size: 16px;
            color: #657786;
        }

        .profile-header form {
            margin-top: 10px;
        }

        .tweet-box {
            border: 1px solid #e1e8ed;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .tweet-box img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin-top: 10px;
        }

        .tweet-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .tweet-header a {
            font-weight: bold;
            color: #1da1f2;
            text-decoration: none;
            margin-right: 10px;
        }

        .tweet-header small {
            color: #657786;
            font-size: 0.9em;
        }

        .tweet-content {
            font-size: 16px;
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

        .tweet-footer span {
            display: flex;
            align-items: center;
        }

        .like-button, .delete-button {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 0.9em;
            color: #1da1f2;
        }

        .like-button:hover, .delete-button:hover {
            text-decoration: underline;
        }

        .delete-button {
            color: red;
        }
    </style>
</head>
<body>
    <h2><?php echo htmlspecialchars($profile_username); ?>'s Profile</h2>

    <div class="profile-header">
        <img src="uploads/<?php echo htmlspecialchars($profile_picture); ?>" alt="<?php echo htmlspecialchars($profile_username); ?>'s Profile Picture">
        <div>
            <h3><?php echo htmlspecialchars($profile_username); ?></h3>
            <div class="bio">
                <p><?php echo nl2br(htmlspecialchars($bio)); ?></p>
                <?php if ($user_id == $profile_user_id && !$edit_mode): ?>
                    <a href="profile.php?username=<?php echo urlencode($profile_username); ?>&edit=true">Edit Profile</a>
                <?php endif; ?>
            </div>
            
            <?php if ($edit_mode): ?>
                <form action="profile.php?username=<?php echo urlencode($profile_username); ?>" method="post">
                    <textarea name="bio"><?php echo htmlspecialchars($bio); ?></textarea>
                    <button type="submit">Update Bio</button>
                </form>
                <?php if ($profile_picture): ?>
                    <h4>Update Profile Picture</h4>
                    <form action="profile.php?username=<?php echo urlencode($profile_username); ?>" method="post" enctype="multipart/form-data">
                        <input type="file" name="profile_picture" accept="image/*">
                        <button type="submit">Update Profile Picture</button>
                    </form>
                <?php endif; ?>
                <a href="profile.php?username=<?php echo urlencode($profile_username); ?>">Cancel</a>
            <?php endif; ?>

            <?php if ($user_id !== $profile_user_id): ?>
                <!-- follow/unfollow buttons -->
                <?php if ($following): ?>
                    <form action="unfollow.php" method="post">
                        <input type="hidden" name="followee_id" value="<?php echo htmlspecialchars($profile_user_id); ?>">
                        <button type="submit">Unfollow</button>
                    </form>
                <?php else: ?>
                    <form action="follow.php" method="post">
                        <input type="hidden" name="followee_id" value="<?php echo htmlspecialchars($profile_user_id); ?>">
                        <button type="submit">Follow</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- display the tweets -->
    <?php while ($tweet = $tweets_result->fetch_assoc()): ?>
        <div class="tweet-box">
            <div class="tweet-header">
                <a href="profile.php?username=<?php echo htmlspecialchars($profile_username); ?>"><?php echo htmlspecialchars($profile_username); ?></a>
                <small><?php echo htmlspecialchars($tweet['created_at']); ?></small>
            </div>
            <div class="tweet-content">
                <?php echo nl2br(htmlspecialchars($tweet['content'])); ?>
            </div>
            <?php if ($tweet['image']): ?>
                <img src="uploads/<?php echo htmlspecialchars($tweet['image']); ?>" alt="Tweet Image">
            <?php endif; ?>
            <div class="tweet-footer">
                <span><?php echo $tweet['like_count']; ?> Likes</span>
                <form action="profile.php?username=<?php echo urlencode($profile_username); ?>" method="post" style="display:inline;">
                    <input type="hidden" name="tweet_id" value="<?php echo $tweet['id']; ?>">
                    <?php
                    // Check if the user has already liked the tweet
                    $like_check_stmt = $conn->prepare("SELECT * FROM likes WHERE user_id = ? AND tweet_id = ?");
                    $like_check_stmt->bind_param("ii", $user_id, $tweet['id']);
                    $like_check_stmt->execute();
                    $liked = $like_check_stmt->fetch();
                    $like_check_stmt->close();
                    ?>
                    <button type="submit" class="like-button"><?php echo $liked ? 'Unlike' : 'Like'; ?></button>
                </form>
                <?php if ($tweet['user_id'] == $user_id): ?>
                    <form action="delete_tweet.php" method="post" style="display:inline;">
                        <input type="hidden" name="tweet_id" value="<?php echo $tweet['id']; ?>">
                        <button type="submit" class="delete-button">Delete</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endwhile; ?>
</body>
</html>
