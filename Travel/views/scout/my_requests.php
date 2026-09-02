<?php

$title = 'My Requests';
require 'views/layouts/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">My Post Requests</h1>
        <p class="page-sub">Track, edit, and delete your submitted requests</p>
    </div>

    <a href="index.php?page=create_request" class="btn btn-primary">+ New Request</a>
</div>

<?php if (!empty($flash)): ?>
    <div class="alert alert-<?= h($flash['type']) ?>">
        <?= h($flash['message']) ?>
    </div>
<?php endif; ?>

<div class="card filter-card">
    <input type="text" id="requestSearch" placeholder="Search requests by title, country, genre, or status...">
</div>

<div class="card table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Country</th>
                <th>Genre</th>
                <th>Cost</th>
                <th>Type</th>
                <th>Status</th>
                <th>Requested</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody id="requestTableBody">
            <?php if (empty($requests)): ?>
                <tr>
                    <td colspan="8" class="center muted">No requests submitted yet</td>
                </tr>
            <?php else: ?>
                <?php foreach ($requests as $request): ?>
                    <?php
                    $data = json_decode($request['post_data'], true);
                    if (!is_array($data)) {
                        $data = [];
                    }
                    ?>
                    <tr id="request-<?= h($request['id']) ?>">
                        <td><?= h($data['title'] ?? 'N/A') ?></td>
                        <td><?= h($data['country'] ?? 'N/A') ?></td>
                        <td>
                            <span class="badge badge-primary"><?= h($data['genre'] ?? 'N/A') ?></span>
                        </td>
                        <td><?= h(ucfirst($data['cost_level'] ?? 'N/A')) ?></td>
                        <td>
                            <?php if (!empty($request['original_post_id'])): ?>
                                <span class="badge badge-info">Change</span>
                            <?php else: ?>
                                <span class="badge badge-success">New</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-<?= h($request['status']) ?>">
                                <?= h($request['status']) ?>
                            </span>
                        </td>
                        <td><?= date('M d, Y', strtotime($request['requested_at'])) ?></td>
                        <td>
                            <?php if ($request['status'] === 'pending'): ?>
                                <a href="index.php?page=edit_request&id=<?= h($request['id']) ?>" class="btn-sm btn-edit">Edit</a>
                                <button type="button" class="btn-sm btn-danger" onclick="deleteRequest(<?= (int)$request['id'] ?>)">
                                    Delete
                                </button>
                            <?php else: ?>
                                <span class="muted">Locked</span>
                            <?php endif; ?>
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


//AJAX DELETE active korlam pending request theke
//ekhon scout e request dlt AJAX diye korte parbe, page reload lagbe na, request list theke row ta remove hoye jabe instantly
function deleteRequest(id) {
    if (!confirm("Delete this pending request?")) {
        return;
    }

    const body = new URLSearchParams();
    body.append("id", id);
    body.append("csrf_token", CSRF_TOKEN);

    fetch("index.php?page=ajax&type=delete_request", {
        method: "DELETE",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: body.toString()
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const row = document.getElementById("request-" + id);

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

document.getElementById("requestSearch").addEventListener("input", function () {
    const q = this.value.trim();

    fetch("index.php?page=ajax&type=search_requests&q=" + encodeURIComponent(q))
    .then(response => response.json())
    .then(rows => {
        const body = document.getElementById("requestTableBody");

        if (!Array.isArray(rows) || rows.length === 0) {
            body.innerHTML = `<tr><td colspan="8" class="center muted">No requests found</td></tr>`;
            return;
        }

        body.innerHTML = rows.map(row => {
            const typeBadge = row.original_post_id
                ? `<span class="badge badge-info">Change</span>`
                : `<span class="badge badge-success">New</span>`;

            const action = row.status === "pending"
                ? `<a href="index.php?page=edit_request&id=${Number(row.id)}" class="btn-sm btn-edit">Edit</a>
                   <button type="button" class="btn-sm btn-danger" onclick="deleteRequest(${Number(row.id)})">Delete</button>`
                : `<span class="muted">Locked</span>`;

            return `
                <tr id="request-${Number(row.id)}">
                    <td>${esc(row.title || "N/A")}</td>
                    <td>${esc(row.country || "N/A")}</td>
                    <td><span class="badge badge-primary">${esc(row.genre || "N/A")}</span></td>
                    <td>${esc(row.cost_level || "N/A")}</td>
                    <td>${typeBadge}</td>
                    <td><span class="badge badge-${esc(row.status)}">${esc(row.status)}</span></td>
                    <td>${esc(row.requested_at)}</td>
                    <td>${action}</td>
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