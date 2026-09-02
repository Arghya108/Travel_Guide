<?php

$title = 'Admin Dashboard';
require 'views/layouts/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Admin Dashboard</h1>
        <p class="page-sub">Platform overview and management</p>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?= h($counts['total_users']) ?></div>
        <div class="stat-label">Total Users</div>
        <div class="stat-small">
            <?= h($counts['admins']) ?> admins &middot;
            <?= h($counts['scouts']) ?> scouts &middot;
            <?= h($counts['users']) ?> users
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-number warning"><?= h($counts['pending_requests']) ?></div>
        <div class="stat-label">Pending Post Requests</div>
    </div>

    <div class="stat-card">
        <div class="stat-number success"><?= h($counts['total_posts']) ?></div>
        <div class="stat-label">Published Posts</div>
    </div>

    <div class="stat-card">
        <div class="stat-number info"><?= h($counts['total_comments']) ?></div>
        <div class="stat-label">Total Comments</div>
    </div>
</div>

<div class="card quick-card">
    <h3>Quick Actions</h3>

    <div class="quick-actions">
        <a href="index.php?page=users" class="btn btn-primary">Manage Users</a>
        <a href="index.php?page=post_moderation" class="btn btn-ghost">Moderate Posts</a>
        <a href="index.php?page=comment_moderation" class="btn btn-ghost">Review Comments</a>
    </div>
</div>

<?php require 'views/layouts/footer.php'; ?>