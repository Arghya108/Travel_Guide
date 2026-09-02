<?php

$title = 'Manage Users';
require 'views/layouts/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">User Management</h1>
        <p class="page-sub">Add, verify, unverify, search, and remove users</p>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= h($success) ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= h($error) ?></div>
<?php endif; ?>

<div class="two-col">
    <div class="card form-card">
        <h3>Add New User</h3>

        <form method="POST" action="index.php?page=users" class="form" id="addUserForm" novalidate>
            <?= csrfField() ?>

            <label>Name <span class="star">*</span></label>
            <input type="text" name="name" placeholder="Enter full name" required>

            <label>Email <span class="star">*</span></label>
            <input type="email" name="email" placeholder="example@email.com" required>

            <label>Password <span class="star">*</span></label>
            <input type="password" name="password" placeholder="Minimum 8 characters" required>

            <label>Role <span class="star">*</span></label>
            <select name="role" required>
                <option value="user">General User</option>
                <option value="scout">Scout</option>
                <option value="admin">Admin</option>
            </select>

            <label>Verification Status</label>
            <select name="is_verified">
                <option value="1">Verified</option>
                <option value="0">Pending</option>
            </select>

            <button type="submit" class="btn btn-primary">Add User</button>
        </form>
    </div>

    <div class="card table-card wide-card">
        <div class="table-top">
            <h3>All Users</h3>
            <input type="text" id="userSearch" placeholder="Search by name, email, or role...">
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Verified</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody id="userTableBody">
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" class="center muted">No users found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <tr id="user-<?= h($u['id']) ?>">
                            <td><?= h($u['name']) ?></td>
                            <td><?= h($u['email']) ?></td>
                            <td>
                                <span class="badge badge-primary"><?= h($u['role']) ?></span>
                            </td>
                            <td>
                                <?php if ($u['role'] === 'admin' && $u['id'] == $_SESSION['user']['id']): ?>
                                    <span class="badge badge-success">Current Admin</span>
                                <?php else: ?>
                                    <button
                                        type="button"
                                        class="btn-sm <?= $u['is_verified'] ? 'btn-view' : 'btn-edit' ?>"
                                        onclick="toggleVerify(<?= (int)$u['id'] ?>, <?= $u['is_verified'] ? 0 : 1 ?>)">
                                        <?= $u['is_verified'] ? 'Verified' : 'Approve User' ?>
                                    </button>
                                <?php endif; ?>
                            </td>
                            <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                            <td>
                                <?php if ($u['id'] == $_SESSION['user']['id']): ?>
                                    <span class="muted">Own Account</span>
                                <?php else: ?>
                                    <button
                                        type="button"
                                        class="btn-sm btn-danger"
                                        onclick="deleteUser(<?= (int)$u['id'] ?>)">
                                        Delete
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
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

document.getElementById("addUserForm").addEventListener("submit", function (e) {
    const name = document.querySelector("[name='name']").value.trim();
    const email = document.querySelector("[name='email']").value.trim();
    const password = document.querySelector("[name='password']").value;

    if (name === "" || email === "" || password === "") {
        e.preventDefault();
        alert("Please fill in all required fields.");
        return;
    }

    if (password.length < 8) {
        e.preventDefault();
        alert("Password must be at least 8 characters.");
    }
});

function toggleVerify(id, value) {
    const formData = new FormData();
    formData.append("id", id);
    formData.append("is_verified", value);
    formData.append("csrf_token", CSRF_TOKEN);

    fetch("index.php?page=ajax&type=verify_toggle", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || "Verification update failed.");
        }
    })
    .catch(() => {
        alert("Network error. Please try again.");
    });
}

function deleteUser(id) {
    if (!confirm("Delete this user? Related posts, requests, wishlist, and comments may also be removed.")) {
        return;
    }

    const formData = new FormData();
    formData.append("id", id);
    formData.append("csrf_token", CSRF_TOKEN);

    fetch("index.php?page=ajax&type=delete_user", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const row = document.getElementById("user-" + id);
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

document.getElementById("userSearch").addEventListener("input", function () {
    const q = this.value.trim();

    fetch("index.php?page=ajax&type=search_users&q=" + encodeURIComponent(q))
    .then(response => response.json())
    .then(users => {
        const body = document.getElementById("userTableBody");

        if (!Array.isArray(users) || users.length === 0) {
            body.innerHTML = `<tr><td colspan="6" class="center muted">No users found</td></tr>`;
            return;
        }

        body.innerHTML = users.map(user => {
            const isOwn = Number(user.id) === Number(<?= (int)$_SESSION['user']['id'] ?>);

            const verifyColumn = isOwn && user.role === "admin"
                ? `<span class="badge badge-success">Current Admin</span>`
                : `<button type="button"
                        class="btn-sm ${Number(user.is_verified) === 1 ? "btn-view" : "btn-edit"}"
                        onclick="toggleVerify(${Number(user.id)}, ${Number(user.is_verified) === 1 ? 0 : 1})">
                        ${Number(user.is_verified) === 1 ? "Verified" : "Approve User"}
                   </button>`;

            const actionColumn = isOwn
                ? `<span class="muted">Own Account</span>`
                : `<button type="button" class="btn-sm btn-danger" onclick="deleteUser(${Number(user.id)})">Delete</button>`;

            return `
                <tr id="user-${Number(user.id)}">
                    <td>${esc(user.name)}</td>
                    <td>${esc(user.email)}</td>
                    <td><span class="badge badge-primary">${esc(user.role)}</span></td>
                    <td>${verifyColumn}</td>
                    <td>${esc(user.created_at)}</td>
                    <td>${actionColumn}</td>
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