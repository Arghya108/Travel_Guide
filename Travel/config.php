<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Dhaka');


// Database Connection
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'travel_guide_db';

$conn = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if (!$conn) {
    die('Database connection failed: ' . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');

// Upload Paths
define('PROFILE_UPLOAD_DIR', __DIR__ . '/public/uploads/profile/');
define('POST_UPLOAD_DIR', __DIR__ . '/public/uploads/posts/');

define('PROFILE_UPLOAD_WEB', 'public/uploads/profile/');
define('POST_UPLOAD_WEB', 'public/uploads/posts/');

if (!is_dir(PROFILE_UPLOAD_DIR)) {
    mkdir(PROFILE_UPLOAD_DIR, 0777, true);
}

if (!is_dir(POST_UPLOAD_DIR)) {
    mkdir(POST_UPLOAD_DIR, 0777, true);
}


// Helper Functions
function cleanInput($data) {
    return trim(stripslashes($data));
}

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit;
}

// Flash Message Helpers
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlash() {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }

    return null;
}

// CSRF Protection
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . h(csrfToken()) . '">';
}

function verifyCsrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

// Session Helpers
function setLoginSession($user) {
    $_SESSION['user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'is_verified' => $user['is_verified'],
        'profile_picture' => $user['profile_picture'] ?? null
    ];

    // Compatibility with older session style
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
}

function currentUser() {
    return $_SESSION['user'] ?? null;
}

function isLoggedIn() {
    return isset($_SESSION['user']);
}

function isVerified() {
    return isset($_SESSION['user']) && $_SESSION['user']['is_verified'] == 1;
}

function isAdmin() {
    return isset($_SESSION['user'])
        && $_SESSION['user']['role'] === 'admin'
        && $_SESSION['user']['is_verified'] == 1;
}

function isScout() {
    return isset($_SESSION['user'])
        && $_SESSION['user']['role'] === 'scout'
        && $_SESSION['user']['is_verified'] == 1;
}

function isGeneralUser() {
    return isset($_SESSION['user'])
        && $_SESSION['user']['role'] === 'user'
        && $_SESSION['user']['is_verified'] == 1;
}

// Cookie Helpers
function setSecureCookie($name, $value, $expires) {
    setcookie($name, $value, [
        'expires' => $expires,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

function clearCookieValue($name) {
    setcookie($name, '', [
        'expires' => time() - 3600,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

// Image Upload Helper
function uploadImage($file, $targetDir, $webPath, &$error) {
    if (empty($file['name'])) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Image upload failed.';
        return false;
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        $error = 'Image size must be less than 2MB.';
        return false;
    }

    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif'
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!array_key_exists($mime, $allowedTypes)) {
        $error = 'Only JPG, PNG, WEBP, and GIF images are allowed.';
        return false;
    }

    $filename = uniqid('img_', true) . '.' . $allowedTypes[$mime];
    $destination = $targetDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        $error = 'Could not save uploaded image.';
        return false;
    }

    return $webPath . $filename;
}

// Default Admin Auto Seed
// Email: admin@travelguide.com
// Password: admin123
$checkStmt = mysqli_prepare($conn, "SELECT id FROM users WHERE role = 'admin' LIMIT 1");
mysqli_stmt_execute($checkStmt);
$checkResult = mysqli_stmt_get_result($checkStmt);

if ($checkResult && mysqli_num_rows($checkResult) === 0) {
    $hash = password_hash('admin123', PASSWORD_DEFAULT);

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO users (name, email, password_hash, role, is_verified)
         VALUES ('Administrator', 'admin@travelguide.com', ?, 'admin', 1)"
    );

    mysqli_stmt_bind_param($stmt, 's', $hash);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

mysqli_stmt_close($checkStmt);
?>