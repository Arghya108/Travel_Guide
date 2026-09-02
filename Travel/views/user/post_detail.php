<?php

$title = $post['title'];
require 'views/layouts/header.php';
?>

<div class="detail-layout">
    <div class="detail-main">
        <div class="card detail-card">
            <div class="detail-image">
                <?php if (!empty($post['image'])): ?>
                    <img src="<?= h($post['image']) ?>" alt="<?= h($post['title']) ?>">
                <?php else: ?>
                    <span>&#127748;</span>
                <?php endif; ?>
            </div>

            <div class="detail-body">
                <h1><?= h($post['title']) ?></h1>

                <div class="post-card-meta">
                    <span class="badge badge-info"><?= h($post['country']) ?></span>
                    <span class="badge badge-primary"><?= h($post['genre']) ?></span>
                    <span class="badge badge-success"><?= h($post['cost_level']) ?></span>
                </div>

                <p class="muted">
                    Submitted by <?= h($post['scout_name'] ?? 'Scout') ?> |
                    <?= date('M d, Y', strtotime($post['created_at'])) ?>
                </p>

                <h3>Short History & Country Representation</h3>
                <p class="long-text"><?= nl2br(h($post['short_history'])) ?></p>

                <h3>Travel Medium Information</h3>
                <p class="long-text"><?= nl2br(h($post['travel_medium_info'])) ?></p>
            </div>
        </div>

        <div class="card comments-card">
            <h2>Comments</h2>

            <?php if (isGeneralUser()): ?>
                <form id="commentForm" class="form comment-form" novalidate>
                    <input type="hidden" name="post_id" value="<?= h($post['id']) ?>">

                    <label>Write Comment</label>
                    <textarea
                        name="content"
                        id="commentContent"
                        maxlength="1000"
                        placeholder="Write your comment..."
                        required
                    ></textarea>

                    <div class="form-bottom">
                        <small class="muted">
                            <span id="charCount">0</span>/1000 characters
                        </small>

                        <button type="submit" class="btn btn-primary">Post Comment</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-warning">
                    Only verified general users can post comments.
                </div>
            <?php endif; ?>

            <div id="commentsList">
                <?php if (empty($comments)): ?>
                    <p class="muted">No comments yet.</p>
                <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="comment-item" id="comment-<?= h($comment['id']) ?>">
                            <div>
                                <strong><?= h($comment['user_name']) ?></strong>
                                <small><?= date('M d, Y h:i A', strtotime($comment['created_at'])) ?></small>
                            </div>

                            <p><?= nl2br(h($comment['content'])) ?></p>

                            <?php if (isGeneralUser() && $comment['user_id'] == $_SESSION['user']['id']): ?>
                                <button
                                    type="button"
                                    class="btn-sm btn-danger"
                                    onclick="deleteComment(<?= (int)$comment['id'] ?>)">
                                    Delete
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <aside class="detail-side">
        <div class="card cost-card">
            <h3>Probable Cost Estimate</h3>

            <p class="muted">
                Base Cost:
                <strong><?= h($currency) ?> <?= h(number_format($baseCost, 2)) ?></strong>
            </p>

            <?php if (isGeneralUser()): ?>
                <form id="costForm" class="form" novalidate>
                    <input type="hidden" name="post_id" value="<?= h($post['id']) ?>">

                    <label>Number of Travelers</label>
                    <input type="number" name="travelers" min="1" max="10" value="1" required>

                    <label>Number of Days</label>
                    <input type="number" name="days" min="1" max="90" value="7" required>

                    <button type="submit" class="btn btn-primary">Calculate</button>
                </form>

                <div id="costResult" class="cost-result"></div>
            <?php else: ?>
                <div class="alert alert-warning">
                    Cost calculator is available for verified general users only.
                </div>
            <?php endif; ?>
        </div>
    </aside>
</div>




//script edit korlam comment er jonno, cost calculator er jonno, and AJAX delete comment er jonno
//AJAX diye add , no extra reload , instant comment display korbe
//AJAX diye DELETE nijer comment add korsi
//Inline comment error chck korlam ar inline cost er calculator add korlam
<script>
const CSRF_TOKEN = "<?= h(csrfToken()) ?>";

function esc(value) {
    return String(value == null ? "" : value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
}

function nl2br(value) {
    return esc(value).replace(/\n/g, "<br>");
}

function showInlineError(field, message) {
    const span = document.querySelector(`[data-error-for="${field}"]`);
    const input = document.querySelector(`[name="${field}"]`);

    if (span) {
        span.textContent = message;
    }

    if (input) {
        input.classList.add("input-error");
    }
}

function clearInlineErrors(scope) {
    scope.querySelectorAll("[data-error-for]").forEach(span => {
        span.textContent = "";
    });

    scope.querySelectorAll(".input-error").forEach(input => {
        input.classList.remove("input-error");
    });
}

const commentContent = document.getElementById("commentContent");

if (commentContent) {
    commentContent.insertAdjacentHTML("afterend", `<span class="error" data-error-for="content"></span>`);

    commentContent.addEventListener("input", function () {
        document.getElementById("charCount").textContent = commentContent.value.length;
    });
}

const commentForm = document.getElementById("commentForm");

if (commentForm) {
    commentForm.addEventListener("submit", function (e) {
        e.preventDefault();
        clearInlineErrors(commentForm);

        const content = document.getElementById("commentContent").value.trim();

        if (content === "") {
            showInlineError("content", "Comment cannot be empty.");
            return;
        }

        if (content.length > 1000) {
            showInlineError("content", "Comment must be within 1000 characters.");
            return;
        }

        const formData = new FormData(commentForm);
        formData.append("csrf_token", CSRF_TOKEN);

        fetch("index.php?page=ajax&type=add_comment", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const commentsList = document.getElementById("commentsList");

                const emptyText = commentsList.querySelector(".muted");
                if (emptyText && emptyText.textContent.includes("No comments")) {
                    commentsList.innerHTML = "";
                }

                const commentHtml = `
                    <div class="comment-item" id="comment-${Number(data.comment_id)}">
                        <div>
                            <strong>${esc(data.user_name)}</strong>
                            <small>${esc(data.created_at)}</small>
                        </div>

                        <p>${nl2br(data.content)}</p>

                        <button
                            type="button"
                            class="btn-sm btn-danger"
                            onclick="deleteComment(${Number(data.comment_id)})">
                            Delete
                        </button>
                    </div>
                `;

                commentsList.insertAdjacentHTML("afterbegin", commentHtml);
                commentForm.reset();
                document.getElementById("charCount").textContent = "0";
                return;
            }

            if (data.errors) {
                Object.keys(data.errors).forEach(field => {
                    showInlineError(field, data.errors[field]);
                });
                return;
            }

            alert(data.error || "Comment submission failed.");
        })
        .catch(() => {
            alert("Network error. Please try again.");
        });
    });
}

function deleteComment(commentId) {
    if (!confirm("Delete this comment?")) {
        return;
    }

    const body = new URLSearchParams();
    body.append("id", commentId);
    body.append("csrf_token", CSRF_TOKEN);

    fetch("index.php?page=ajax&type=delete_comment", {
        method: "DELETE",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: body.toString()
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const comment = document.getElementById("comment-" + commentId);

            if (comment) {
                comment.remove();
            }
        } else {
            alert(data.error || "Delete failed.");
        }
    })
    .catch(() => {
        alert("Network error. Please try again.");
    });
}

const costForm = document.getElementById("costForm");

if (costForm) {
    document.querySelector("[name='travelers']").insertAdjacentHTML("afterend", `<span class="error" data-error-for="travelers"></span>`);
    document.querySelector("[name='days']").insertAdjacentHTML("afterend", `<span class="error" data-error-for="days"></span>`);

    costForm.addEventListener("submit", function (e) {
        e.preventDefault();
        clearInlineErrors(costForm);

        const travelers = Number(document.querySelector("[name='travelers']").value);
        const days = Number(document.querySelector("[name='days']").value);

        let hasError = false;

        if (travelers < 1 || travelers > 10) {
            showInlineError("travelers", "Travelers must be between 1 and 10.");
            hasError = true;
        }

        if (days < 1 || days > 90) {
            showInlineError("days", "Days must be between 1 and 90.");
            hasError = true;
        }

        if (hasError) {
            return;
        }

        const formData = new FormData(costForm);
        formData.append("csrf_token", CSRF_TOKEN);

        fetch("index.php?page=ajax&type=calculate_cost", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const resultBox = document.getElementById("costResult");

            if (data.success) {
                resultBox.innerHTML = `
                    <strong>Total Estimate:</strong><br>
                    ${esc(data.currency)} ${esc(data.total)}
                `;
                return;
            }

            if (data.errors) {
                Object.keys(data.errors).forEach(field => {
                    showInlineError(field, data.errors[field]);
                });
                return;
            }

            resultBox.textContent = data.error || "Calculation failed.";
        })
        .catch(() => {
            alert("Network error. Please try again.");
        });
    });
}
</script>




<?php require 'views/layouts/footer.php'; ?>