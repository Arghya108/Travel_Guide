<?php

$title = 'Wishlist';
require 'views/layouts/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">My Wishlist</h1>
        <p class="page-sub">Your saved travel destinations</p>
    </div>

    <a href="index.php?page=browse" class="btn btn-primary">Browse More</a>
</div>

<?php if (empty($items)): ?>

    <div class="card empty-card">
        <div class="empty-icon">&#10084;</div>
        <h3>Your wishlist is empty</h3>
        <p>Browse approved destinations and save your favorite places.</p>
        <a href="index.php?page=browse" class="btn btn-primary">Browse Destinations</a>
    </div>

<?php else: ?>

    <div class="posts-grid" id="wishlistGrid">
        <?php foreach ($items as $item): ?>
            <div class="post-card" id="wish-<?= h($item['post_id']) ?>">
                <div class="post-card-img">
                    <?php if (!empty($item['image'])): ?>
                        <img src="<?= h($item['image']) ?>" alt="<?= h($item['title']) ?>">
                    <?php else: ?>
                        &#127748;
                    <?php endif; ?>
                </div>

                <div class="post-card-body">
                    <h3><?= h($item['title']) ?></h3>

                    <div class="post-card-meta">
                        <span class="badge badge-info"><?= h($item['country']) ?></span>
                        <span class="badge badge-primary"><?= h($item['genre']) ?></span>
                        <span class="badge badge-success"><?= h($item['cost_level']) ?></span>
                    </div>

                    <p><?= h(substr($item['short_history'], 0, 120)) ?>...</p>
                </div>

                <div class="post-card-footer">
                    <a href="index.php?page=post_detail&id=<?= h($item['post_id']) ?>" class="btn-sm btn-view">
                        View
                    </a>

                    <button type="button" class="btn-sm btn-danger" onclick="removeWishlist(<?= (int)$item['post_id'] ?>)">
                        Remove
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

<?php endif; ?>

<script>
const CSRF_TOKEN = "<?= h(csrfToken()) ?>";


//AJAX DELETE active korlam wishlist theke
function removeWishlist(postId) {
    if (!confirm("Remove this destination from your wishlist?")) {
        return;
    }

    const body = new URLSearchParams();
    body.append("post_id", postId);
    body.append("csrf_token", CSRF_TOKEN);

    fetch("index.php?page=ajax&type=wishlist_remove", {
        method: "DELETE",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: body.toString()
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const item = document.getElementById("wish-" + postId);

            if (item) {
                item.remove();
            }
        } else {
            alert(data.error || "Remove failed.");
        }
    })
    .catch(() => {
        alert("Network error. Please try again.");
    });
}
</script>

<?php require 'views/layouts/footer.php'; ?>