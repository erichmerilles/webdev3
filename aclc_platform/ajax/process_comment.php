<?php
session_start();
require_once '../config/db.php';

// Security Check: Ensure the user is logged in before allowing a comment
if (!isset($_SESSION['logged_in'])) {
    echo "error_auth";
    exit();
}

if (isset($_POST['post_id']) && isset($_POST['comment_text'])) {
    $postId = (int)$_POST['post_id'];
    $commentText = htmlspecialchars(trim($_POST['comment_text']));

    // SECURE: Get the username directly from the server session, not the frontend
    $studentName = $_SESSION['username'];

    // NEW: Catch the parent_id if this is a reply. Default to 0 if it's a main comment.
    $parentId = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;

    // Prevent empty blank spaces from being submitted
    if (empty($commentText)) {
        echo "error";
        exit();
    }

    // Updated SQL query to insert the parent_id
    $stmt = $pdo->prepare("INSERT INTO comments (post_id, student_name, comment_text, parent_id) VALUES (?, ?, ?, ?)");

    if ($stmt->execute([$postId, $studentName, $commentText, $parentId])) {
        // Return the student name so JavaScript can display it instantly
        echo htmlspecialchars($studentName);
    } else {
        echo "error";
    }
} else {
    echo "error";
}
