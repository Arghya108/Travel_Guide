<?php

$title = 'Scout Dashboard';
require 'views/layouts/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Scout Dashboard</h1>
        <p class="page-sub">Overview of your post request submissions</p>
    </div>

    <a href="index.php?page=create_request" class="btn btn-primary">+ New Request</a>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?= h($counts['total']) ?></div>
        <div class="stat-label">Total Requests</div>
    </div>

    <div class="stat-card">
        <div class="stat-number warning"><?= h($counts['pending']) ?></div>
        <div class="stat-label">Pending Review</div>
    </div>

    <div class="stat-card">
        <div class="stat-number success"><?= h($counts['approved']) ?></div>
        <div class="stat-label">Approved</div>
    </div>

    <div class="stat-card">
        <div class="stat-number danger"><?= h($counts['rejected']) ?></div>
        <div class="stat-label">Rejected</div>
    </div>
</div>

<div class="card quick-card">
    <h3>Quick Actions</h3>

    <div class="quick-actions">
        <a href="index.php?page=create_request" class="btn btn-primary">Submit New Destination</a>
        <a href="index.php?page=my_requests" class="btn btn-ghost">View My Requests</a>
        <a href="index.php?page=approved_posts" class="btn btn-ghost">View Published Posts</a>
    </div>
</div>

<?php require 'views/layouts/footer.php'; ?>