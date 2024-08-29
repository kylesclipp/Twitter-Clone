<style>

.tweet-form textarea {
    width: 100%;
    height: 150px;
    padding: 10px;
    border: 1px solid #ccd6dd;
    border-radius: 8px;
    box-sizing: border-box;
    font-size: 16px;
    color: #14171a;
    margin-bottom: 10px;
    resize: vertical; 
}

.tweet-form button {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    background-color: #1da1f2;
    color: white;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.tweet-form button:hover {
    background-color: #1991db;
}

</style>
    
<form action="post_tweet.php" method="post" enctype="multipart/form-data" class="tweet-form">
    <textarea name="content" placeholder="What's happening?" required></textarea>
    <input type="file" name="image" accept="image/*">
    <button type="submit">Tweet</button>
</form>

