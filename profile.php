<?php
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

// Get profile
$stmt = $pdo->prepare("SELECT u.*, ap.* FROM users u 
                      LEFT JOIN athlete_profiles ap ON u.user_id = ap.user_id 
                      WHERE u.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$profile = $stmt->fetch();

// Update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("UPDATE users SET 
            first_name = ?, last_name = ?, bio = ?, phone = ?, city = ?
            WHERE user_id = ?");
        $stmt->execute([
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['bio'],
            $_POST['phone'],
            $_POST['city'],
            $_SESSION['user_id']
        ]);
        
        if ($profile['user_type'] === 'athlete' && $profile['athlete_id']) {
            $stmt = $pdo->prepare("UPDATE athlete_profiles SET 
                sport = ?, position = ?, height_cm = ?, weight_kg = ?,
                current_club = ?, achievements = ?
                WHERE user_id = ?");
            $stmt->execute([
                $_POST['sport'],
                $_POST['position'],
                $_POST['height_cm'],
                $_POST['weight_kg'],
                $_POST['current_club'],
                $_POST['achievements'],
                $_SESSION['user_id']
            ]);
        }
        
        $pdo->commit();
        $message = 'Profile updated!';
        
        // Refresh data
        $stmt = $pdo->prepare("SELECT u.*, ap.* FROM users u 
                              LEFT JOIN athlete_profiles ap ON u.user_id = ap.user_id 
                              WHERE u.user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $profile = $stmt->fetch();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Update failed';
    }
}

// Calculate completion
$completion = 0;
$fields = ['first_name', 'last_name', 'bio', 'phone', 'city'];
foreach ($fields as $f) if (!empty($profile[$f])) $completion += 10;
if ($profile['user_type'] === 'athlete') {
    foreach (['sport', 'position', 'height_cm', 'current_club'] as $f) {
        if (!empty($profile[$f])) $completion += 10;
    }
}
$completion = min(100, $completion);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile - ScoutSphere</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .completion-bar {
            background: #333;
            height: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .completion-fill {
            background: #0b5ed7;
            height: 100%;
            border-radius: 5px;
            transition: width 0.3s;
        }
    </style>
</head>
<body class="page-dashboard">
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <h2>Edit Profile</h2>
        
        <div class="card" style="background: #1a1a1a; padding: 20px; border-radius: 16px; margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between;">
                <span>Profile Completion</span>
                <span><?php echo $completion; ?>%</span>
            </div>
            <div class="completion-bar">
                <div class="completion-fill" style="width: <?php echo $completion; ?>%"></div>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" class="auth-form">
            <h3>Basic Information</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($profile['first_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($profile['last_name']); ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" value="<?php echo htmlspecialchars($profile['email']); ?>" disabled style="opacity: 0.5;">
            </div>
            
            <div class="form-group">
                <label>Phone</label>
                <input type="tel" name="phone" value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>City</label>
                <input type="text" name="city" value="<?php echo htmlspecialchars($profile['city'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Bio</label>
                <textarea name="bio" rows="4"><?php echo htmlspecialchars($profile['bio'] ?? ''); ?></textarea>
            </div>
            
            <?php if ($profile['user_type'] === 'athlete'): ?>
                <h3>Athlete Information</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Sport</label>
                        <select name="sport">
                            <option value="football" <?php echo ($profile['sport'] ?? '') === 'football' ? 'selected' : ''; ?>>Football</option>
                            <option value="basketball" <?php echo ($profile['sport'] ?? '') === 'basketball' ? 'selected' : ''; ?>>Basketball</option>
                            <option value="rugby" <?php echo ($profile['sport'] ?? '') === 'rugby' ? 'selected' : ''; ?>>Rugby</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Position</label>
                        <input type="text" name="position" value="<?php echo htmlspecialchars($profile['position'] ?? ''); ?>" placeholder="e.g., Striker">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Height (cm)</label>
                        <input type="number" name="height_cm" value="<?php echo $profile['height_cm'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Weight (kg)</label>
                        <input type="number" name="weight_kg" value="<?php echo $profile['weight_kg'] ?? ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Current Club</label>
                    <input type="text" name="current_club" value="<?php echo htmlspecialchars($profile['current_club'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Achievements</label>
                    <textarea name="achievements" rows="3"><?php echo htmlspecialchars($profile['achievements'] ?? ''); ?></textarea>
                </div>
            <?php endif; ?>
            
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </main>
</body>
</html>