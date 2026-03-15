<?php
session_start();
require_once 'config/db.php';

// Redirect if already logged in based on their role
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

$error = '';
$login_success = false;
$redirect_url = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
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

        // Flag the success and determine where they should go, but don't redirect just yet!
        $login_success = true;
        $redirect_url = ($user['role'] === 'admin') ? 'admin.php' : 'index.php';
    } else {
        $error = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>ACLC Portal Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="login-page">

    <div class="card shadow-lg login-card">
        <div class="brand-header">
            <h3 class="mb-0 fw-bold"><i class="bi bi-building me-2"></i>ACLC PLATFORM</h3>
            <small>Campus Portal</small>
        </div>
        <div class="card-body p-4">

            <form method="POST" action="login.php">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">
                        <i class="bi bi-person-fill me-1"></i>Student ID or Admin Username
                    </label>
                    <input type="text" name="username" class="form-control" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label text-muted small fw-bold">
                        <i class="bi bi-lock-fill me-1"></i>Password
                    </label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 fw-bold" style="background-color: #003580; border-color: #003580;">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Access Portal
                </button>
            </form>

            <div class="text-center mt-4">
                <span class="text-muted small">
                    <i class="bi bi-shield-lock me-1"></i>Secure Campus Authentication
                </span>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <?php if ($login_success): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Authentication Successful',
                    text: 'Welcome to the ACLC Platform!',
                    showConfirmButton: false,
                    timer: 1500,
                    timerProgressBar: true,
                    allowOutsideClick: false,
                    heightAuto: false // Stops the login card from jumping up!
                }).then(() => {
                    window.location.href = '<?= $redirect_url ?>';
                });
            });
        </script>
    <?php elseif (!empty($error)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Authentication Failed',
                    text: '<?= htmlspecialchars($error) ?>',
                    confirmButtonColor: '#003580',
                    confirmButtonText: 'Try Again',
                    heightAuto: false // Stops the login card from jumping up!
                });
            });
        </script>
    <?php endif; ?>

</body>

</html>