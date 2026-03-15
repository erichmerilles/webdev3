<?php
require_once '../config/db.php';

if (isset($_POST['post_id'])) {
    $postId = (int)$_POST['post_id'];

    // Update like count
    $stmt = $pdo->prepare("UPDATE posts SET likes = likes + 1 WHERE id = ?");
    if ($stmt->execute([$postId])) {
        // Fetch new count to send back to JS
        $stmt2 = $pdo->prepare("SELECT likes FROM posts WHERE id = ?");
        $stmt2->execute([$postId]);
        echo $stmt2->fetchColumn();
    } else {
        echo "error";
    }
}
