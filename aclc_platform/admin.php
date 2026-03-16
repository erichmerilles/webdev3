<?php
session_start();

// Lock the server time to Philippine Standard Time so scheduled dates are 100% accurate
date_default_timezone_set('Asia/Manila');

// Security Check: Kick out unauthenticated users or students
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

require_once 'config/db.php';

$error = '';
$login_success = false;
$redirect_url = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['username']) && isset($_POST['password'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Select the user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify the hashed password
    if ($user && password_verify($password, $user['password_hash'])) {
        // Set universal session variables for RBAC
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        // Flag the success and determine where they should go
        $login_success = true;
        $redirect_url = ($user['role'] === 'admin') ? 'admin.php' : 'index.php';
    } else {
        $error = "Invalid username or password.";
    }
}

// --- Handle Campus Post Update (NEW) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_post'])) {
    $editId = (int)$_POST['edit_id'];
    $title = $_POST['title'];
    $content = $_POST['content'];
    $type = $_POST['type'];
    $eventDate = !empty($_POST['event_date']) ? $_POST['event_date'] : null;

    $stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ?, type = ?, event_date = ? WHERE id = ?");
    $stmt->execute([$title, $content, $type, $eventDate, $editId]);
    header("Location: admin.php?success=1");
    exit();
}

// --- Handle Urgent Bulletin Update (NEW) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_xml'])) {
    $editId = $_POST['edit_xml_id'];
    $xmlFile = 'data/announcements.xml';
    if (file_exists($xmlFile)) {
        $xml = new DOMDocument();
        $xml->load($xmlFile);
        $xpath = new DOMXPath($xml);

        // Find the specific announcement
        $announcements = $xpath->query("//announcement[id='$editId']");
        foreach ($announcements as $node) {
            // Safely update each child node using relative XPath instead of getElementsByTagName
            $categoryNode = $xpath->query("category", $node)->item(0);
            if ($categoryNode) $categoryNode->nodeValue = htmlspecialchars($_POST['xml_category']);

            $titleNode = $xpath->query("title", $node)->item(0);
            if ($titleNode) $titleNode->nodeValue = htmlspecialchars($_POST['xml_title']);

            $descNode = $xpath->query("description", $node)->item(0);
            if ($descNode) $descNode->nodeValue = htmlspecialchars($_POST['xml_description']);
        }
        $xml->save($xmlFile);
    }
    header("Location: admin.php?xml_success=1");
    exit();
}

// --- Handle Campus Post Deletion ---
if (isset($_GET['delete_id'])) {
    $deleteId = (int)$_GET['delete_id'];

    // First, find and delete the associated image file if it exists
    $stmt = $pdo->prepare("SELECT image_path FROM posts WHERE id = ?");
    $stmt->execute([$deleteId]);
    $post = $stmt->fetch();

    if ($post && !empty($post['image_path']) && file_exists($post['image_path'])) {
        unlink($post['image_path']);
    }

    // Then, delete the record from the system
    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
    if ($stmt->execute([$deleteId])) {
        header("Location: admin.php?deleted=1");
        exit();
    }
}

// --- Handle Urgent Bulletin Deletion ---
if (isset($_GET['delete_xml_id'])) {
    $deleteXmlId = $_GET['delete_xml_id'];
    $xmlFile = 'data/announcements.xml';
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

// --- Handle Campus Post Creation & SCHEDULING ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_post'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $type = $_POST['type'];
    $imagePath = null;

    // Determine the creation date: Use selected schedule date, or default to right now
    $scheduledDate = !empty($_POST['scheduled_date']) ? date('Y-m-d H:i:s', strtotime($_POST['scheduled_date'])) : date('Y-m-d H:i:s');

    // Capture the actual event date if it was provided
    $eventDate = !empty($_POST['event_date']) ? $_POST['event_date'] : null;

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

    // Include the scheduled date AND event date into the insert query
    $stmt = $pdo->prepare("INSERT INTO posts (title, content, type, image_path, created_at, event_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$title, $content, $type, $imagePath, $scheduledDate, $eventDate]);
    header("Location: admin.php?success=1");
    exit();
}

// --- Handle Urgent Bulletin Creation ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_xml'])) {
    $xmlFile = 'data/announcements.xml';
    $xml = new DOMDocument('1.0', 'UTF-8');
    $xml->preserveWhiteSpace = false;
    $xml->formatOutput = true; // Keeps the file cleanly indented

    if (file_exists($xmlFile)) {
        $xml->load($xmlFile);
    } else {
        $xml->appendChild($xml->createElement('campus_updates'));
    }

    $root = $xml->documentElement;

    // Build the new node
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

// --- Fetch all posts for the Management Table ---
$allPostsStmt = $pdo->query("SELECT * FROM posts ORDER BY created_at DESC");
$allPosts = $allPostsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="icon" type="image/*" href="assets/img/logo2.ico">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ACLC Admin Dashboard</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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
                <div class="bg-primary text-white rounded p-2 me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    <i class="bi bi-grid-1x2-fill fs-6"></i>
                </div>
                ACLC Admin Workspace
            </a>
            <div class="d-flex align-items-center">
                <span class="text-dark me-4 small fw-medium">
                    <i class="bi bi-person-circle me-1 opacity-75"></i> <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?>
                </span>
                <a href="index.php" class="btn btn-sm btn-outline-dark me-2 rounded-pill px-3" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Return to Student Feed">
                    <i class="bi bi-box-arrow-up-right me-1"></i>View Feed
                </a>
                <button onclick="confirmLogout(event)" class="btn btn-sm btn-danger rounded-pill px-3" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Sign out of Admin Panel">
                    <i class="bi bi-power me-1"></i>Logout
                </button>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 py-5 max-w-7xl">

        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h3 class="fw-bold mb-1 text-dark">Dashboard Overview</h3>
                <p class="text-muted mb-0 small">Manage school publications, events, and urgent broadcasts.</p>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card kpi-card h-100">
                    <div class="card-body d-flex align-items-center p-4">
                        <div class="kpi-icon bg-primary bg-opacity-10 text-primary me-3"><i class="bi bi-journal-text"></i></div>
                        <div>
                            <h3 class="fw-bold mb-0 text-dark"><?= $totalPosts ?></h3>
                            <span class="text-muted small text-uppercase fw-bold" style="letter-spacing: 0.5px;">Total Posts</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card kpi-card h-100">
                    <div class="card-body d-flex align-items-center p-4">
                        <div class="kpi-icon bg-success bg-opacity-10 text-success me-3"><i class="bi bi-people-fill"></i></div>
                        <div>
                            <h3 class="fw-bold mb-0 text-dark"><?= $totalInteractions ?: 0 ?></h3>
                            <span class="text-muted small text-uppercase fw-bold" style="letter-spacing: 0.5px;">Interactions</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card kpi-card h-100">
                    <div class="card-body d-flex align-items-center p-4">
                        <div class="kpi-icon bg-info bg-opacity-10 text-info me-3"><i class="bi bi-megaphone-fill"></i></div>
                        <div>
                            <h3 class="fw-bold mb-0 text-dark"><?= $newsCount ?></h3>
                            <span class="text-muted small text-uppercase fw-bold" style="letter-spacing: 0.5px;">News Articles</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card kpi-card h-100">
                    <div class="card-body d-flex align-items-center p-4">
                        <div class="kpi-icon bg-warning bg-opacity-10 text-warning me-3"><i class="bi bi-calendar-event-fill"></i></div>
                        <div>
                            <h3 class="fw-bold mb-0 text-dark"><?= $eventCount ?></h3>
                            <span class="text-muted small text-uppercase fw-bold" style="letter-spacing: 0.5px;">ACLC Events</span>
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
                            <div class="mb-3">
                                <label class="form-label text-secondary fw-semibold small">Title</label>
                                <input type="text" name="title" class="form-control bg-light" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-secondary fw-semibold small">Category</label>
                                <select name="type" id="postTypeSelect" class="form-select bg-light">
                                    <option value="news">News Article</option>
                                    <option value="event">ACLC Event</option>
                                </select>
                            </div>
                            <div class="mb-3" id="eventDateContainer" style="display: none;">
                                <label class="form-label text-secondary fw-semibold small">Actual Event Date <span class="text-danger">*</span></label>
                                <input type="date" name="event_date" class="form-control bg-light">
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-secondary fw-semibold small">Cover Image</label>
                                <input type="file" name="post_image" class="form-control bg-light" accept="image/*">
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-secondary fw-semibold small">Schedule Post (Optional)</label>
                                <input type="datetime-local" name="scheduled_date" class="form-control bg-light">
                            </div>
                            <div class="mb-4">
                                <label class="form-label text-secondary fw-semibold small">Content</label>
                                <textarea name="content" class="form-control bg-light" rows="4" required></textarea>
                            </div>
                            <button type="submit" name="submit_post" class="btn btn-primary w-100 py-2 fw-bold">Publish Item</button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header pt-4 pb-3">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-pie-chart-fill text-warning me-2"></i>Engagement Split</h6>
                    </div>
                    <div class="card-body d-flex justify-content-center p-4">
                        <div style="width: 100%; max-width: 250px;">
                            <canvas id="analyticsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">

                <div class="card mb-4 border-danger border-opacity-25">
                    <div class="card-header bg-danger text-white py-3 border-bottom-0" style="border-radius: 1rem 1rem 0 0 !important;">
                        <h6 class="fw-bold mb-0"><i class="bi bi-bell-fill me-2"></i>Manage Urgent Bulletins</h6>
                    </div>
                    <div class="card-body bg-danger bg-opacity-10 p-4">
                        <form method="POST" class="row g-3 align-items-end mb-4">
                            <div class="col-md-3">
                                <label class="small fw-bold text-danger opacity-75">Category</label>
                                <input type="text" name="xml_category" class="form-control bg-white" placeholder="e.g. URGENT" required>
                            </div>
                            <div class="col-md-4">
                                <label class="small fw-bold text-danger opacity-75">Title</label>
                                <input type="text" name="xml_title" class="form-control bg-white" placeholder="Alert Title" required>
                            </div>
                            <div class="col-md-5">
                                <label class="small fw-bold text-danger opacity-75">Description</label>
                                <div class="input-group">
                                    <input type="text" name="xml_description" class="form-control bg-white" placeholder="Short description..." required>
                                    <button type="submit" name="submit_xml" class="btn btn-danger fw-bold">Broadcast</button>
                                </div>
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
                                    <?php
                                    $xmlFile = 'data/announcements.xml';
                                    if (file_exists($xmlFile)) {
                                        $xml = simplexml_load_file($xmlFile);
                                        if (count($xml->announcement) > 0) {
                                            foreach ($xml->announcement as $item) {
                                                echo "<tr>";
                                                echo "<td class='ps-3'><span class='badge bg-danger text-white rounded-pill px-3 py-2 shadow-sm'>" . strtoupper($item->category) . "</span></td>";
                                                echo "<td class='fw-bold text-dark'>{$item->title}</td>";
                                                echo "<td class='text-muted small'>{$item->description}</td>";
                                                echo "<td class='text-end pe-3 text-nowrap'>
                                                                <button type='button' class='btn btn-sm btn-outline-primary rounded-circle me-1' data-bs-toggle='modal' data-bs-target='#editBulletinModal' data-id='{$item->id}' data-category='" . htmlspecialchars($item->category, ENT_QUOTES) . "' data-title='" . htmlspecialchars($item->title, ENT_QUOTES) . "' data-description='" . htmlspecialchars($item->description, ENT_QUOTES) . "' title='Edit Bulletin' style='width:32px; height:32px; padding:0;'>
                                                                    <i class='bi bi-pencil-fill'></i>
                                                                </button>
                                                                <button onclick=\"confirmDelete('admin.php?delete_xml_id={$item->id}', 'Urgent Bulletin')\" class='btn btn-sm btn-outline-danger rounded-circle' data-bs-toggle='tooltip' title='Delete Bulletin' style='width:32px; height:32px; padding:0;'>
                                                                    <i class='bi bi-trash-fill'></i>
                                                                </button>
                                                              </td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='4' class='text-center py-4 text-muted small'>No urgent bulletins currently active.</td></tr>";
                                        }
                                    }
                                    ?>
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
                                    <?php if (empty($allPosts)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">No publications found. Create one to get started.</td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php foreach ($allPosts as $p): ?>
                                        <?php
                                        // Check if the post's creation date is in the future
                                        $isScheduled = strtotime($p['created_at']) > time();
                                        ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center py-2">
                                                    <span class="fw-bold text-dark text-truncate" style="max-width: 220px;" title="<?= htmlspecialchars($p['title']) ?>">
                                                        <?= htmlspecialchars($p['title']) ?>
                                                    </span>
                                                    <?php if ($isScheduled): ?>
                                                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 ms-2 flex-shrink-0 rounded-pill px-2">Scheduled</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge <?= $p['type'] == 'event' ? 'bg-warning bg-opacity-25 text-dark border border-warning border-opacity-50' : 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25' ?> rounded-pill px-3 py-2 shadow-sm">
                                                    <?= ucfirst($p['type']) ?>
                                                </span>
                                            </td>
                                            <td class="text-muted small fw-medium">
                                                <?= date('M d, Y g:i A', strtotime($p['created_at'])) ?>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center text-secondary small fw-bold bg-light rounded-pill px-2 py-1 d-inline-flex border">
                                                    <i class="bi bi-heart-fill text-danger me-2"></i> <?= $p['likes'] ?>
                                                </div>
                                            </td>
                                            <td class="pe-4 text-end text-nowrap">
                                                <button type="button" class="btn btn-sm btn-light text-primary border rounded-circle shadow-sm me-1" data-bs-toggle="modal" data-bs-target="#editPostModal" data-id="<?= $p['id'] ?>" data-title="<?= htmlspecialchars($p['title'], ENT_QUOTES) ?>" data-type="<?= $p['type'] ?>" data-event-date="<?= $p['event_date'] ?? '' ?>" data-content="<?= htmlspecialchars($p['content'], ENT_QUOTES) ?>" title="Edit Post" style="width:34px; height:34px; padding:0;">
                                                    <i class="bi bi-pencil-fill"></i>
                                                </button>
                                                <button onclick="confirmDelete('admin.php?delete_id=<?= $p['id'] ?>', 'Campus Post')" class="btn btn-sm btn-light text-danger border rounded-circle shadow-sm" data-bs-toggle="tooltip" title="Delete Post" style="width:34px; height:34px; padding:0;">
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

    <div class="modal fade" id="editPostModal" tabindex="-1" aria-labelledby="editPostModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold" id="editPostModalLabel">Edit Campus Post</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="edit_id" id="editPostId">
                        <div class="mb-3">
                            <label class="form-label text-secondary fw-semibold small">Title</label>
                            <input type="text" name="title" id="editPostTitle" class="form-control bg-light" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-secondary fw-semibold small">Category</label>
                            <select name="type" id="editPostType" class="form-select bg-light">
                                <option value="news">News Article</option>
                                <option value="event">ACLC Event</option>
                            </select>
                        </div>
                        <div class="mb-3" id="editEventDateContainer" style="display: none;">
                            <label class="form-label text-secondary fw-semibold small">Actual Event Date</label>
                            <input type="date" name="event_date" id="editEventDate" class="form-control bg-light">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-secondary fw-semibold small">Content</label>
                            <textarea name="content" id="editPostContent" class="form-control bg-light" rows="6" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_post" class="btn btn-primary rounded-pill px-4 fw-bold">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editBulletinModal" tabindex="-1" aria-labelledby="editBulletinModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold" id="editBulletinModalLabel">Edit Urgent Bulletin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="edit_xml_id" id="editXmlId">
                        <div class="mb-3">
                            <label class="form-label text-secondary fw-semibold small">Category</label>
                            <input type="text" name="xml_category" id="editXmlCategory" class="form-control bg-light" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-secondary fw-semibold small">Title</label>
                            <input type="text" name="xml_title" id="editXmlTitle" class="form-control bg-light" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-secondary fw-semibold small">Description</label>
                            <input type="text" name="xml_description" id="editXmlDescription" class="form-control bg-light" required>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_xml" class="btn btn-danger rounded-pill px-4 fw-bold">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script src="assets/js/main.js"></script>

    <script>
        // Chart.js requires PHP variables to render the split
        const ctxEl = document.getElementById('analyticsChart');
        if (ctxEl) {
            const ctx = ctxEl.getContext('2d');
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

        // SweetAlert Toast Notifications for PHP Success/Delete triggers
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
    </script>
</body>

</html>