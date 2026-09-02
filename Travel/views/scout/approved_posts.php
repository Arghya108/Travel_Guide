<?php

$title = 'Approved Posts';
require 'views/layouts/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">My Published Posts</h1>
        <p class="page-sub">Destinations you submitted that have been approved and published</p>
    </div>

    <a href="index.php?page=create_request" class="btn btn-primary">+ New Request</a>
</div>

<?php if (empty($posts)): ?>
    <div class="card empty-card">
        <div class="empty-icon">&#128214;</div>
        <h3>No approved posts yet</h3>
        <p>None of your posts have been approved yet. Keep submitting travel information.</p>
        <a href="index.php?page=create_request" class="btn btn-primary">Submit New Request</a>
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
                        <span class="badge badge-success">Published</span>
                    </div>

                    <p><?= h(substr($post['short_history'], 0, 130)) ?>...</p>
                </div>

                <div class="post-card-footer">
                    <small class="muted">
                        <?= date('M d, Y', strtotime($post['created_at'])) ?>
                    </small>

                    <button type="button" class="btn-sm btn-edit" onclick="requestChanges(<?= (int)$post['id'] ?>)">
                        Request Changes
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
function requestChanges(postId) {
    if (confirm("Create a change request for this published post?")) {
        window.location.href = "index.php?page=create_request&change_for=" + postId;
    }
}
</script>

<?php require 'views/layouts/footer.php'; ?>