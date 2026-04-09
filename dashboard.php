<?php
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user info
$stmt = $pdo->prepare("SELECT u.*, ap.profile_completion_percent, ap.sport, ap.position 
                       FROM users u 
                       LEFT JOIN athlete_profiles ap ON u.user_id = ap.user_id 
                       WHERE u.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get stats
$stmt = $pdo->prepare("SELECT COALESCE(SUM(view_count), 0) FROM profile_views 
                       WHERE viewed_user_id = ? AND view_date >= CURDATE() - INTERVAL 30 DAY");
$stmt->execute([$_SESSION['user_id']]);
$views = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM shortlists WHERE athlete_user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$shortlists = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM media WHERE user_id = ? AND media_type = 'video'");
$stmt->execute([$_SESSION['user_id']]);
$videos = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM media WHERE user_id = ? AND media_type = 'photo'");
$stmt->execute([$_SESSION['user_id']]);
$photos = $stmt->fetchColumn();

// Get recent activity
$stmt = $pdo->prepare("SELECT al.*, u.first_name as actor_name, u.last_name as actor_last 
                       FROM activity_log al 
                       LEFT JOIN users u ON al.actor_user_id = u.user_id 
                       WHERE al.user_id = ? 
                       ORDER BY al.created_at DESC 
                       LIMIT 5");
$stmt->execute([$_SESSION['user_id']]);
$activities = $stmt->fetchAll();

// Get recent media
$stmt = $pdo->prepare("SELECT * FROM media WHERE user_id = ? ORDER BY uploaded_at DESC LIMIT 4");
$stmt->execute([$_SESSION['user_id']]);
$recent_media = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ScoutSphere</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .welcome-banner {
            background: linear-gradient(135deg, rgba(116, 52, 211, 0.2) 0%, rgba(11, 94, 215, 0.2) 100%);
            border: 1px solid rgba(116, 52, 211, 0.3);
            border-radius: 16px;
            padding: 25px 30px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #1a1a1a;
            border-radius: 16px;
            padding: 25px;
            border-top: 4px solid #0b5ed7;
        }
        .stat-card h3 {
            font-size: 2.5rem;
            color: #0b5ed7;
            margin-bottom: 5px;
        }
        .stat-card p {
            color: #aaa;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        .activity-list {
            list-style: none;
        }
        .activity-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #333;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .activity-icon {
            width: 40px;
            height: 40px;
            background: #222;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        .media-preview {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        .media-thumb-small {
            aspect-ratio: 1;
            background: #222;
            border-radius: 8px;
            overflow: hidden;
        }
        .media-thumb-small img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            .welcome-banner {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
        }
    </style>
</head>
<body class="page-dashboard">
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
	<!-- Show session messages -->
    <?php showMessage(); ?>

        <header class="top-nav">
            <div class="search-bar">
                <input type="search" placeholder="Search...">
            </div>
            <div class="nav-actions">
                <button class="icon-btn">🔔</button>
                <div class="user-badge">
                    <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                </div>
            </div>
        </header>
        
        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <div>
                <h2>Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>! 👋</h2>
                <p style="color: #aaa; margin-top: 5px;">
                    <?php if ($user['profile_completion_percent'] < 100): ?>
                        Your profile is <?php echo $user['profile_completion_percent']; ?>% complete. 
                        <a href="profile.php" style="color: #0b5ed7;">Add more details</a> to attract scouts.
                    <?php else: ?>
                        Your profile is complete! Scouts can now find you.
                    <?php endif; ?>
                </p>
            </div>
            <a href="upload.php" class="btn btn-primary">+ Upload Media</a>
        </div>
        
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $views; ?></h3>
                <p>Profile Views (30 days)</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $shortlists; ?></h3>
                <p>Scout Shortlists</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $videos; ?></h3>
                <p>Videos</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $photos; ?></h3>
                <p>Photos</p>
            </div>
        </div>
        
        <!-- Main Grid -->
        <div class="dashboard-grid">
            <!-- Activity Feed -->
            <div>
                <h3 style="margin-bottom: 20px;">Recent Activity</h3>
                <div class="activity-card" style="background: #1a1a1a; border-radius: 16px; padding: 20px;">
                    <?php if (empty($activities)): ?>
                        <p style="color: #666; text-align: center; padding: 40px;">
                            No activity yet. Upload media to get noticed!
                        </p>
                    <?php else: ?>
                        <ul class="activity-list">
                            <?php foreach ($activities as $activity): ?>
                                <li class="activity-item">
                                    <div class="activity-icon">
                                        <?php
                                        $icons = [
                                            'profile_view' => '👁️',
                                            'shortlist_add' => '⭐',
                                            'message_received' => '💬',
                                            'media_view' => '🎬',
                                            'milestone' => '🏆'
                                        ];
                                        echo $icons[$activity['activity_type']] ?? '📌';
                                        ?>
                                    </div>
                                    <div style="flex: 1;">
                                        <p style="color: #ddd;">
                                            <?php 
                                            $meta = json_decode($activity['metadata_json'], true);
                                            echo htmlspecialchars($meta['preview'] ?? $activity['activity_type']); 
                                            ?>
                                        </p>
                                        <p style="color: #666; font-size: 12px; margin-top: 3px;">
                                            <?php echo date('M j, g:i a', strtotime($activity['created_at'])); ?>
                                        </p>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Quick Preview -->
            <div>
                <h3 style="margin-bottom: 20px;">Recent Uploads</h3>
                <?php if (empty($recent_media)): ?>
                    <div style="background: #1a1a1a; border-radius: 16px; padding: 30px; text-align: center;">
                        <p style="color: #666;">No uploads yet</p>
                        <a href="upload.php" class="btn btn-outline" style="margin-top: 15px;">Upload Now</a>
                    </div>
                <?php else: ?>
                    <div class="media-preview">
                        <?php foreach ($recent_media as $m): ?>
                            <div class="media-thumb-small">
                                <?php if ($m['media_type'] === 'video'): ?>
                                    <div style="width: 100%; height: 100%; background: #222; display: flex; align-items: center; justify-content: center; color: #666;">
                                        🎬
                                    </div>
                                <?php else: ?>
                                    <img src="<?php echo $m['file_url']; ?>" alt="">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="upload.php" style="display: block; text-align: center; margin-top: 15px; color: #0b5ed7;">View All</a>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <script>
        // Mobile sidebar toggle
        document.getElementById('sidebarToggle')?.addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('sidebarOverlay').classList.toggle('active');
        });
        
        document.getElementById('sidebarClose')?.addEventListener('click', () => {
            document.getElementById('sidebar').classList.remove('active');
            document.getElementById('sidebarOverlay').classList.remove('active');
        });
        
        document.getElementById('sidebarOverlay')?.addEventListener('click', () => {
            document.getElementById('sidebar').classList.remove('active');
            document.getElementById('sidebarOverlay').classList.remove('active');
        });
    </script>
</body>
</html>