<?php
session_start();
date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/db.php';

$stmt = $pdo->query("SELECT * FROM posts WHERE created_at <= NOW() ORDER BY created_at DESC");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ACLC Portal | Elegant Student Hub</title>

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

    <div class="hero-elegant text-center">
        <div class="container">
            <h1 class="display-5 fw-bold mb-2">Campus Updates</h1>
            <p class="text-muted lead">The official news and announcements of ACLC College of Manila.</p>
        </div>
    </div>

    <div class="container py-4">
        <div class="row g-5">

            <div class="col-lg-8">
                <?php if (empty($posts)): ?>
                    <div class="text-center py-5">
                        <p class="text-muted italic">Everything is quiet on campus for now.</p>
                    </div>
                <?php endif; ?>

                <?php foreach ($posts as $post): ?>
                    <div class="card elegant-card">
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
                                <button class="btn btn-elegant flex-grow-1" onclick="likePost(<?= $post['id'] ?>, this)">
                                    <i class="bi bi-heart me-2"></i> <span id="likeCount_<?= $post['id'] ?>"><?= $post['likes'] ?></span>
                                </button>
                                <button class="btn btn-elegant flex-grow-1" data-bs-toggle="collapse" data-bs-target="#comments_<?= $post['id'] ?>">
                                    <i class="bi bi-chat-right-text me-2"></i> Comments
                            </div>
                        </div>

                        <div class="collapse" id="comments_<?= $post['id'] ?>">
                            <div class="p-4 bg-light border-top border-light">
                                <div id="commentList_<?= $post['id'] ?>" class="mb-4">
                                    <?php
                                    $c_stmt = $pdo->prepare("SELECT * FROM comments WHERE post_id = ? ORDER BY created_at ASC");
                                    $c_stmt->execute([$post['id']]);
                                    while ($comment = $c_stmt->fetch(PDO::FETCH_ASSOC)) {
                                        echo "<div class='mb-3'>";
                                        echo "<span class='fw-bold small text-dark'>" . htmlspecialchars($comment['student_name']) . "</span>";
                                        echo "<p class='small text-muted mb-0'>" . htmlspecialchars($comment['comment_text']) . "</p>";
                                        echo "</div>";
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

            <div class="col-lg-4">
                <div class="sidebar-sticky">
                    <h6 class="fw-bold text-uppercase small letter-spacing-1 mb-4"><span class="alert-dot"></span>Active Bulletins</h6>
                    <div class="urgent-scroll">
                        <?php
                        if (file_exists('data/announcements.xml')) {
                            $xml = simplexml_load_file('data/announcements.xml');
                            foreach ($xml->announcement as $item) {
                                echo "
                                <div class='mb-4 pb-3 border-bottom border-light'>
                                    <div class='text-danger fw-bold small mb-1'>{$item->category}</div>
                                    <h6 class='fw-bold mb-1'>{$item->title}</h6>
                                    <p class='small text-muted mb-0'>{$item->description}</p>
                                </div>";
                            }
                        }
                        ?>
                    </div>

                    <div class="mt-5">
                        <h6 class="fw-bold text-uppercase small letter-spacing-1 mb-3">Quick Links</h6>
                        <ul class="list-unstyled">
                            <li class="mb-2"><a href="#" class="text-decoration-none text-muted small hover-navy">Main ACLC Website</a></li>
                            <li class="mb-2"><a href="#" class="text-decoration-none text-muted small hover-navy">Student Portal Login</a></li>
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