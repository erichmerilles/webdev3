<?php
session_start();

// Security Check: Kick out unauthenticated users
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/db.php';

// Fetch all posts and their associated comments
$stmt = $pdo->query("SELECT * FROM posts ORDER BY created_at DESC");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ACLC News & Events</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom py-3 sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
                <i class="bi bi-building me-2 fs-4"></i>
                ACLC News & Events Platform
            </a>

            <div class="d-flex align-items-center">
                <span class="text-white me-4 small opacity-75 d-none d-md-block">
                    <i class="bi bi-person-circle me-1"></i> Logged in as: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
                </span>

                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="admin.php" class="btn btn-sm btn-light text-primary fw-bold rounded-pill px-3 me-2" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Access Control Panel">
                        <i class="bi bi-shield-lock me-1"></i> Admin Panel
                    </a>
                <?php endif; ?>

                <button onclick="confirmLogout(event)" class="btn btn-sm btn-outline-light rounded-pill px-3" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Sign out of your account">
                    <i class="bi bi-box-arrow-right me-1 d-none d-sm-inline"></i>Logout
                </button>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center g-4">

            <div class="col-lg-8">

                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h4 class="fw-bold text-dark m-0">Campus Bulletin</h4>
                    <span class="text-muted small"><i class="bi bi-clock-history me-1"></i> Latest Updates</span>
                </div>

                <?php if (empty($posts)): ?>
                    <div class="text-center py-5 text-muted bg-white rounded-4 shadow-sm">
                        <i class="bi bi-inbox fs-1 mb-3 d-block opacity-50"></i>
                        <h5>No announcements at this time.</h5>
                        <p class="small">Check back later for updates from the administration.</p>
                    </div>
                <?php endif; ?>

                <?php foreach ($posts as $post): ?>
                    <div class="card announcement-card">

                        <div class="card-header-custom d-flex justify-content-between align-items-start">
                            <div>
                                <span class="badge <?= $post['type'] == 'event' ? 'bg-warning text-dark' : 'bg-primary' ?> mb-2 px-2 py-1">
                                    <i class="bi <?= $post['type'] == 'event' ? 'bi-calendar-event' : 'bi-megaphone' ?> me-1"></i>
                                    <?= strtoupper($post['type']) ?>
                                </span>
                                <h4 class="fw-bold mb-1" style="color: #212529;"><?= htmlspecialchars($post['title']) ?></h4>
                                <div class="text-muted small">
                                    Posted on <?= date('F j, Y \a\t g:i A', strtotime($post['created_at'])) ?>
                                </div>
                            </div>

                            <?php if ($post['type'] == 'event'): ?>
                                <div class="event-date-box d-none d-sm-block shadow-sm">
                                    <div class="text-danger fw-bold small text-uppercase"><?= date('M', strtotime($post['created_at'])) ?></div>
                                    <div class="fs-4 fw-bold text-dark lh-1"><?= date('d', strtotime($post['created_at'])) ?></div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($post['image_path'])): ?>
                            <img src="<?= htmlspecialchars($post['image_path']) ?>" class="img-fluid w-100" alt="Announcement Image" style="max-height: 450px; object-fit: contain; background-color: #f8f9fa; border-bottom: 1px solid #f0f0f0;">
                        <?php endif; ?>

                        <div class="card-body p-4">
                            <p class="card-text text-dark" style="font-size: 1.05rem; line-height: 1.6;">
                                <?= nl2br(htmlspecialchars($post['content'])) ?>
                            </p>
                        </div>

                        <div class="card-footer bg-white border-top-0 pt-0 pb-3 px-4 d-flex gap-2">
                            <button class="btn btn-sm btn-outline-secondary action-btn" onclick="likePost(<?= $post['id'] ?>, this)">
                                <i class="bi bi-hand-thumbs-up"></i> Like (<span id="likeCount_<?= $post['id'] ?>"><?= $post['likes'] ?></span>)
                            </button>
                            <button class="btn btn-sm btn-outline-secondary action-btn" data-bs-toggle="collapse" data-bs-target="#comments_<?= $post['id'] ?>">
                                <i class="bi bi-chat-text"></i> Student Feedback
                            </button>
                        </div>

                        <div class="collapse comment-section" id="comments_<?= $post['id'] ?>">
                            <div class="p-4">
                                <h6 class="fw-bold small text-muted mb-3 text-uppercase">Discussion</h6>

                                <div id="commentList_<?= $post['id'] ?>" class="mb-4">
                                    <?php
                                    $c_stmt = $pdo->prepare("SELECT * FROM comments WHERE post_id = ? ORDER BY created_at ASC");
                                    $c_stmt->execute([$post['id']]);
                                    $hasComments = false;

                                    while ($comment = $c_stmt->fetch(PDO::FETCH_ASSOC)) {
                                        $hasComments = true;
                                        echo "<div class='d-flex mb-3 pb-3 border-bottom border-light'>";
                                        echo "<i class='bi bi-person-circle fs-4 text-secondary me-3 mt-1'></i>";
                                        echo "<div>";
                                        echo "<div class='fw-bold text-dark small'>" . htmlspecialchars($comment['student_name']) . "</div>";
                                        echo "<div class='text-secondary' style='font-size: 0.95rem;'>" . htmlspecialchars($comment['comment_text']) . "</div>";
                                        echo "</div></div>";
                                    }

                                    if (!$hasComments) {
                                        echo "<p class='text-muted small fst-italic' id='noCommentMsg_{$post['id']}'>No comments yet. Be the first to share your thoughts!</p>";
                                    }
                                    ?>
                                </div>

                                <div class="input-group">
                                    <input type="text" id="commentInput_<?= $post['id'] ?>" class="form-control bg-white" placeholder="Add a respectful comment..." onkeypress="if(event.key === 'Enter') submitComment(<?= $post['id'] ?>)">
                                    <button class="btn btn-primary px-4 fw-bold" onclick="submitComment(<?= $post['id'] ?>)">
                                        Post
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>

            <div class="col-lg-4">
                <div class="position-sticky" style="top: 100px;">

                    <div class="card border-0 bg-transparent mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-exclamation-triangle-fill text-danger fs-4 me-2"></i>
                            <h5 class="fw-bold m-0 text-dark">Urgent Bulletins</h5>
                        </div>

                        <?php
                        if (file_exists('data/announcements.xml')) {
                            $xml = simplexml_load_file('data/announcements.xml');
                            foreach ($xml->announcement as $item) {
                                echo "
                            <div class='urgent-alert-box p-3 mb-3'>
                                <span class='badge bg-danger mb-2 px-2'>{$item->category}</span>
                                <h6 class='fw-bold mb-1 text-dark'>{$item->title}</h6>
                                <p class='small text-muted mb-0'>{$item->description}</p>
                            </div>";
                            }
                        } else {
                            echo "<div class='text-muted small p-3 bg-white rounded-3 border shadow-sm'>No urgent bulletins published.</div>";
                        }
                        ?>
                    </div>

                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3"><i class="bi bi-link-45deg me-2 text-primary fs-5"></i>Campus Resources</h6>
                            <ul class="list-unstyled mb-0 small">
                                <li class="mb-2"><a href="http://www.aclc.edu.ph/" class="text-decoration-none text-secondary hover-primary"><i class="bi bi-chevron-right me-1"></i> ACLC Official Website</a></li>
                                <li class="mb-2"><a href="http://www.amaesonline.com/index_pscs.php" class="text-decoration-none text-secondary hover-primary"><i class="bi bi-chevron-right me-1"></i> Student Portal</a></li>
                            </ul>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>

    <script>
        // 1. Initialize Bootstrap Tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            })
        });

        // 2. SweetAlert2 Logout Confirmation
        function confirmLogout(event) {
            event.preventDefault(); // Stop instant redirect

            Swal.fire({
                title: 'Ready to leave?',
                text: "You are about to sign out of the ACLC Platform.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#003580',
                cancelButtonColor: '#dc3545',
                confirmButtonText: 'Yes, log me out',
                cancelButtonText: 'Stay logged in',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show a quick goodbye toast before redirecting
                    Swal.fire({
                        title: 'Logging out...',
                        icon: 'success',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 1000,
                        timerProgressBar: true
                    }).then(() => {
                        window.location.href = 'logout.php';
                    });
                }
            })
        }
    </script>
</body>

</html>