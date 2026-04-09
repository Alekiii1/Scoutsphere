<?php
// Get unread message count
$unread_messages = 0;
$unread_notifications = 0;

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->execute([$_SESSION['user_id']]);
    $unread_messages = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$_SESSION['user_id']]);
    $unread_notifications = $stmt->fetchColumn();
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="index.php" class="logo">ScoutSphere</a>
        <button class="sidebar-close" id="sidebarClose">✕</button>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="sidebar-menu">
            <li>
                <a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">📊</span>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="profile.php" class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">👤</span>
                    <span class="nav-text">My Profile</span>
                </a>
            </li>
            <li>
                <a href="upload.php" class="<?php echo $current_page == 'upload.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">⬆️</span>
                    <span class="nav-text">Upload Media</span>
                </a>
            </li>
            <li>
                <a href="messages.php" class="<?php echo $current_page == 'messages.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">💬</span>
                    <span class="nav-text">Messages</span>
                    <?php if ($unread_messages > 0): ?>
                        <span class="nav-badge"><?php echo $unread_messages; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="logout.php">
                    <span class="nav-icon">🚪</span>
                    <span class="nav-text">Logout</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>

<button class="sidebar-toggle" id="sidebarToggle">☰</button>
<div class="sidebar-overlay" id="sidebarOverlay"></div>