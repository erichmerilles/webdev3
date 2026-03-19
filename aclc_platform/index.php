<?php
session_start();
date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/db.php';

// Fetch all posts
$stmt = $pdo->query("SELECT * FROM posts WHERE created_at <= NOW() ORDER BY created_at DESC");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==========================================
// PERSISTENT LIKE TRACKING 
// ==========================================
$userId = $_SESSION['user_id'];

// 1. Get all Post IDs this user has liked
$likedPostsStmt = $pdo->prepare("SELECT post_id FROM post_likes WHERE user_id = ?");
$likedPostsStmt->execute([$userId]);
$userLikedPosts = $likedPostsStmt->fetchAll(PDO::FETCH_COLUMN);

// 2. Get all Comment IDs this user has liked
$likedCommentsStmt = $pdo->prepare("SELECT comment_id FROM comment_likes WHERE user_id = ?");
$likedCommentsStmt->execute([$userId]);
$userLikedComments = $likedCommentsStmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="icon" type="image/*" href="assets/img/logo2.ico">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ACLC Portal | Student Hub</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-elegant sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <span class="brand-text fs-3 fw-bold">aclc<span class="text-info">.</span>hub</span>
            </a>

            <div class="ms-auto d-flex align-items-center gap-3">
                <span class="text-muted small d-none d-md-block">Welcome back, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></span>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="admin.php" class="btn btn-sm btn-elegant border-0"><i class="bi bi-grid-1x2"></i></a>
                <?php endif; ?>
                <button onclick="confirmLogout(event)" class="btn btn-sm btn-dark rounded-pill px-4 fw-bold">Logout</button>
            </div>
        </div>
    </nav>

    <div class="hero-elegant text-center pb-0">
        <div class="hero-bg-container mb-0" style="border-bottom-left-radius: 0; border-bottom-right-radius: 0;">
            <div class="container pt-5 pb-4">
                <h1 class="display-5 fw-bold mb-2">Campus Updates</h1>
                <p class="text-muted lead mb-0">The official news and announcements of ACLC College of Manila.</p>
            </div>
        </div>
    </div>

    <div class="container-fluid px-0 mb-4">
        <div class="ticker-container mx-auto" style="max-width: 96%; border-bottom-left-radius: 15px; border-bottom-right-radius: 15px;">
            <div class="ticker-label text-uppercase">
                <span class="alert-dot bg-danger me-2"></span> ACLC News
            </div>
            <div class="ticker-wrapper">
                <div class="ticker-content">
                    <?php
                    $bulletins = [];
                    if (file_exists('data/announcements.xml')) {
                        $xml = simplexml_load_file('data/announcements.xml');
                        foreach ($xml->announcement as $item) {
                            $bulletins[] = "<span class='badge bg-white text-danger me-2 text-uppercase'>" . htmlspecialchars($item->category) . "</span> <span class='fw-bold me-1'>" . htmlspecialchars($item->title) . ":</span> <span class='me-5 opacity-75'>" . htmlspecialchars($item->description) . "</span>";
                        }
                    }

                    if (empty($bulletins)) {
                        echo "<span class='fst-italic opacity-75'>No active urgent bulletins at this time.</span>";
                    } else {
                        $bulletinString = implode("", $bulletins);
                        echo $bulletinString . $bulletinString;
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-3">
        <div class="row g-5">

            <div class="col-lg-8">

                <div class="d-flex align-items-center gap-2 mb-4 overflow-auto pb-2 feed-filters">
                    <button class="filter-pill active" data-filter="all">All Updates</button>
                    <button class="filter-pill" data-filter="news"><i class="bi bi-megaphone me-1"></i> News</button>
                    <button class="filter-pill" data-filter="event"><i class="bi bi-calendar-event me-1"></i> Events</button>
                </div>

                <?php if (empty($posts)): ?>
                    <div class="text-center py-5">
                        <p class="text-muted italic">No current post to display.</p>
                    </div>
                <?php endif; ?>

                <div id="feedContainer">
                    <?php foreach ($posts as $post): ?>
                        <div class="card elegant-card post-card" data-category="<?= $post['type'] ?>">
                            <div class="p-4 d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="card-meta mb-1"><?= $post['type'] ?></div>
                                    <h2 class="h4 fw-bold mb-0"><?= htmlspecialchars($post['title']) ?></h2>
                                </div>
                                <?php if ($post['type'] == 'event'): ?>
                                    <?php $displayDate = !empty($post['event_date']) ? $post['event_date'] : $post['created_at']; ?>
                                    <div class="date-badge-elegant">
                                        <div class="small fw-bold text-uppercase text-muted"><?= date('M', strtotime($displayDate)) ?></div>
                                        <div class="h3 fw-bold mb-0 text-dark"><?= date('d', strtotime($displayDate)) ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($post['image_path'])): ?>
                                <div class="px-4">
                                    <img src="<?= htmlspecialchars($post['image_path']) ?>" class="img-fluid rounded-4 w-100" alt="News Image" style="max-height: 450px; object-fit: cover;">
                                </div>
                            <?php endif; ?>

                            <div class="card-body p-4">
                                <p class="text-muted" style="line-height: 1.8; font-size: 1.05rem;">
                                    <?= nl2br(htmlspecialchars($post['content'])) ?>
                                </p>
                                <hr class="opacity-0">

                                <div class="d-flex gap-3 mt-2">
                                    <?php
                                    $isPostLiked = in_array($post['id'], $userLikedPosts);
                                    $btnClass = $isPostLiked ? 'btn-primary text-white bg-primary' : 'btn-elegant';
                                    $iconClass = $isPostLiked ? 'bi-heart-fill' : 'bi-heart';
                                    ?>
                                    <button class="btn <?= $btnClass ?> flex-grow-1" onclick="likePost(<?= $post['id'] ?>, this)">
                                        <i class="bi <?= $iconClass ?> me-2"></i> <span id="likeCount_<?= $post['id'] ?>"><?= $post['likes'] ?></span>
                                    </button>

                                    <button class="btn btn-elegant flex-grow-1" data-bs-toggle="collapse" data-bs-target="#comments_<?= $post['id'] ?>">
                                        <i class="bi bi-chat-right-text me-2"></i> Comments
                                    </button>
                                </div>
                            </div>

                            <div class="collapse" id="comments_<?= $post['id'] ?>">
                                <div class="p-4 bg-light border-top border-light">
                                    <div id="commentList_<?= $post['id'] ?>" class="mb-4">
                                        <?php
                                        $c_stmt = $pdo->prepare("SELECT * FROM comments WHERE post_id = ? ORDER BY created_at ASC");
                                        $c_stmt->execute([$post['id']]);
                                        $all_comments = $c_stmt->fetchAll(PDO::FETCH_ASSOC);

                                        $comments_by_parent = [];
                                        foreach ($all_comments as $c) {
                                            $parentId = $c['parent_id'] ?? 0;
                                            $comments_by_parent[$parentId][] = $c;
                                        }

                                        if (!empty($comments_by_parent[0])) {
                                            foreach ($comments_by_parent[0] as $comment) {
                                                $likes = $comment['likes'] ?? 0;

                                                // DYNAMIC COMMENT LIKES
                                                $isCommentLiked = in_array($comment['id'], $userLikedComments);
                                                $cTextClass = $isCommentLiked ? 'text-primary' : 'text-muted';
                                                $cIconClass = $isCommentLiked ? 'bi-hand-thumbs-up-fill' : 'bi-hand-thumbs-up';

                                                echo "<div class='mb-3'>";
                                                echo "  <span class='fw-bold small text-dark'>" . htmlspecialchars($comment['student_name']) . "</span>";
                                                echo "  <p class='small text-muted mb-1'>" . htmlspecialchars($comment['comment_text']) . "</p>";

                                                echo "  <div class='d-flex gap-3 align-items-center mb-2'>";
                                                echo "      <button class='btn btn-link p-0 {$cTextClass} small text-decoration-none' style='font-size: 0.8rem;' onclick='likeComment({$comment['id']}, this)'><i class='bi {$cIconClass}'></i> <span id='commentLike_{$comment['id']}'>{$likes}</span></button>";
                                                echo "      <button class='btn btn-link p-0 text-muted small text-decoration-none' style='font-size: 0.8rem;' onclick='toggleReplyBox({$comment['id']})'>Reply</button>";
                                                echo "  </div>";

                                                echo "  <div id='replyBox_{$comment['id']}' class='d-none mb-3'>";
                                                echo "      <div class='input-group rounded-pill overflow-hidden border shadow-sm input-group-sm'>";
                                                echo "          <input type='text' id='replyInput_{$comment['id']}' class='form-control border-0 ps-3' placeholder='Write a reply...'>";
                                                echo "          <button class='btn btn-primary px-3' onclick='submitReply({$post['id']}, {$comment['id']})'>Post</button>";
                                                echo "      </div>";
                                                echo "  </div>";

                                                if (!empty($comments_by_parent[$comment['id']])) {
                                                    echo "<div class='ms-4 ps-3 border-start border-2 border-primary border-opacity-25' id='replyList_{$comment['id']}'>";
                                                    foreach ($comments_by_parent[$comment['id']] as $reply) {
                                                        $replyLikes = $reply['likes'] ?? 0;

                                                        $isReplyLiked = in_array($reply['id'], $userLikedComments);
                                                        $rTextClass = $isReplyLiked ? 'text-primary' : 'text-muted';
                                                        $rIconClass = $isReplyLiked ? 'bi-hand-thumbs-up-fill' : 'bi-hand-thumbs-up';

                                                        echo "<div class='mb-2'>";
                                                        echo "  <span class='fw-bold small text-dark'>" . htmlspecialchars($reply['student_name']) . "</span>";
                                                        echo "  <p class='small text-muted mb-1'>" . htmlspecialchars($reply['comment_text']) . "</p>";
                                                        echo "  <button class='btn btn-link p-0 {$rTextClass} small text-decoration-none' style='font-size: 0.75rem;' onclick='likeComment({$reply['id']}, this)'><i class='bi {$rIconClass}'></i> <span id='commentLike_{$reply['id']}'>{$replyLikes}</span></button>";
                                                        echo "</div>";
                                                    }
                                                    echo "</div>";
                                                } else {
                                                    echo "<div class='ms-4 ps-3 border-start border-2 border-primary border-opacity-25' id='replyList_{$comment['id']}'></div>";
                                                }
                                                echo "</div>";
                                            }
                                        } else {
                                            echo "<p class='text-muted small italic text-center py-2' id='noCommentMsg_{$post['id']}'>Be the first to start the conversation.</p>";
                                        }
                                        ?>
                                    </div>
                                    <div class="input-group rounded-pill overflow-hidden border shadow-sm">
                                        <input type="text" id="commentInput_<?= $post['id'] ?>" class="form-control border-0 ps-4" placeholder="Write a comment...">
                                        <button class="btn btn-primary px-4" onclick="submitComment(<?= $post['id'] ?>)">Post</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div id="noResultsMsg" class="text-center py-5 d-none">
                    <p class="text-muted italic">No updates found for this category.</p>
                </div>

            </div>

            <div class="col-lg-4">
                <div class="sidebar-sticky">
                    <div class="mt-2">
                        <h6 class="fw-bold text-uppercase small letter-spacing-1 mb-3">Quick Links</h6>
                        <ul class="list-unstyled">
                            <li class="mb-2"><a href="http://www.aclc.edu.ph/" class="text-decoration-none text-muted small hover-navy" target="_blank">Main ACLC Website</a></li>
                            <li class="mb-2"><a href="http://www.amaesonline.com/index_pscs.php" class="text-decoration-none text-muted small hover-navy" target="_blank">Student Portal Login</a></li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>

</body>

</html>