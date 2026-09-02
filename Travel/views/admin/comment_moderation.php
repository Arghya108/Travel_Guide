<?php

$title = 'Comment Moderation';
require 'views/layouts/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Comment Moderation</h1>
        <p class="page-sub">Review, search, and remove user comments</p>
    </div>
</div>

<div class="card filter-card">
    <input type="text" id="commentSearch" placeholder="Search by post, user, or comment...">
</div>

<div class="card table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>Post</th>
                <th>User</th>
                <th>Comment</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody id="commentTableBody">
            <?php if (empty($comments)): ?>
                <tr>
                    <td colspan="5" class="center muted">No comments found</td>
                </tr>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                    <tr id="comment-<?= h($comment['id']) ?>">
                        <td><?= h($comment['post_title']) ?></td>
                        <td><?= h($comment['user_name']) ?></td>
                        <td><?= h($comment['content']) ?></td>
                        <td><?= date('M d, Y h:i A', strtotime($comment['created_at'])) ?></td>
                        <td>
                            <button
                                type="button"
                                class="btn-sm btn-danger"
                                onclick="deleteCommentAdmin(<?= (int)$comment['id'] ?>)">
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

function esc(value) {
    return String(value == null ? "" : value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
}

function deleteCommentAdmin(id) {
    if (!confirm("Delete this comment?")) {
        return;
    }

    const formData = new FormData();
    formData.append("id", id);
    formData.append("csrf_token", CSRF_TOKEN);

    fetch("index.php?page=ajax&type=delete_comment_admin", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const row = document.getElementById("comment-" + id);
            if (row) {
                row.remove();
            }
        } else {
            alert(data.error || "Delete failed.");
        }
    })
    .catch(() => {
        alert("Network error. Please try again.");
    });
}

document.getElementById("commentSearch").addEventListener("input", function () {
    const q = this.value.trim();

    fetch("index.php?page=ajax&type=search_comments&q=" + encodeURIComponent(q))
    .then(response => response.json())
    .then(comments => {
        const body = document.getElementById("commentTableBody");

        if (!Array.isArray(comments) || comments.length === 0) {
            body.innerHTML = `<tr><td colspan="5" class="center muted">No comments found</td></tr>`;
            return;
        }

        body.innerHTML = comments.map(comment => {
            return `
                <tr id="comment-${Number(comment.id)}">
                    <td>${esc(comment.post_title)}</td>
                    <td>${esc(comment.user_name)}</td>
                    <td>${esc(comment.content)}</td>
                    <td>${esc(comment.created_at)}</td>
                    <td>
                        <button type="button" class="btn-sm btn-danger" onclick="deleteCommentAdmin(${Number(comment.id)})">
                            Delete
                        </button>
                    </td>
                </tr>
            `;
        }).join("");
    })
    .catch(() => {
        alert("Search failed.");
    });
});
</script>

<?php require 'views/layouts/footer.php'; ?>