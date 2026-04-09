<?php
// ========== SESSION FLASH MESSAGES ==========
function setMessage($type, $text) {
    $_SESSION['message'] = [
        'type' => $type,   // 'success', 'error', 'warning', 'info'
        'text' => $text
    ];
}

function showMessage() {
    if (isset($_SESSION['message'])) {
        $msg = $_SESSION['message'];
        $typeClass = match($msg['type']) {
            'success' => 'alert-success',
            'error'   => 'alert-error',
            'warning' => 'alert-warning',
            default   => 'alert-info'
        };
        echo '<div class="alert ' . $typeClass . '">';
        echo '<span>' . htmlspecialchars($msg['text']) . '</span>';
        echo '<button onclick="this.parentElement.remove()" style="float:right;background:none;border:none;cursor:pointer;">×</button>';
        echo '</div>';
        unset($_SESSION['message']);
    }
}

// ========== VALIDATION HELPERS ==========
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function emailExists($pdo, $email) {
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch() !== false;
}

function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// ========== REDIRECT WITH MESSAGE ==========
function redirect($url, $type = null, $message = null) {
    if ($type && $message) {
        setMessage($type, $message);
    }
    header("Location: $url");
    exit;
}

// ========== ACTIVITY LOGGING ==========
function logActivity($pdo, $user_id, $actor_id, $type, $ref_type, $ref_id, $metadata) {
    $stmt = $pdo->prepare("INSERT INTO activity_log 
        (user_id, actor_user_id, activity_type, reference_type, reference_id, metadata_json) 
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $actor_id, $type, $ref_type, $ref_id, json_encode($metadata)]);
}
?>