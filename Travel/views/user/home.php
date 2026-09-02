<?php

$title = 'Home';
require 'views/layouts/header.php';
?>

<?php if (!isLoggedIn()): ?>

    <section class="hero">
        <div class="hero-icon">&#127758;</div>

        <h1>Welcome to Travel Guide</h1>

        <p>
            Discover beautiful destinations around the world. Explore travel history,
            country information, travel medium details, cost levels, comments, and wishlist features.
        </p>

        <div class="hero-actions">
            <a href="index.php?page=register" class="btn btn-primary">Create Account</a>
            <a href="index.php?page=login" class="btn btn-ghost">Login</a>
        </div>
    </section>

<?php elseif (!isVerified()): ?>

    <div class="card pending-card">
        <div class="empty-icon">&#9888;</div>

        <h2>Your account is pending admin approval</h2>

        <p>
            You have successfully logged in, but your account is not verified yet.
            Please wait until an admin verifies your account.
        </p>

        <a href="index.php?page=profile" class="btn btn-primary">View Profile</a>
    </div>

<?php else: ?>

    <div class="page-header">
        <div>
            <h1 class="page-title">Latest Approved Destinations</h1>
            <p class="page-sub">Freshly published travel suggestions from our scouts</p>
        </div>

        <a href="index.php?page=browse" class="btn btn-primary">Browse All</a>
    </div>

    <?php if (empty($posts)): ?>

        <div class="card empty-card">
            <div class="empty-icon">&#128214;</div>
            <h3>No approved posts yet</h3>
            <p>Approved destinations will appear here after admin approval.</p>
        </div>

    <?php else: ?>

        <div class="posts-grid">
            <?php foreach ($posts as $post): ?>
                <div class="post-card">
                    <div class="post-card-img">
                        <?php if (!empty($post['image'])): ?>
                            <img src="<?= h($post['image']) ?>" alt="<?= h($post['title']) ?>">
                        <?php else: ?>
                            &#127748;
                        <?php endif; ?>
                    </div>

                    <div class="post-card-body">
                        <h3><?= h($post['title']) ?></h3>

                        <div class="post-card-meta">
                            <span class="badge badge-info"><?= h($post['country']) ?></span>
                            <span class="badge badge-primary"><?= h($post['genre']) ?></span>
                            <span class="badge badge-success"><?= h($post['cost_level']) ?></span>
                        </div>

                        <p><?= h(substr($post['short_history'], 0, 130)) ?>...</p>
                    </div>

                    <div class="post-card-footer">
                        <small class="muted">
                            <?= date('M d, Y', strtotime($post['created_at'])) ?>
                        </small>

                        <a href="index.php?page=post_detail&id=<?= h($post['id']) ?>" class="btn-sm btn-view">
                            Read More
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>

<?php endif; ?>

<?php require 'views/layouts/footer.php'; ?>