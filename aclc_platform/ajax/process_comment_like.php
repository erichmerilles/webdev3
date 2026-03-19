<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['logged_in'])) {
    echo "error_auth";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['comment_id']) && isset($_POST['action'])) {
    $commentId = (int)$_POST['comment_id'];
    $action = $_POST['action'];
    $userId = (int)$_SESSION['user_id'];

    try {
        $pdo->beginTransaction();

        if ($action === 'like') {
            $stmt = $pdo->prepare("INSERT IGNORE INTO comment_likes (user_id, comment_id) VALUES (?, ?)");
            $stmt->execute([$userId, $commentId]);

            if ($stmt->rowCount() > 0) {
                $pdo->prepare("UPDATE comments SET likes = likes + 1 WHERE id = ?")->execute([$commentId]);
            }
        } else if ($action === 'unlike') {
            $stmt = $pdo->prepare("DELETE FROM comment_likes WHERE user_id = ? AND comment_id = ?");
            $stmt->execute([$userId, $commentId]);

            if ($stmt->rowCount() > 0) {
                $pdo->prepare("UPDATE comments SET likes = GREATEST(0, likes - 1) WHERE id = ?")->execute([$commentId]);
            }
        }

        $pdo->commit();
        echo "success";
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "error";
    }
} else {
    echo "error";
}
