<?php
require_once '../includes/config.php';

$errors = [];

// Initialize login tracking
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = time();
}

$max_attempts = 5;
$lockout_time = 60; // seconds

// Check for lockout
if ($_SESSION['login_attempts'] >= $max_attempts && (time() - $_SESSION['last_attempt_time']) < $lockout_time) {
    $errors[] = "Too many login attempts. Please try again after {$lockout_time} seconds.";
} else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = sanitizeInput($_POST['username']);
        $password = $_POST['password'];
        $captcha = $_POST['captcha'] ?? '';

        if (empty($username)) $errors[] = "Username is required";
        if (empty($password)) $errors[] = "Password is required";

        // CAPTCHA check after 3 failed attempts
        if ($_SESSION['login_attempts'] >= 3) {
            if (empty($captcha) || strtolower($captcha) !== strtolower($_SESSION['captcha_code'])) {
                $errors[] = "CAPTCHA verification failed";
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['login_attempts'] = 0; // reset on success
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = (bool)$user['is_admin'];
                redirect($_SESSION['is_admin'] ? '../admin/dashboard.php' : '../profile/view.php');
            } else {
                $_SESSION['login_attempts']++;
                $_SESSION['last_attempt_time'] = time();
                $errors[] = "Invalid username or password";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Recycle Platform</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light">
    <?php include '../includes/header.php'; ?>
    
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white text-center">
                        <h4><i class="fas fa-sign-in-alt me-2"></i>Login</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= $error ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form action="login.php" method="post">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username or Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>
                            
                            <?php if ($_SESSION['login_attempts'] >= 3): ?>
                                <div class="mb-3">
                                    <label for="captcha" class="form-label">Enter CAPTCHA</label>
                                    <div class="input-group">
                                        <img src="captcha.php" alt="CAPTCHA" class="me-2">
                                        <input type="text" class="form-control" name="captcha" required>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-3">
                            <p class="mb-1">Don't have an account?</p>
                            <a href="register.php" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-user-plus me-1"></i>Register here
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

