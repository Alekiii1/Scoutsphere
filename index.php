<?php
require_once 'includes/db.php';

// Get stats for homepage
$athletes = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'athlete'")->fetchColumn();
$scouts = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'scout'")->fetchColumn();
$academies = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'academy'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ScoutSphere – Home</title>
  <link rel="stylesheet" href="css/style.css" />
</head>
<body class="page-home">
  <nav class="navbar">
    <div class="logo"><a href="index.php">ScoutSphere</a></div>
    <ul class="nav-links">
      <li><a href="index.php" class="active">Home</a></li>
      <li><a href="#how-it-works">How It Works</a></li>
      <?php if (isset($_SESSION['user_id'])): ?>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="logout.php">Logout</a></li>
      <?php else: ?>
        <li><a href="login.php">Sign In</a></li>
      <?php endif; ?>
    </ul>
  </nav>

  <section class="hero">
    <div class="hero-container">
      <div class="hero-tagline">WIN TOGETHER</div>
      <h1 class="hero-title gradient-text">SCOUTSPHERE</h1>
      <p class="hero-subtitle">where talent meets opportunity</p>
      <div class="hero-cta">
        <a href="signup.php" class="btn btn-primary btn-lg">Get Started</a>
        <a href="#how-it-works" class="btn btn-outline btn-lg">Learn More</a>
      </div>
      <div class="hero-stats">
        <div class="stat">
          <span class="stat-number"><?php echo $athletes; ?></span>
          <span class="stat-label">Athletes</span>
        </div>
        <div class="stat">
          <span class="stat-number"><?php echo $scouts; ?></span>
          <span class="stat-label">Scouts</span>
        </div>
        <div class="stat">
          <span class="stat-number"><?php echo $academies; ?></span>
          <span class="stat-label">Academies</span>
        </div>
      </div>
    </div>
  </section>

  <div class="split-sections" id="how-it-works">
    <section class="section section-dark">
      <h2 class="section-title">How It Works</h2>
      <div class="cards">
        <div class="card">
          <h3>Create Profile</h3>
          <p>Athletes create a sports profile with basic details and achievements.</p>
        </div>
        <div class="card">
          <h3>Upload Media</h3>
          <p>Upload high-quality videos and photos showcasing your skills.</p>
        </div>
        <div class="card">
          <h3>Get Discovered</h3>
          <p>Scouts search, review, and identify talent for opportunities.</p>
        </div>
      </div>
    </section>

    <section class="section section-accent">
      <h2 class="section-title">Who It's For</h2>
      <div class="cards">
        <div class="card">
          <h3>🏃 Athletes</h3>
          <p>Gain visibility without expensive trials. Showcase your talent worldwide.</p>
        </div>
        <div class="card">
          <h3>🔍 Scouts</h3>
          <p>Discover talent efficiently from anywhere. Filter by sport, position, location.</p>
        </div>
        <div class="card">
          <h3>🏫 Academies</h3>
          <p>Build structured talent pipelines. Manage recruitment operations.</p>
        </div>
      </div>
    </section>
  </div>

  <footer class="site-footer">
    <p>© 2026 ScoutSphere. All rights reserved.</p>
  </footer>
</body>
</html>