<?php

require 'config.php';
require 'models.php';
require 'controllers.php';

$page = $_GET['page'] ?? 'home';

// Remember Me Auto Login
if (!isset($_SESSION['user']) && isset($_COOKIE['remember_token'])) {
    $tokenHash = hash('sha256', $_COOKIE['remember_token']);
    $remembered = getUserByRememberToken($conn, $tokenHash);

    if ($remembered) {
        setLoginSession($remembered);
    }
}
// Logout
if ($page === 'logout') {
    logoutCtrl($conn);
}

// AJAX
if ($page === 'ajax') {
    ajaxCtrl($conn);
}

// Auth Gates
$verifiedOnlyPages = [
    'browse',
    'post_detail'
];

$loginRequiredPages = [
    'profile',
    'wishlist',

    'admin_dashboard',
    'users',
    'post_moderation',
    'comment_moderation',

    'scout_dashboard',
    'create_request',
    'my_requests',
    'edit_request',
    'approved_posts'
];

$adminPages = [
    'admin_dashboard',
    'users',
    'post_moderation',
    'comment_moderation'
];

$scoutPages = [
    'scout_dashboard',
    'create_request',
    'my_requests',
    'edit_request',
    'approved_posts'
];

$userOnlyPages = [
    'wishlist'
];

// Already logged in users should not open login/register again
if (in_array($page, ['login', 'register']) && isLoggedIn()) {
    redirectAfterLogin($_SESSION['user']);
}

// Login required
if ((in_array($page, $loginRequiredPages) || in_array($page, $verifiedOnlyPages)) && !isLoggedIn()) {
    redirect('index.php?page=login');
}

// Verified only
if (in_array($page, $verifiedOnlyPages) && !isVerified()) {
    redirect('index.php?page=home');
}

// Admin gate
if (in_array($page, $adminPages) && !isAdmin()) {
    redirect('index.php?page=home');
}

// Scout gate
if (in_array($page, $scoutPages) && !isScout()) {
    redirect('index.php?page=home');
}

// General user gate
if (in_array($page, $userOnlyPages) && !isGeneralUser()) {
    redirect('index.php?page=home');
}


// Dispatch
switch ($page) {
    case 'home':
        homeCtrl($conn);
        break;

    case 'login':
        loginCtrl($conn);
        break;

    case 'register':
        registerCtrl($conn);
        break;

    case 'profile':
        profileCtrl($conn);
        break;

    case 'wishlist':
        wishlistCtrl($conn);
        break;

    case 'browse':
        browseCtrl($conn);
        break;

    case 'post_detail':
        postDetailCtrl($conn);
        break;

    case 'scout_dashboard':
        scoutDashboardCtrl($conn);
        break;

    case 'create_request':
        createRequestCtrl($conn);
        break;

    case 'my_requests':
        myRequestsCtrl($conn);
        break;

    case 'edit_request':
        editRequestCtrl($conn);
        break;

    case 'approved_posts':
        approvedPostsCtrl($conn);
        break;

    case 'admin_dashboard':
        adminDashboardCtrl($conn);
        break;

    case 'users':
        usersCtrl($conn);
        break;

    case 'post_moderation':
        postModerationCtrl($conn);
        break;

    case 'comment_moderation':
        commentModerationCtrl($conn);
        break;

    default:
        redirect('index.php?page=home');
}

mysqli_close($conn);
?>