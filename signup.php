<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (isset($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $user_type = $_POST['user_type'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $terms = isset($_POST['terms']);

    // Validation
    if (strlen($first_name) < 2) $errors['first_name'] = 'First name must be at least 2 characters.';
    if (strlen($last_name) < 2) $errors['last_name'] = 'Last name must be at least 2 characters.';
    if (!validateEmail($email)) $errors['email'] = 'Enter a valid email address.';
    elseif (emailExists($pdo, $email)) $errors['email'] = 'This email is already registered. <a href="login.php">Log in</a> instead.';

    if (!in_array($user_type, ['athlete', 'scout', 'academy'])) $errors['user_type'] = 'Select a valid account type.';
    if (strlen($password) < 8) $errors['password'] = 'Password must be at least 8 characters.';
    elseif (!preg_match('/[A-Z]/', $password)) $errors['password'] = 'Password must include at least one uppercase letter.';
    elseif (!preg_match('/[0-9]/', $password)) $errors['password'] = 'Password must include at least one number.';
    if ($password !== $confirm) $errors['confirm_password'] = 'Passwords do not match.';
    if (!$terms) $errors['terms'] = 'You must agree to the Terms of Service.';

    if (empty($errors)) {
        $pdo->beginTransaction();
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, first_name, last_name, user_type, is_verified) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->execute([$email, $hash, $first_name, $last_name, $user_type]);
            $user_id = $pdo->lastInsertId();

            if ($user_type === 'athlete') {
                $pdo->prepare("INSERT INTO athlete_profiles (user_id, sport) VALUES (?, 'football')")->execute([$user_id]);
            } elseif ($user_type === 'scout') {
                $pdo->prepare("INSERT INTO scout_profiles (user_id) VALUES (?)")->execute([$user_id]);
            } elseif ($user_type === 'academy') {
                $pdo->prepare("INSERT INTO academy_profiles (user_id, academy_name) VALUES (?, ?)")->execute([$user_id, "$first_name $last_name"]);
            }

            $pdo->commit();
            setMessage('success', 'Account created! Please log in.');
            redirect('login.php');
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors['general'] = 'Registration failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign Up - ScoutSphere</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .form-group.has-error input, .form-group.has-error select {
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
        .password-strength-bar {
            height: 4px;
            background: #333;
            margin-top: 5px;
        }
        .strength-fill {
            width: 0%;
            height: 100%;
            transition: width 0.3s;
        }
    </style>
</head>
<body class="page-auth">
    <div class="auth-container">
        <div class="auth-card">
            <h1>Join ScoutSphere</h1>
            <p class="subtitle">Create your free account</p>

            <?php if (!empty($errors['general'])): ?>
                <div class="general-error">⚠️ <?php echo $errors['general']; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-row">
                    <div class="form-group <?php echo isset($errors['first_name']) ? 'has-error' : ''; ?>">
                        <label>First Name *</label>
                        <input type="text" name="first_name" value="<?php echo htmlspecialchars($old['first_name'] ?? ''); ?>">
                        <?php if (isset($errors['first_name'])): ?>
                            <div class="field-error">✗ <?php echo $errors['first_name']; ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group <?php echo isset($errors['last_name']) ? 'has-error' : ''; ?>">
                        <label>Last Name *</label>
                        <input type="text" name="last_name" value="<?php echo htmlspecialchars($old['last_name'] ?? ''); ?>">
                        <?php if (isset($errors['last_name'])): ?>
                            <div class="field-error">✗ <?php echo $errors['last_name']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group <?php echo isset($errors['email']) ? 'has-error' : ''; ?>">
                    <label>Email *</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>">
                    <?php if (isset($errors['email'])): ?>
                        <div class="field-error">✗ <?php echo $errors['email']; ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group <?php echo isset($errors['user_type']) ? 'has-error' : ''; ?>">
                    <label>I am a *</label>
                    <select name="user_type">
                        <option value="">Select type</option>
                        <option value="athlete" <?php echo ($old['user_type'] ?? '') === 'athlete' ? 'selected' : ''; ?>>Athlete</option>
                        <option value="scout" <?php echo ($old['user_type'] ?? '') === 'scout' ? 'selected' : ''; ?>>Scout</option>
                        <option value="academy" <?php echo ($old['user_type'] ?? '') === 'academy' ? 'selected' : ''; ?>>Academy</option>
                    </select>
                    <?php if (isset($errors['user_type'])): ?>
                        <div class="field-error">✗ <?php echo $errors['user_type']; ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group <?php echo isset($errors['password']) ? 'has-error' : ''; ?>">
                    <label>Password *</label>
                    <input type="password" name="password" id="password" onkeyup="checkStrength()">
                    <div class="password-strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                    <small>Min 8 chars, one uppercase, one number</small>
                    <?php if (isset($errors['password'])): ?>
                        <div class="field-error">✗ <?php echo $errors['password']; ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group <?php echo isset($errors['confirm_password']) ? 'has-error' : ''; ?>">
                    <label>Confirm Password *</label>
                    <input type="password" name="confirm_password">
                    <?php if (isset($errors['confirm_password'])): ?>
                        <div class="field-error">✗ <?php echo $errors['confirm_password']; ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group <?php echo isset($errors['terms']) ? 'has-error' : ''; ?>">
                    <label class="checkbox-wrapper">
                        <input type="checkbox" name="terms" <?php echo isset($old['terms']) ? 'checked' : ''; ?>>
                        I agree to the <a href="#">Terms</a>
                    </label>
                    <?php if (isset($errors['terms'])): ?>
                        <div class="field-error">✗ <?php echo $errors['terms']; ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary">Create Account</button>

                <div class="auth-switch">
                    Already have an account? <a href="login.php">Log in</a>
                </div>
            </form>
        </div>
    </div>
    <script>
        function checkStrength() {
            let pwd = document.getElementById('password').value;
            let strength = 0;
            if (pwd.length >= 8) strength++;
            if (/[A-Z]/.test(pwd) && /[a-z]/.test(pwd)) strength++;
            if (/[0-9]/.test(pwd)) strength++;
            let fill = document.getElementById('strengthFill');
            if (strength === 1) fill.style.width = '33%', fill.style.background = '#dc3545';
            else if (strength === 2) fill.style.width = '66%', fill.style.background = '#ffc107';
            else if (strength === 3) fill.style.width = '100%', fill.style.background = '#28a745';
            else fill.style.width = '0%';
        }
    </script>
</body>
</html>