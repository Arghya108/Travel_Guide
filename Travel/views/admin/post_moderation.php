<?php

$title = 'Post Moderation';
require 'views/layouts/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Post Moderation</h1>
        <p class="page-sub">Approve scout requests and manage published posts</p>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= h($success) ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= h($error) ?></div>
<?php endif; ?>

<?php if (!empty($editPost)): ?>
    <div class="card form-card">
        <h3>Edit Approved Post</h3>

        <form method="POST" action="index.php?page=post_moderation" class="form" id="editPostForm" novalidate>
            <?= csrfField() ?>

            <input type="hidden" name="action" value="update_post">
            <input type="hidden" name="id" value="<?= h($editPost['id']) ?>">

            <label>Title <span class="star">*</span></label>
            <input type="text" name="title" value="<?= h($editPost['title']) ?>" required>

            <label>Short History <span class="star">*</span></label>
            <textarea name="short_history" required><?= h($editPost['short_history']) ?></textarea>

            <label>Country <span class="star">*</span></label>
            <input type="text" name="country" value="<?= h($editPost['country']) ?>" required>

            <label>Genre <span class="star">*</span></label>
            <select name="genre" required>
                <?php
                $genres = ['beach', 'mountain', 'city', 'historical', 'adventure', 'cultural', 'nature'];
                foreach ($genres as $genre):
                ?>
                    <option value="<?= h($genre) ?>" <?= $editPost['genre'] === $genre ? 'selected' : '' ?>>
                        <?= h(ucfirst($genre)) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Cost Level <span class="star">*</span></label>
            <select name="cost_level" required>
                <option value="low" <?= $editPost['cost_level'] === 'low' ? 'selected' : '' ?>>Low</option>
                <option value="medium" <?= $editPost['cost_level'] === 'medium' ? 'selected' : '' ?>>Medium</option>
                <option value="high" <?= $editPost['cost_level'] === 'high' ? 'selected' : '' ?>>High</option>
            </select>

            <label>Travel Medium Info <span class="star">*</span></label>
            <textarea name="travel_medium_info" required><?= h($editPost['travel_medium_info']) ?></textarea>

            <div class="button-row">
                <button type="submit">Update Post</button>
                <button type="button" onclick="window.location.href='index.php?page=post_moderation'">Cancel</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<div class="card table-card">
    <h3>Pending Post Requests</h3>

    <table class="data-table">
        <thead>
            <tr>
                <th>Scout</th>
                <th>Title</th>
                <th>Country</th>
                <th>Genre</th>
                <th>Type</th>
                <th>Requested</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>
            <?php if (empty($pendingRequests)): ?>
                <tr>
                    <td colspan="7" class="center muted">No pending requests</td>
                </tr>
            <?php else: ?>
                <?php foreach ($pendingRequests as $request): ?>
                    <?php
                    $data = json_decode($request['post_data'], true);
                    if (!is_array($data)) {
                        $data = [];
                    }
                    ?>
                    <tr id="request-<?= h($request['id']) ?>">
                        <td>
                            <strong><?= h($request['scout_name']) ?></strong><br>
                            <small class="muted"><?= h($request['scout_email']) ?></small>
                        </td>
                        <td><?= h($data['title'] ?? 'N/A') ?></td>
                        <td><?= h($data['country'] ?? 'N/A') ?></td>
                        <td>
                            <span class="badge badge-primary"><?= h($data['genre'] ?? 'N/A') ?></span>
                        </td>
                        <td>
                            <?php if (!empty($request['original_post_id'])): ?>
                                <span class="badge badge-info">Change Request</span>
                            <?php else: ?>
                                <span class="badge badge-success">New Post</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('M d, Y', strtotime($request['requested_at'])) ?></td>
                        <td>
                            <button type="button" class="btn-sm btn-view" onclick="approveRequest(<?= (int)$request['id'] ?>)">
                                Approve
                            </button>
                            <button type="button" class="btn-sm btn-danger" onclick="rejectRequest(<?= (int)$request['id'] ?>)">
                                Reject
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="card table-card">
    <h3>All Posts</h3>

    <table class="data-table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Scout</th>
                <th>Country</th>
                <th>Genre</th>
                <th>Cost</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>
            <?php if (empty($posts)): ?>
                <tr>
                    <td colspan="7" class="center muted">No posts found</td>
                </tr>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <tr id="post-<?= h($post['id']) ?>">
                        <td><?= h($post['title']) ?></td>
                        <td><?= h($post['scout_name'] ?? 'N/A') ?></td>
                        <td><?= h($post['country']) ?></td>
                        <td>
                            <span class="badge badge-primary"><?= h($post['genre']) ?></span>
                        </td>
                        <td><?= h(ucfirst($post['cost_level'])) ?></td>
                        <td>
                            <span class="badge badge-<?= h($post['status']) ?>"><?= h($post['status']) ?></span>
                        </td>
                        <td>
                            <a href="index.php?page=post_moderation&edit=<?= h($post['id']) ?>" class="btn-sm btn-edit">
                                Edit
                            </a>
                            <button type="button" class="btn-sm btn-danger" onclick="deletePost(<?= (int)$post['id'] ?>)">
                                Delete
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
const CSRF_TOKEN = "<?= h(csrfToken()) ?>";

function sendPostAction(type, id, rowPrefix) {
    const formData = new FormData();
    formData.append("id", id);
    formData.append("csrf_token", CSRF_TOKEN);

    fetch("index.php?page=ajax&type=" + type, {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (type === "approve_request") {
                location.reload();
                return;
            }

            const row = document.getElementById(rowPrefix + "-" + id);
            if (row) {
                row.remove();
            }
        } else {
            alert(data.error || "Action failed.");
        }
    })
    .catch(() => {
        alert("Network error. Please try again.");
    });
}

function approveRequest(id) {
    if (confirm("Approve this post request?")) {
        sendPostAction("approve_request", id, "request");
    }
}

function rejectRequest(id) {
    if (confirm("Reject this post request?")) {
        sendPostAction("reject_request", id, "request");
    }
}

function deletePost(id) {
    if (confirm("Delete this post? Related comments, wishlist entries, and cost estimate will also be removed.")) {
        sendPostAction("delete_post", id, "post");
    }
}

const editPostForm = document.getElementById("editPostForm");

if (editPostForm) {
    editPostForm.addEventListener("submit", function (e) {
        const title = document.querySelector("[name='title']").value.trim();
        const history = document.querySelector("[name='short_history']").value.trim();
        const country = document.querySelector("[name='country']").value.trim();
        const travel = document.querySelector("[name='travel_medium_info']").value.trim();

        if (title === "" || history === "" || country === "" || travel === "") {
            e.preventDefault();
            alert("Please fill in all required fields.");
        }
    });
}
</script>

<?php require 'views/layouts/footer.php'; ?>