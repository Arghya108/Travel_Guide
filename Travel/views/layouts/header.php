<?php

$user = currentUser();
$currentPage = $_GET['page'] ?? 'home';
$title = $title ?? 'Travel Guide';

function activeLink($pageName, $currentPage) {
    return $pageName === $currentPage ? 'active' : '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?= h($title) ?> &mdash; Travel Guide</title>

<link rel="stylesheet" href="style.css">
</head>

<body class="app-body">

<header class="navbar">
    <div class="navbar-inner">

        <a class="brand" href="index.php?page=home">
            <span class="brand-icon">&#127758;</span>
            <span>TravelGuide</span>
        </a>

        <nav class="nav-links">

            <?php if (!$user): ?>

                <a href="index.php?page=home" class="<?= activeLink('home', $currentPage) ?>">
                    Home
                </a>

                <a href="index.php?page=login" class="<?= activeLink('login', $currentPage) ?>">
                    Login
                </a>

                <a href="index.php?page=register" class="<?= activeLink('register', $currentPage) ?>">
                    Register
                </a>

            <?php else: ?>

                <?php if ($user['role'] === 'admin' && $user['is_verified'] == 1): ?>

                    <a href="index.php?page=admin_dashboard" class="<?= activeLink('admin_dashboard', $currentPage) ?>">
                        Dashboard
                    </a>

                    <a href="index.php?page=users" class="<?= activeLink('users', $currentPage) ?>">
                        Users
                    </a>

                    <a href="index.php?page=post_moderation" class="<?= activeLink('post_moderation', $currentPage) ?>">
                        Posts
                    </a>

                    <a href="index.php?page=comment_moderation" class="<?= activeLink('comment_moderation', $currentPage) ?>">
                        Comments
                    </a>

                    <a href="index.php?page=browse" class="<?= activeLink('browse', $currentPage) ?>">
                        Browse
                    </a>

                <?php elseif ($user['role'] === 'scout' && $user['is_verified'] == 1): ?>

                    <a href="index.php?page=scout_dashboard" class="<?= activeLink('scout_dashboard', $currentPage) ?>">
                        Dashboard
                    </a>

                    <a href="index.php?page=create_request" class="<?= activeLink('create_request', $currentPage) ?>">
                        New Request
                    </a>

                    <a href="index.php?page=my_requests" class="<?= activeLink('my_requests', $currentPage) ?>">
                        My Requests
                    </a>

                    <a href="index.php?page=approved_posts" class="<?= activeLink('approved_posts', $currentPage) ?>">
                        Approved Posts
                    </a>

                    <a href="index.php?page=browse" class="<?= activeLink('browse', $currentPage) ?>">
                        Browse
                    </a>

                <?php elseif ($user['role'] === 'user' && $user['is_verified'] == 1): ?>

                    <a href="index.php?page=home" class="<?= activeLink('home', $currentPage) ?>">
                        Home
                    </a>

                    <a href="index.php?page=browse" class="<?= activeLink('browse', $currentPage) ?>">
                        Browse
                    </a>

                    <a href="index.php?page=wishlist" class="<?= activeLink('wishlist', $currentPage) ?>">
                        Wishlist
                    </a>

                <?php else: ?>

                    <a href="index.php?page=home" class="<?= activeLink('home', $currentPage) ?>">
                        Home
                    </a>

                <?php endif; ?>

                <a href="index.php?page=profile" class="<?= activeLink('profile', $currentPage) ?>">
                    Profile
                </a>

            <?php endif; ?>

        </nav>

        <?php if ($user): ?>
            <div class="nav-user">

                <span class="user-pill">
                    <span class="user-avatar">
                        <?= h(strtoupper(substr($user['name'], 0, 1))) ?>
                    </span>

                    <span class="user-meta">
                        <span class="user-name"><?= h($user['name']) ?></span>
                        <span class="user-role"><?= h(ucfirst($user['role'])) ?></span>
                    </span>
                </span>

                <a href="index.php?page=logout" class="btn-logout">
                    Logout
                </a>

            </div>
        <?php endif; ?>

    </div>
</header>

<main class="main-content">