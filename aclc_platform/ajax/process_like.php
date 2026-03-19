<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['logged_in'])) {
    echo "error_auth";
    exit();
}

if (isset($_POST['post_id']) && isset($_POST['action'])) {
    $postId = (int)$_POST['post_id'];
    $action = $_POST['action'];
    $userId = (int)$_SESSION['user_id']; // Get the actual logged-in user!

    try {
        $pdo->beginTransaction(); // Start a transaction for safety

        if ($action === 'like') {
            // 1. Add the record to the tracking table (IGNORE prevents duplicate errors)
            $stmt = $pdo->prepare("INSERT IGNORE INTO post_likes (user_id, post_id) VALUES (?, ?)");
            $stmt->execute([$userId, $postId]);

            // 2. Only increase the total count if the insert was actually successful
            if ($stmt->rowCount() > 0) {
                $pdo->prepare("UPDATE posts SET likes = likes + 1 WHERE id = ?")->execute([$postId]);
            }
        } else if ($action === 'unlike') {
            // 1. Remove the record from the tracking table
            $stmt = $pdo->prepare("DELETE FROM post_likes WHERE user_id = ? AND post_id = ?");
            $stmt->execute([$userId, $postId]);

            // 2. Only decrease the total count if a record was actually deleted
            if ($stmt->rowCount() > 0) {
                $pdo->prepare("UPDATE posts SET likes = GREATEST(0, likes - 1) WHERE id = ?")->execute([$postId]);
            }
        }

        $pdo->commit(); // Save the changes

        // Fetch the perfectly accurate new count
        $stmt2 = $pdo->prepare("SELECT likes FROM posts WHERE id = ?");
        $stmt2->execute([$postId]);
        echo $stmt2->fetchColumn();
    } catch (PDOException $e) {
        $pdo->rollBack(); // Cancel changes if something breaks
        echo "error";
    }
} else {
    echo "error";
}
