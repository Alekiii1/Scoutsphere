<?php
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['media'])) {
    $file = $_FILES['media'];
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $media_type = (strpos($file['type'], 'video') !== false) ? 'video' : 'photo';
    
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/quicktime'];
    $max_size = 100 * 1024 * 1024;
    
    if (!in_array($file['type'], $allowed_types)) {
        $error = 'Invalid file type. Allowed: JPG, PNG, GIF, MP4, MOV';
    } elseif ($file['size'] > $max_size) {
        $error = 'File too large. Maximum 100MB';
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Upload failed. Error code: ' . $file['error'];
    } else {
        $upload_dir = 'uploads/' . $media_type . 's/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $ext;
        $filepath = $upload_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $file_size_mb = round($file['size'] / 1024 / 1024, 2);
            
            $stmt = $pdo->prepare("INSERT INTO media 
                (user_id, media_type, title, description, file_url, file_size_mb, processing_status) 
                VALUES (?, ?, ?, ?, ?, ?, 'completed')");
            $stmt->execute([
                $_SESSION['user_id'], 
                $media_type, 
                $title, 
                $description, 
                $filepath, 
                $file_size_mb
            ]);
            
            $message = 'Upload successful!';
        } else {
            $error = 'Failed to save file';
        }
    }
}

// Get user's media
$stmt = $pdo->prepare("SELECT * FROM media WHERE user_id = ? ORDER BY uploaded_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$media = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Media - ScoutSphere</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .upload-area {
            border: 3px dashed #333;
            border-radius: 16px;
            padding: 60px;
            text-align: center;
            margin-bottom: 30px;
            cursor: pointer;
        }
        .upload-area:hover {
            border-color: #0b5ed7;
            background: rgba(11, 94, 215, 0.05);
        }
        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        .media-item {
            background: #1a1a1a;
            border-radius: 12px;
            overflow: hidden;
        }
        .media-thumb {
            aspect-ratio: 1;
            background: #222;
        }
        .media-thumb img, .media-thumb video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .media-info {
            padding: 15px;
        }
    </style>
</head>
<body class="page-dashboard">
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <h2>Upload Media</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="upload-area" onclick="document.getElementById('fileInput').click()">
                <p style="font-size: 48px;">📁</p>
                <h3>Click to select file</h3>
                <p style="color: #666;">JPG, PNG, GIF, MP4, MOV (Max 100MB)</p>
                <input type="file" name="media" id="fileInput" accept="image/*,video/*" style="display: none;" required onchange="this.form.submit()">
            </div>
            
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" placeholder="e.g., Match Highlights" required>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3"></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">Upload</button>
        </form>
        
        <h3 style="margin-top: 40px;">Your Media</h3>
        
        <?php if (empty($media)): ?>
            <p style="color: #666;">No media uploaded yet.</p>
        <?php else: ?>
            <div class="media-grid">
                <?php foreach ($media as $item): ?>
                    <div class="media-item">
                        <div class="media-thumb">
                            <?php if ($item['media_type'] === 'video'): ?>
                                <video src="<?php echo $item['file_url']; ?>" preload="metadata"></video>
                            <?php else: ?>
                                <img src="<?php echo $item['file_url']; ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                            <?php endif; ?>
                        </div>
                        <div class="media-info">
                            <h4><?php echo htmlspecialchars($item['title']); ?></h4>
                            <p style="color: #666; font-size: 12px;"><?php echo $item['media_type']; ?> • <?php echo $item['file_size_mb']; ?> MB</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>