<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (isset($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    // Validation
    if (empty($email)) {
        $errors['email'] = 'Email is required.';
    } elseif (!validateEmail($email)) {
        $errors['email'] = 'Enter a valid email address (e.g., name@example.com).';
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT user_id, email, password_hash, first_name, last_name, user_type, is_active FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors['general'] = 'Invalid email or password. Please try again.';
        } elseif (!$user['is_active']) {
            $errors['general'] = 'Your account has been deactivated. Contact support.';
        } else {
            // Login success
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_type'] = $user['user_type'];

            // Update last login
            $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?")->execute([$user['user_id']]);

            // Remember me
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $pdo->prepare("INSERT INTO user_sessions (session_id, user_id, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))")->execute([$token, $user['user_id']]);
                setcookie('remember_token', $token, time() + 86400 * 30, '/', '', false, true);
            }

            setMessage('success', "Welcome back, {$user['first_name']}! 👋");
            redirect('dashboard.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - ScoutSphere</title>
<link rel="stylesheet" href="/scoutsphere/css/style.css">
    <style>
        .form-group.has-error input {
            border-color: #dc3545 !important;
            background: rgba(220,53,69,0.1);
        }
        .field-error {
            color: #ff6b6b;
            font-size: 13px;
            margin-top: 5px;
        }
        .general-error {
            background: rgba(220,53,69,0.15);
            border: 1px solid #dc3545;
            color: #ff6b6b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="page-auth">
    <div class="auth-container">
        <div class="auth-card">
            <h1>Welcome Back</h1>
            <p class="subtitle">Log in to your ScoutSphere account</p>

            <?php if (!empty($errors['general'])): ?>
                <div class="general-error">⚠️ <?php echo htmlspecialchars($errors['general']); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group <?php echo isset($errors['email']) ? 'has-error' : ''; ?>">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    <?php if (isset($errors['email'])): ?>
                        <div class="field-error">✗ <?php echo $errors['email']; ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group <?php echo isset($errors['password']) ? 'has-error' : ''; ?>">
                    <label>Password</label>
                    <input type="password" name="password" required>
                    <?php if (isset($errors['password'])): ?>
                        <div class="field-error">✗ <?php echo $errors['password']; ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-options">
                    <label><input type="checkbox" name="remember"> Remember me</label>
                    <a href="forgot-password.php">Forgot password?</a>
                </div>

                <button type="submit" class="btn btn-primary">Log In</button>

                <div class="auth-switch">
                    Don't have an account? <a href="signup.php">Sign up</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>