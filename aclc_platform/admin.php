<?php
session_start();

// Security Check: Kick out unauthenticated users or students
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

require_once 'config/db.php';
$xmlFile = 'data/announcements.xml';

// --- Handle MySQL Post Deletion ---
if (isset($_GET['delete_id'])) {
    $deleteId = (int)$_GET['delete_id'];

    // First, find and delete the associated image file if it exists
    $stmt = $pdo->prepare("SELECT image_path FROM posts WHERE id = ?");
    $stmt->execute([$deleteId]);
    $post = $stmt->fetch();

    if ($post && !empty($post['image_path']) && file_exists($post['image_path'])) {
        unlink($post['image_path']);
    }

    // Then, delete the record from the database
    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
    if ($stmt->execute([$deleteId])) {
        header("Location: admin.php?deleted=1");
        exit();
    }
}

// --- Handle Urgent Bulletin (XML) Deletion ---
if (isset($_GET['delete_xml_id'])) {
    $deleteXmlId = $_GET['delete_xml_id'];
    if (file_exists($xmlFile)) {
        $xml = new DOMDocument();
        $xml->load($xmlFile);
        $xpath = new DOMXPath($xml);

        // Find the specific announcement by its ID and remove it
        foreach ($xpath->query("//announcement[id='$deleteXmlId']") as $node) {
            $node->parentNode->removeChild($node);
        }
        $xml->save($xmlFile);
    }
    header("Location: admin.php?xml_deleted=1");
    exit();
}

// --- Handle MySQL Post Creation ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_post'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $type = $_POST['type'];
    $imagePath = null;

    // Process file upload securely
    if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'assets/img/uploads/';
        $fileName = time() . '_' . basename($_FILES['post_image']['name']);
        $targetFilePath = $uploadDir . $fileName;
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

        $allowTypes = array('jpg', 'png', 'jpeg', 'gif', 'webp');
        if (in_array($fileType, $allowTypes)) {
            if (move_uploaded_file($_FILES['post_image']['tmp_name'], $targetFilePath)) {
                $imagePath = $targetFilePath;
            }
        }
    }

    $stmt = $pdo->prepare("INSERT INTO posts (title, content, type, image_path) VALUES (?, ?, ?, ?)");
    $stmt->execute([$title, $content, $type, $imagePath]);
    header("Location: admin.php?success=1");
    exit();
}

// --- Handle Urgent Bulletin (XML) Creation ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_xml'])) {
    $xml = new DOMDocument('1.0', 'UTF-8');
    $xml->preserveWhiteSpace = false;
    $xml->formatOutput = true; // Keeps the XML file cleanly indented

    if (file_exists($xmlFile)) {
        $xml->load($xmlFile);
    } else {
        $xml->appendChild($xml->createElement('campus_updates'));
    }

    $root = $xml->documentElement;

    // Build the new XML node
    $announcement = $xml->createElement('announcement');
    $announcement->appendChild($xml->createElement('id', time())); // Use timestamp as unique ID
    $announcement->appendChild($xml->createElement('category', htmlspecialchars($_POST['xml_category'])));
    $announcement->appendChild($xml->createElement('title', htmlspecialchars($_POST['xml_title'])));
    $announcement->appendChild($xml->createElement('date', date('Y-m-d')));
    $announcement->appendChild($xml->createElement('description', htmlspecialchars($_POST['xml_description'])));

    $root->appendChild($announcement);
    $xml->save($xmlFile);

    header("Location: admin.php?xml_success=1");
    exit();
}

// --- Analytics Queries ---
$totalPosts = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$totalInteractions = $pdo->query("SELECT (SELECT SUM(likes) FROM posts) + (SELECT COUNT(*) FROM comments)")->fetchColumn();
$newsCount = $pdo->query("SELECT COUNT(*) FROM posts WHERE type='news'")->fetchColumn();
$eventCount = $pdo->query("SELECT COUNT(*) FROM posts WHERE type='event'")->fetchColumn();
$newsLikes = $pdo->query("SELECT COALESCE(SUM(likes), 0) FROM posts WHERE type='news'")->fetchColumn();
$eventLikes = $pdo->query("SELECT COALESCE(SUM(likes), 0) FROM posts WHERE type='event'")->fetchColumn();

// --- Fetch all posts for the Data Table ---
$allPostsStmt = $pdo->query("SELECT * FROM posts ORDER BY created_at DESC");
$allPosts = $allPostsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ACLC Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="#">
                <i class="bi bi-grid-1x2-fill me-2 text-primary"></i>
                ACLC Admin Workspace
            </a>
            <div class="d-flex align-items-center">
                <span class="text-light me-4 small">
                    <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($_SESSION['username']) ?>
                </span>
                <a href="index.php" class="btn btn-sm btn-outline-light me-2" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Return to Student Feed">
                    <i class="bi bi-box-arrow-up-right me-1"></i>View Feed
                </a>
                <button onclick="confirmLogout(event)" class="btn btn-sm btn-danger" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Sign out of Admin Panel">
                    <i class="bi bi-box-arrow-right me-1"></i>Logout
                </button>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 py-4 max-w-7xl">

        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h3 class="fw-bold mb-1">Dashboard Overview</h3>
                <p class="text-muted mb-0 small">Manage campus publications, events, and urgent broadcasts.</p>
            </div>
        </div>

        <?php if (isset($_GET['success']) || isset($_GET['xml_success'])): ?>
            <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-4 rounded-3 auto-close-alert">
                <i class="bi bi-check-circle-fill me-2 fs-5"></i> Item published successfully!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['deleted']) || isset($_GET['xml_deleted'])): ?>
            <div class="alert alert-dark border-0 shadow-sm d-flex align-items-center mb-4 rounded-3 auto-close-alert">
                <i class="bi bi-trash3-fill me-2 fs-5"></i> Item deleted successfully.
            </div>
        <?php endif; ?>

        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="kpi-icon bg-primary bg-opacity-10 text-primary me-3"><i class="bi bi-journal-text"></i></div>
                        <div>
                            <h4 class="fw-bold mb-0"><?= $totalPosts ?></h4>
                            <span class="text-muted small text-uppercase fw-semibold">Total Posts</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="kpi-icon bg-success bg-opacity-10 text-success me-3"><i class="bi bi-people-fill"></i></div>
                        <div>
                            <h4 class="fw-bold mb-0"><?= $totalInteractions ?: 0 ?></h4>
                            <span class="text-muted small text-uppercase fw-semibold">Interactions</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="kpi-icon bg-info bg-opacity-10 text-info me-3"><i class="bi bi-megaphone-fill"></i></div>
                        <div>
                            <h4 class="fw-bold mb-0"><?= $newsCount ?></h4>
                            <span class="text-muted small text-uppercase fw-semibold">News Articles</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="kpi-icon bg-warning bg-opacity-10 text-warning me-3"><i class="bi bi-calendar-event-fill"></i></div>
                        <div>
                            <h4 class="fw-bold mb-0"><?= $eventCount ?></h4>
                            <span class="text-muted small text-uppercase fw-semibold">Campus Events</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">

            <div class="col-lg-4">

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h6 class="fw-bold mb-0"><i class="bi bi-pencil-square text-primary me-2"></i>Create New Post</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label text-muted fw-semibold small">Title</label>
                                <input type="text" name="title" class="form-control bg-light border-0" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted fw-semibold small">Category</label>
                                <select name="type" class="form-select bg-light border-0">
                                    <option value="news">News Article</option>
                                    <option value="event">Campus Event</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted fw-semibold small">Cover Image</label>
                                <input type="file" name="post_image" class="form-control bg-light border-0" accept="image/*">
                            </div>
                            <div class="mb-4">
                                <label class="form-label text-muted fw-semibold small">Content</label>
                                <textarea name="content" class="form-control bg-light border-0" rows="4" required></textarea>
                            </div>
                            <button type="submit" name="submit_post" class="btn btn-primary w-100 fw-bold rounded-3">Publish Item</button>
                        </form>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h6 class="fw-bold mb-0"><i class="bi bi-pie-chart-fill text-warning me-2"></i>Engagement Split</h6>
                    </div>
                    <div class="card-body d-flex justify-content-center">
                        <div style="width: 100%; max-width: 250px;">
                            <canvas id="analyticsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-danger text-white border-bottom-0 py-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-bell-fill me-2"></i>Manage Urgent Bulletins</h6>
                    </div>
                    <div class="card-body bg-light pb-4">
                        <form method="POST" class="row g-2 align-items-end mb-4">
                            <div class="col-md-3">
                                <label class="small fw-bold text-muted">Category</label>
                                <input type="text" name="xml_category" class="form-control" placeholder="e.g. URGENT" required>
                            </div>
                            <div class="col-md-4">
                                <label class="small fw-bold text-muted">Title</label>
                                <input type="text" name="xml_title" class="form-control" placeholder="Alert Title" required>
                            </div>
                            <div class="col-md-5">
                                <label class="small fw-bold text-muted">Description</label>
                                <div class="input-group">
                                    <input type="text" name="xml_description" class="form-control" placeholder="Short description..." required>
                                    <button type="submit" name="submit_xml" class="btn btn-danger fw-bold">Broadcast Alert</button>
                                </div>
                            </div>
                        </form>

                        <div class="table-responsive bg-white rounded-3 shadow-sm">
                            <table class="table table-hover align-middle mb-0 text-sm">
                                <tbody>
                                    <?php
                                    if (file_exists($xmlFile)) {
                                        $xml = simplexml_load_file($xmlFile);
                                        if (count($xml->announcement) > 0) {
                                            foreach ($xml->announcement as $item) {
                                                echo "<tr>";
                                                echo "<td class='ps-3'><span class='badge bg-danger bg-opacity-10 text-danger border border-danger'>{$item->category}</span></td>";
                                                echo "<td class='fw-bold text-dark'>{$item->title}</td>";
                                                echo "<td class='text-muted small'>{$item->description}</td>";
                                                echo "<td class='text-end pe-3'>
                                                        <button onclick=\"confirmDelete('admin.php?delete_xml_id={$item->id}', 'Urgent Bulletin')\" class='btn btn-sm btn-light text-danger' data-bs-toggle='tooltip' title='Delete Bulletin'>
                                                            <i class='bi bi-trash-fill'></i>
                                                        </button>
                                                      </td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='4' class='text-center py-3 text-muted small'>No urgent bulletins currently active.</td></tr>";
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-card-list text-primary me-2"></i>Manage Publications</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-custom align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4 border-0">Title</th>
                                        <th class="border-0">Category</th>
                                        <th class="border-0">Date Posted</th>
                                        <th class="border-0">Engagement</th>
                                        <th class="pe-4 text-end border-0">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($allPosts)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">No publications found. Create one to get started.</td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php foreach ($allPosts as $p): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold text-dark text-truncate" style="max-width: 250px;"><?= htmlspecialchars($p['title']) ?></div>
                                            </td>
                                            <td>
                                                <span class="badge <?= $p['type'] == 'event' ? 'bg-warning bg-opacity-25 text-dark border border-warning' : 'bg-primary bg-opacity-25 text-primary border border-primary' ?> rounded-pill">
                                                    <?= ucfirst($p['type']) ?>
                                                </span>
                                            </td>
                                            <td class="text-muted small fw-semibold"><?= date('M d, Y', strtotime($p['created_at'])) ?></td>
                                            <td>
                                                <div class="d-flex align-items-center text-secondary small fw-bold">
                                                    <i class="bi bi-heart-fill text-danger me-1"></i> <?= $p['likes'] ?>
                                                </div>
                                            </td>
                                            <td class="pe-4 text-end">
                                                <button onclick="confirmDelete('admin.php?delete_id=<?= $p['id'] ?>', 'Post')" class="btn btn-sm btn-light text-danger fw-bold rounded-3" data-bs-toggle="tooltip" title="Delete Post">
                                                    <i class="bi bi-trash3-fill"></i>
                                                </button>
                                            </td>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // 1. Initialize Bootstrap Tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            })
        });

        // 2. Chart.js Initialization
        const ctx = document.getElementById('analyticsChart').getContext('2d');
        new Chart(ctx, {
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
                            padding: 20
                        }
                    }
                }
            }
        });

        // 3. SweetAlert2 Logout Confirmation
        function confirmLogout(event) {
            event.preventDefault();
            Swal.fire({
                title: 'Sign out of Admin?',
                text: "You will need your credentials to access the Control Panel again.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#212529',
                cancelButtonColor: '#dc3545',
                confirmButtonText: 'Yes, log me out',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Logging out...',
                        icon: 'success',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 1000
                    }).then(() => {
                        window.location.href = 'logout.php';
                    });
                }
            })
        }

        // 4. SweetAlert2 Universal Delete Confirmation
        function confirmDelete(deleteUrl, itemType) {
            Swal.fire({
                title: 'Are you sure?',
                text: "This " + itemType + " will be permanently deleted. This action cannot be undone.",
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = deleteUrl;
                }
            })
        }

        // 5. Auto-close Alerts
        setTimeout(() => {
            const alerts = document.querySelectorAll('.auto-close-alert');
            alerts.forEach(alert => {
                alert.style.transition = "opacity 0.8s ease-out, margin 0.8s ease-out, padding 0.8s ease-out";
                alert.style.opacity = "0";

                setTimeout(() => {
                    alert.style.height = "0px";
                    alert.style.padding = "0px";
                    alert.style.marginBottom = "0px";
                    alert.style.overflow = "hidden";

                    setTimeout(() => {
                        alert.remove();
                    }, 800);
                }, 800);
            });
        }, 3000); // Waits 3 seconds before starting the fade
    </script>
</body>

</html>