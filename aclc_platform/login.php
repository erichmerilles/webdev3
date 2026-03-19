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
$success_msg = '';
$login_success = false;
$redirect_url = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // === Handle Login ===
    if (isset($_POST['action']) && $_POST['action'] === 'login') {
        $username = trim($_POST['username']); // They log in with USN
        $password = $_POST['password'];

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            // NEW: Check if the account is approved before letting them in!
            if (isset($user['status']) && $user['status'] === 'pending') {
                $error = "Your account is pending admin approval. Please check back later.";
            } else {
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['first_name'] = $user['first_name'] ?? '';
                $_SESSION['last_name'] = $user['last_name'] ?? '';

                // Optional: You can store their real name in the session now if you want to use it!
                $_SESSION['first_name'] = $user['first_name'] ?? 'Student';

                $login_success = true;
                $redirect_url = ($user['role'] === 'admin') ? 'admin.php' : 'index.php';
            }
        } else {
            $error = "Invalid USN or password.";
        }
    }
    // === Handle Registration (Sign Up) ===
    elseif (isset($_POST['action']) && $_POST['action'] === 'register') {
        $usn = trim($_POST['reg_usn']);
        $first_name = trim($_POST['reg_firstname']);
        $last_name = trim($_POST['reg_lastname']);
        $email = trim($_POST['reg_email']);
        $year_level = trim($_POST['reg_year']);
        $section = trim($_POST['reg_section']);
        $password = $_POST['reg_password'];
        $confirm = $_POST['reg_confirm'];

        // Basic Validation
        if (empty($usn) || empty($first_name) || empty($last_name) || empty($email) || empty($year_level) || empty($section) || empty($password)) {
            $error = "All fields are required.";
        } elseif ($password !== $confirm) {
            $error = "Passwords do not match.";
        } else {
            // Check if USN already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$usn]);
            if ($stmt->fetch()) {
                $error = "That USN is already registered.";
            } else {
                // Securely hash the password and save everything to the database
                $hashed = password_hash($password, PASSWORD_DEFAULT);

                // Note: Because of your database update, 'status' defaults to 'pending' automatically!
                $stmt = $pdo->prepare("INSERT INTO users (username, first_name, last_name, email, year_level, section, password_hash, role) VALUES (?, ?, ?, ?, ?, ?, ?, 'student')");

                if ($stmt->execute([$usn, $first_name, $last_name, $email, $year_level, $section, $hashed])) {
                    $success_msg = "Account created successfully! Please wait for admin approval before logging in.";
                } else {
                    $error = "An error occurred. Please try again.";
                }
            }
        }
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
            <small id="formSubtitle">School Portal</small>
        </div>
        <div class="card-body p-4">

            <div id="loginFormContainer">
                <form method="POST" action="login.php">
                    <input type="hidden" name="action" value="login">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">
                            <i class="bi bi-person-fill me-1"></i>USN (Student Number)
                        </label>
                        <input type="text" name="username" class="form-control" required autofocus>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold">
                            <i class="bi bi-lock-fill me-1"></i>Password
                        </label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold mb-3" style="background-color: #003580; border-color: #003580;">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Access Portal
                    </button>
                </form>
                <div class="text-center">
                    <span class="text-muted small">New student? </span>
                    <button type="button" class="btn btn-link p-0 small fw-bold text-decoration-none" onclick="toggleForms()" style="color: #003580;">Sign up here</button>
                </div>
            </div>

            <div id="registerFormContainer" class="d-none">
                <form method="POST" action="login.php">
                    <input type="hidden" name="action" value="register">

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold"><i class="bi bi-person-badge-fill me-1"></i>USN</label>
                        <input type="text" name="reg_usn" class="form-control" placeholder="e.g 1900123123" required maxlength="11" inputmode="numeric" oninput="this.value=this.value.replace(/\D/g,'')">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted small fw-bold"><i class="bi bi-person-vcard me-1"></i>First Name</label>
                            <input type="text" name="reg_firstname" class="form-control" placeholder="First Name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted small fw-bold"><i class="bi bi-person-vcard-fill me-1"></i>Last Name</label>
                            <input type="text" name="reg_lastname" class="form-control" placeholder="Last Name" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold"><i class="bi bi-envelope-fill me-1"></i>Email</label>
                        <input type="email" name="reg_email" class="form-control" placeholder="Email address..." required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted small fw-bold"><i class="bi bi-mortarboard-fill me-1"></i>Year Level</label>
                            <select name="reg_year" class="form-select" required>
                                <option value="" disabled selected>Select Year</option>
                                <option value="Grade 11">Grade 11</option>
                                <option value="Grade 12">Grade 12</option>
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted small fw-bold"><i class="bi bi-diagram-3-fill me-1"></i>Section</label>
                            <input type="text" name="reg_section" class="form-control" placeholder="Section..." required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold"><i class="bi bi-shield-lock-fill me-1"></i>Password</label>
                        <input type="password" name="reg_password" class="form-control" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold"><i class="bi bi-shield-check me-1"></i>Confirm Password</label>
                        <input type="password" name="reg_confirm" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-success w-100 fw-bold mb-3">
                        <i class="bi bi-person-plus-fill me-2"></i>Create Account
                    </button>
                </form>
                <div class="text-center">
                    <span class="text-muted small">Already have an account? </span>
                    <button type="button" class="btn btn-link p-0 small fw-bold text-decoration-none" onclick="toggleForms()" style="color: #003580;">Login here</button>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Toggle between Login and Sign Up forms
        function toggleForms() {
            const loginForm = document.getElementById('loginFormContainer');
            const registerForm = document.getElementById('registerFormContainer');
            const subtitle = document.getElementById('formSubtitle');

            if (loginForm.classList.contains('d-none')) {
                loginForm.classList.remove('d-none');
                registerForm.classList.add('d-none');
                subtitle.innerText = "Student Registration";
            } else {
                loginForm.classList.add('d-none');
                registerForm.classList.remove('d-none');
                subtitle.innerText = "School Portal";
            }
        }
    </script>

    <?php if ($login_success): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Welcome to the ACLC Platform!',
                    text: 'Authentication Successful',
                    showConfirmButton: false,
                    timer: 1500,
                    timerProgressBar: true,
                    allowOutsideClick: false,
                    heightAuto: false
                }).then(() => {
                    window.location.href = '<?= $redirect_url ?>';
                });
            });
        </script>
    <?php elseif (!empty($success_msg)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Registration Successful!',
                    text: '<?= htmlspecialchars($success_msg) ?>',
                    confirmButtonColor: '#003580',
                    confirmButtonText: 'Go to Login',
                    heightAuto: false
                });
            });
        </script>
    <?php elseif (!empty($error)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: '<?= htmlspecialchars($error) ?>',
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'Try Again',
                    heightAuto: false
                }).then(() => {
                    // Keep the registration form open if the error occurred during registration
                    <?php if (isset($_POST['action']) && $_POST['action'] === 'register'): ?>
                        toggleForms();
                    <?php endif; ?>
                });
            });
        </script>
    <?php endif; ?>

</body>

</html>