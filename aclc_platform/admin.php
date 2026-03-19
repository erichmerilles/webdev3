<?php
session_start();
date_default_timezone_set('Asia/Manila');

// Security Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

require_once 'config/db.php';

// Pulls in the backend logic file from the same root folder
require_once 'admin_logic.php';

// --- Analytics & Queries ---
$totalPosts = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$totalInteractions = $pdo->query("SELECT (SELECT COALESCE(SUM(likes),0) FROM posts) + (SELECT COUNT(*) FROM comments)")->fetchColumn();
$newsCount = $pdo->query("SELECT COUNT(*) FROM posts WHERE type='news'")->fetchColumn();
$eventCount = $pdo->query("SELECT COUNT(*) FROM posts WHERE type='event'")->fetchColumn();
$newsLikes = $pdo->query("SELECT COALESCE(SUM(likes), 0) FROM posts WHERE type='news'")->fetchColumn();
$eventLikes = $pdo->query("SELECT COALESCE(SUM(likes), 0) FROM posts WHERE type='event'")->fetchColumn();

$allPosts = $pdo->query("SELECT * FROM posts ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$allComments = $pdo->query("SELECT c.*, p.title as post_title FROM comments c LEFT JOIN posts p ON c.post_id = p.id ORDER BY c.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$allStudents = $pdo->query("SELECT * FROM users WHERE role='student' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="icon" type="image/*" href="assets/img/logo2.ico">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ACLC Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-elegant sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="admin.php">
                <div class="bg-primary text-white rounded p-2 me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;"><i class="bi bi-grid-1x2-fill fs-6"></i></div>
                ACLC Admin Workspace
            </a>
            <div class="d-flex align-items-center">
                <span class="text-dark me-4 small fw-medium"><i class="bi bi-person-circle me-1 opacity-75"></i> <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></span>
                <a href="index.php" class="btn btn-sm btn-outline-dark me-2 rounded-pill px-3"><i class="bi bi-box-arrow-up-right me-1"></i>View Feed</a>
                <button onclick="confirmLogout(event)" class="btn btn-sm btn-danger rounded-pill px-3"><i class="bi bi-power me-1"></i>Logout</button>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 py-5 max-w-7xl">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h3 class="fw-bold mb-1 text-dark">Dashboard Overview</h3>
                <p class="text-muted mb-0 small">Manage school publications, events, and user accounts.</p>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card kpi-card h-100">
                    <div class="card-body d-flex align-items-center p-4">
                        <div class="kpi-icon bg-primary bg-opacity-10 text-primary me-3"><i class="bi bi-journal-text"></i></div>
                        <div>
                            <h3 class="fw-bold mb-0 text-dark"><?= $totalPosts ?></h3><span class="text-muted small text-uppercase fw-bold" style="letter-spacing: 0.5px;">Total Posts</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card kpi-card h-100">
                    <div class="card-body d-flex align-items-center p-4">
                        <div class="kpi-icon bg-success bg-opacity-10 text-success me-3"><i class="bi bi-people-fill"></i></div>
                        <div>
                            <h3 class="fw-bold mb-0 text-dark"><?= $totalInteractions ?: 0 ?></h3><span class="text-muted small text-uppercase fw-bold" style="letter-spacing: 0.5px;">Interactions</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card kpi-card h-100">
                    <div class="card-body d-flex align-items-center p-4">
                        <div class="kpi-icon bg-info bg-opacity-10 text-info me-3"><i class="bi bi-person-badge-fill"></i></div>
                        <div>
                            <h3 class="fw-bold mb-0 text-dark"><?= count($allStudents) ?></h3><span class="text-muted small text-uppercase fw-bold" style="letter-spacing: 0.5px;">Students</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card kpi-card h-100">
                    <div class="card-body d-flex align-items-center p-4">
                        <div class="kpi-icon bg-warning bg-opacity-10 text-warning me-3"><i class="bi bi-calendar-event-fill"></i></div>
                        <div>
                            <h3 class="fw-bold mb-0 text-dark"><?= $eventCount ?></h3><span class="text-muted small text-uppercase fw-bold" style="letter-spacing: 0.5px;">ACLC Events</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header pt-4 pb-3">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-pencil-square text-primary me-2"></i>Create New Post</h6>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3"><label class="form-label text-secondary fw-semibold small">Title</label><input type="text" name="title" class="form-control bg-light" required></div>
                            <div class="mb-3"><label class="form-label text-secondary fw-semibold small">Category</label><select name="type" id="postTypeSelect" class="form-select bg-light">
                                    <option value="news">News Article</option>
                                    <option value="event">ACLC Event</option>
                                </select></div>
                            <div class="mb-3" id="eventDateContainer" style="display: none;"><label class="form-label text-secondary fw-semibold small">Actual Event Date <span class="text-danger">*</span></label><input type="date" name="event_date" class="form-control bg-light"></div>
                            <div class="mb-3"><label class="form-label text-secondary fw-semibold small">Cover Image</label><input type="file" name="post_image" class="form-control bg-light" accept="image/*"></div>
                            <div class="mb-3"><label class="form-label text-secondary fw-semibold small">Schedule Post (Optional)</label><input type="datetime-local" name="scheduled_date" class="form-control bg-light"></div>
                            <div class="mb-4"><label class="form-label text-secondary fw-semibold small">Content</label><textarea name="content" class="form-control bg-light" rows="4" required></textarea></div>
                            <button type="submit" name="submit_post" class="btn btn-primary w-100 py-2 fw-bold">Publish Item</button>
                        </form>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header pt-4 pb-3">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-pie-chart-fill text-warning me-2"></i>Engagement Split</h6>
                    </div>
                    <div class="card-body d-flex justify-content-center p-4">
                        <div style="width: 100%; max-width: 250px;"><canvas id="analyticsChart"></canvas></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">

                <div class="card mb-4 border-info border-opacity-25">
                    <div class="card-header pt-4 pb-3 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-people-fill text-info me-2"></i>Manage Student Accounts</h6>
                        <button class="btn btn-sm btn-info text-white rounded-pill px-3 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addStudentModal"><i class="bi bi-plus-lg me-1"></i>Add Student</button>
                    </div>
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table id="studentsTable" class="table table-hover table-custom align-middle mb-0 w-100 border-top">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Username (USN)</th>
                                        <th>Status</th>
                                        <th>Date Created</th>
                                        <th class="pe-4 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($allStudents)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-5 text-muted small">No student accounts found.</td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php foreach ($allStudents as $s): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-dark"><i class="bi bi-person-circle text-secondary me-2"></i><?= htmlspecialchars($s['username']) ?></td>
                                            <td>
                                                <?php if (isset($s['status']) && $s['status'] === 'pending'): ?>
                                                    <span class="badge bg-warning text-dark rounded-pill px-3 py-1">Pending</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1 border border-success border-opacity-25">Approved</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-muted small fw-medium"><?= date('M d, Y', strtotime($s['created_at'])) ?></td>
                                            <td class="pe-4 text-end text-nowrap">
                                                <?php if (isset($s['status']) && $s['status'] === 'pending'): ?>
                                                    <button onclick="window.location.href='admin.php?approve_user_id=<?= $s['id'] ?>'" class="btn btn-sm btn-light text-success border rounded-circle shadow-sm me-1" data-bs-toggle="tooltip" title="Approve Student" style="width:34px; height:34px; padding:0;"><i class="bi bi-check-lg"></i></button>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-sm btn-light text-primary border rounded-circle shadow-sm me-1"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editStudentModal"
                                                    data-id="<?= $s['id'] ?>"
                                                    data-username="<?= htmlspecialchars($s['username'], ENT_QUOTES) ?>"
                                                    data-firstname="<?= htmlspecialchars($s['first_name'] ?? '', ENT_QUOTES) ?>"
                                                    data-lastname="<?= htmlspecialchars($s['last_name'] ?? '', ENT_QUOTES) ?>"
                                                    data-email="<?= htmlspecialchars($s['email'] ?? '', ENT_QUOTES) ?>"
                                                    data-year="<?= htmlspecialchars($s['year_level'] ?? '', ENT_QUOTES) ?>"
                                                    data-section="<?= htmlspecialchars($s['section'] ?? '', ENT_QUOTES) ?>"
                                                    title="Edit User" style="width:34px; height:34px; padding:0;">
                                                    <i class="bi bi-pencil-fill"></i>
                                                </button>
                                                <button onclick="confirmDelete('admin.php?delete_user_id=<?= $s['id'] ?>', 'Student Account')" class="btn btn-sm btn-light text-danger border rounded-circle shadow-sm" data-bs-toggle="tooltip" title="Delete User" style="width:34px; height:34px; padding:0;"><i class="bi bi-trash3-fill"></i></button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card mb-4 border-danger border-opacity-25">
                    <div class="card-header bg-danger text-white py-3 border-bottom-0" style="border-radius: 1rem 1rem 0 0 !important;">
                        <h6 class="fw-bold mb-0"><i class="bi bi-bell-fill me-2"></i>Manage Urgent Bulletins</h6>
                    </div>
                    <div class="card-body bg-danger bg-opacity-10 p-4">
                        <form method="POST" class="row g-3 align-items-end mb-4">
                            <div class="col-md-3"><label class="small fw-bold text-danger opacity-75">Category</label><input type="text" name="xml_category" class="form-control bg-white" placeholder="e.g. URGENT" required></div>
                            <div class="col-md-4"><label class="small fw-bold text-danger opacity-75">Title</label><input type="text" name="xml_title" class="form-control bg-white" placeholder="Alert Title" required></div>
                            <div class="col-md-5"><label class="small fw-bold text-danger opacity-75">Description</label>
                                <div class="input-group"><input type="text" name="xml_description" class="form-control bg-white" placeholder="Short description..." required><button type="submit" name="submit_xml" class="btn btn-danger fw-bold">Broadcast</button></div>
                            </div>
                        </form>
                        <div class="table-responsive bg-white rounded-4 shadow-sm p-3 border border-danger border-opacity-10">
                            <table id="bulletinsTable" class="table table-hover align-middle mb-0 text-sm w-100 table-custom">
                                <thead>
                                    <tr>
                                        <th class="ps-3">Category</th>
                                        <th>Title</th>
                                        <th>Description</th>
                                        <th class="text-end pe-3">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (file_exists('data/announcements.xml') && count(($xml = simplexml_load_file('data/announcements.xml'))->announcement) > 0):
                                        foreach ($xml->announcement as $item): ?>
                                            <tr>
                                                <td class='ps-3'><span class='badge bg-danger text-white rounded-pill px-3 py-2 shadow-sm'><?= strtoupper($item->category) ?></span></td>
                                                <td class='fw-bold text-dark'><?= $item->title ?></td>
                                                <td class='text-muted small'><?= $item->description ?></td>
                                                <td class='text-end pe-3 text-nowrap'>
                                                    <button type='button' class='btn btn-sm btn-outline-primary rounded-circle me-1' data-bs-toggle='modal' data-bs-target='#editBulletinModal' data-id='<?= $item->id ?>' data-category='<?= htmlspecialchars($item->category, ENT_QUOTES) ?>' data-title='<?= htmlspecialchars($item->title, ENT_QUOTES) ?>' data-description='<?= htmlspecialchars($item->description, ENT_QUOTES) ?>' title='Edit Bulletin' style='width:32px; height:32px; padding:0;'><i class='bi bi-pencil-fill'></i></button>
                                                    <button onclick="confirmDelete('admin.php?delete_xml_id=<?= $item->id ?>', 'Urgent Bulletin')" class='btn btn-sm btn-outline-danger rounded-circle' data-bs-toggle='tooltip' title='Delete Bulletin' style='width:32px; height:32px; padding:0;'><i class='bi bi-trash-fill'></i></button>
                                                </td>
                                            </tr>
                                    <?php endforeach;
                                    else: echo "<tr><td colspan='4' class='text-center py-4 text-muted small'>No urgent bulletins currently active.</td></tr>";
                                    endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header pt-4 pb-3 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-card-list text-primary me-2"></i>Manage Publications</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table id="publicationsTable" class="table table-hover table-custom align-middle mb-0 w-100 border-top">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Title</th>
                                        <th>Category</th>
                                        <th>Date Posted</th>
                                        <th>Engagement</th>
                                        <th class="pe-4 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($allPosts)): ?><tr>
                                            <td colspan="5" class="text-center py-5 text-muted">No publications found. Create one to get started.</td>
                                        </tr><?php endif; ?>
                                    <?php foreach ($allPosts as $p): $isScheduled = strtotime($p['created_at']) > time(); ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center py-2"><span class="fw-bold text-dark text-truncate" style="max-width: 220px;" title="<?= htmlspecialchars($p['title']) ?>"><?= htmlspecialchars($p['title']) ?></span><?= $isScheduled ? '<span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 ms-2 flex-shrink-0 rounded-pill px-2">Scheduled</span>' : '' ?></div>
                                            </td>
                                            <td><span class="badge <?= $p['type'] == 'event' ? 'bg-warning bg-opacity-25 text-dark border border-warning border-opacity-50' : 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25' ?> rounded-pill px-3 py-2 shadow-sm"><?= ucfirst($p['type']) ?></span></td>
                                            <td class="text-muted small fw-medium"><?= date('M d, Y g:i A', strtotime($p['created_at'])) ?></td>
                                            <td>
                                                <div class="d-flex align-items-center text-secondary small fw-bold bg-light rounded-pill px-2 py-1 d-inline-flex border"><i class="bi bi-heart-fill text-danger me-2"></i> <?= $p['likes'] ?></div>
                                            </td>
                                            <td class="pe-4 text-end text-nowrap">
                                                <button type="button" class="btn btn-sm btn-light text-primary border rounded-circle shadow-sm me-1" data-bs-toggle="modal" data-bs-target="#editPostModal" data-id="<?= $p['id'] ?>" data-title="<?= htmlspecialchars($p['title'], ENT_QUOTES) ?>" data-type="<?= $p['type'] ?>" data-event-date="<?= $p['event_date'] ?? '' ?>" data-content="<?= htmlspecialchars($p['content'], ENT_QUOTES) ?>" title="Edit Post" style="width:34px; height:34px; padding:0;"><i class="bi bi-pencil-fill"></i></button>
                                                <button onclick="confirmDelete('admin.php?delete_id=<?= $p['id'] ?>', 'Campus Post')" class="btn btn-sm btn-light text-danger border rounded-circle shadow-sm" data-bs-toggle="tooltip" title="Delete Post" style="width:34px; height:34px; padding:0;"><i class="bi bi-trash3-fill"></i></button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header pt-4 pb-3 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-shield-shaded text-success me-2"></i>Moderate Student Comments</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table id="commentsTable" class="table table-hover table-custom align-middle mb-0 w-100 border-top">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Status</th>
                                        <th>Student</th>
                                        <th>Comment</th>
                                        <th>Posted On</th>
                                        <th>Date</th>
                                        <th class="pe-4 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($allComments)): ?><tr>
                                            <td colspan="6" class="text-center py-5 text-muted">No comments to moderate.</td>
                                        </tr><?php endif; ?>
                                    <?php
                                    $badWords = ['stupid', 'idiot', 'hate', 'dumb', 'fake'];
                                    foreach ($allComments as $c):
                                        $isFlagged = false;
                                        $commentLower = strtolower($c['comment_text']);
                                        foreach ($badWords as $word) {
                                            if (strpos($commentLower, $word) !== false) {
                                                $isFlagged = true;
                                                break;
                                            }
                                        }
                                    ?>
                                        <tr class="<?= $isFlagged ? 'bg-danger bg-opacity-10' : '' ?>">
                                            <td class="ps-4">
                                                <?php if ($isFlagged): ?> <span class="badge bg-danger rounded-pill shadow-sm px-2 py-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Flagged</span>
                                                <?php else: ?> <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-1 border border-success border-opacity-25">Clean</span> <?php endif; ?>
                                            </td>
                                            <td class="fw-bold text-dark text-nowrap"><?= htmlspecialchars($c['student_name']) ?></td>
                                            <td>
                                                <div class="text-truncate <?= $isFlagged ? 'fw-bold text-danger' : '' ?>" style="max-width: 200px;" title="<?= htmlspecialchars($c['comment_text']) ?>"><?= htmlspecialchars($c['comment_text']) ?></div>
                                            </td>
                                            <td class="text-muted small fst-italic"><span class="text-truncate d-inline-block" style="max-width: 150px; vertical-align: bottom;"><?= htmlspecialchars($c['post_title'] ?? 'Deleted Post') ?></span></td>
                                            <td class="text-muted small text-nowrap"><?= date('M d, Y', strtotime($c['created_at'])) ?></td>
                                            <td class="pe-4 text-end"><button onclick="confirmDelete('admin.php?delete_comment_id=<?= $c['id'] ?>', 'Comment')" class="btn btn-sm btn-light text-danger border rounded-circle shadow-sm" data-bs-toggle="tooltip" title="Delete Comment" style="width:34px; height:34px; padding:0;"><i class="bi bi-trash3-fill"></i></button></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php require_once 'admin_modals.php'; ?>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="assets/js/main.js"></script>

    <script>
        // Populate User Edit Modal
        const editStudentModal = document.getElementById('editStudentModal');
        if (editStudentModal) {
            editStudentModal.addEventListener('show.bs.modal', event => {
                const button = event.relatedTarget;
                document.getElementById('editUserId').value = button.getAttribute('data-id');
                document.getElementById('editUsername').value = button.getAttribute('data-username');
                document.getElementById('editFirstName').value = button.getAttribute('data-firstname');
                document.getElementById('editLastName').value = button.getAttribute('data-lastname');
                document.getElementById('editEmail').value = button.getAttribute('data-email');
                document.getElementById('editYear').value = button.getAttribute('data-year');
                document.getElementById('editSection').value = button.getAttribute('data-section');
            });
        }

        // DataTables Initialization
        if (window.jQuery && $.fn.DataTable) {
            if ($('#commentsTable').length) {
                $('#commentsTable').DataTable({
                    "pageLength": 5,
                    "lengthChange": false,
                    "ordering": false,
                    "info": true,
                    "language": {
                        "search": "",
                        "searchPlaceholder": "Search comments..."
                    }
                });
            }
            if ($('#studentsTable').length) {
                $('#studentsTable').DataTable({
                    "pageLength": 5,
                    "lengthChange": false,
                    "ordering": false,
                    "info": true,
                    "language": {
                        "search": "",
                        "searchPlaceholder": "Search students..."
                    }
                });
            }
        }

        const ctxEl = document.getElementById('analyticsChart');
        if (ctxEl) {
            new Chart(ctxEl.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['News Likes', 'Event Likes'],
                    datasets: [{
                        data: [<?= $newsLikes ?>, <?= $eventLikes ?>],
                        backgroundColor: ['#0d6efd', '#ffc107'],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    cutout: '75%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: {
                                    family: "'Inter', sans-serif"
                                }
                            }
                        }
                    }
                }
            });
        }

        // --- SweetAlert Notifications ---
        <?php if (isset($_GET['success']) || isset($_GET['xml_success'])): ?>
            Swal.fire({
                title: 'Success!',
                text: 'Action completed successfully!',
                icon: 'success',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true,
                customClass: {
                    popup: 'rounded-4 shadow-lg border'
                }
            });
        <?php endif; ?>

        <?php if (isset($_GET['deleted']) || isset($_GET['xml_deleted'])): ?>
            Swal.fire({
                title: 'Deleted!',
                text: 'Item deleted successfully.',
                icon: 'info',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true,
                customClass: {
                    popup: 'rounded-4 shadow-lg border'
                }
            });
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] == 'duplicate'): ?>
            Swal.fire({
                title: 'Error!',
                text: 'That username already exists. Please choose another.',
                icon: 'error',
                confirmButtonColor: '#0f172a',
                customClass: {
                    popup: 'rounded-4'
                }
            });
        <?php endif; ?>
    </script>
</body>

</html>