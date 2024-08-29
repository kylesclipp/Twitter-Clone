<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$search_query = isset($_GET['query']) ? $_GET['query'] : '';

if (!$search_query) {
    echo "No search query provided.";
    exit();
}


$stmt = $conn->prepare("
    SELECT id, username 
    FROM users 
    WHERE username LIKE ?
");
$search_query_param = '%' . $search_query . '%';
$stmt->bind_param("s", $search_query_param);
$stmt->execute();
$search_result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Search Results</title>
</head>
<body>
    <h2>Search Results for "<?php echo htmlspecialchars($search_query); ?>"</h2>

    <?php if ($search_result->num_rows > 0): ?>
        <ul>
            <?php while ($user = $search_result->fetch_assoc()): ?>
                <li>
                    <a href="profile.php?username=<?php echo urlencode($user['username']); ?>">
                        <?php echo htmlspecialchars($user['username']); ?>
                    </a>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>No users found.</p>
    <?php endif; ?>

    <a href="index.php">Back to Home</a>
</body>
</html>
