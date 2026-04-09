<?php
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Send message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['receiver_id'], $_POST['content'])) {
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, subject, content) 
                          VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['user_id'],
        $_POST['receiver_id'],
        $_POST['subject'] ?? '',
        $_POST['content']
    ]);
    
    // Create notification
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message) 
                          VALUES (?, 'message', 'New Message', ?)");
    $stmt->execute([
        $_POST['receiver_id'],
        'You have a new message from ' . $_SESSION['user_name']
    ]);
    
    header('Location: messages.php?sent=1');
    exit;
}

// Mark as read
if (isset($_GET['read']) && isset($_GET['id'])) {
    $stmt = $pdo->prepare("UPDATE messages SET is_read = 1, read_at = NOW() 
                          WHERE message_id = ? AND receiver_id = ?");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
}

// Get messages
$stmt = $pdo->prepare("
    SELECT m.*, u.first_name, u.last_name,
           CASE WHEN m.sender_id = ? THEN 'sent' ELSE 'received' END as direction
    FROM messages m
    JOIN users u ON (m.sender_id = u.user_id OR m.receiver_id = u.user_id) AND u.user_id != ?
    WHERE m.sender_id = ? OR m.receiver_id = ?
    ORDER BY m.created_at DESC
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
$messages = $stmt->fetchAll();

// Get users for dropdown
$stmt = $pdo->prepare("SELECT user_id, first_name, last_name, user_type FROM users 
                      WHERE user_id != ? AND is_active = 1 ORDER BY first_name");
$stmt->execute([$_SESSION['user_id']]);
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Messages - ScoutSphere</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="page-dashboard">
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <h2>Messages</h2>
        
        <?php if (isset($_GET['sent'])): ?>
            <div class="alert alert-success">Message sent!</div>
        <?php endif; ?>
        
        <!-- New Message -->
        <div class="card" style="background: #1a1a1a; padding: 20px; border-radius: 16px; margin-bottom: 20px;">
            <h3>New Message</h3>
            <form method="POST">
                <div class="form-group">
                    <label>To</label>
                    <select name="receiver_id" required>
                        <option value="">Select recipient...</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?php echo $u['user_id']; ?>">
                                <?php echo $u['first_name'] . ' ' . $u['last_name']; ?> (<?php echo $u['user_type']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Subject</label>
                    <input type="text" name="subject">
                </div>
                <div class="form-group">
                    <label>Message</label>
                    <textarea name="content" rows="3" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Send</button>
            </form>
        </div>
        
        <!-- Inbox -->
        <h3>Inbox</h3>
        <?php foreach ($messages as $msg): ?>
            <div class="card" style="background: #1a1a1a; padding: 15px; border-radius: 12px; margin-bottom: 15px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <strong>
                        <?php echo $msg['direction'] === 'received' ? 'From: ' : 'To: '; ?>
                        <?php echo htmlspecialchars($msg['first_name'] . ' ' . $msg['last_name']); ?>
                    </strong>
                    <small style="color: #666;"><?php echo date('M j, g:i a', strtotime($msg['created_at'])); ?></small>
                </div>
                <p><strong><?php echo htmlspecialchars($msg['subject']); ?></strong></p>
                <p style="color: #ccc;"><?php echo nl2br(htmlspecialchars($msg['content'])); ?></p>
                
                <?php if ($msg['direction'] === 'received' && !$msg['is_read']): ?>
                    <span style="background: #0b5ed7; color: white; padding: 2px 8px; border-radius: 10px; font-size: 12px;">New</span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </main>
</body>
</html>