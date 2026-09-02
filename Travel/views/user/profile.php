<?php

$title = 'Profile';
require 'views/layouts/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">My Profile</h1>
        <p class="page-sub">Update your account information and password</p>
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
        <h3>Profile Information</h3>

        <form method="POST" action="index.php?page=profile" enctype="multipart/form-data" class="form" id="profileForm" novalidate>
            <?= csrfField() ?>

            <input type="hidden" name="action" value="update_profile">

            <label>Name <span class="star">*</span></label>
            <input
                type="text"
                name="name"
                value="<?= h($user['name']) ?>"
                placeholder="Enter your name"
                required
            >

            <label>Email <span class="star">*</span></label>
            <input
                type="email"
                name="email"
                value="<?= h($user['email']) ?>"
                placeholder="example@email.com"
                required
            >

            <label>Profile Picture</label>
            <input type="file" name="profile_picture" accept="image/*">

            <?php if (!empty($user['profile_picture'])): ?>
                <div class="profile-preview">
                    <p class="muted">Current Picture:</p>
                    <img src="<?= h($user['profile_picture']) ?>" alt="Profile Picture">
                </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary">Save Profile</button>
        </form>
    </div>

    <div class="card form-card">
        <h3>Change Password</h3>

        <form method="POST" action="index.php?page=profile" class="form" id="passwordForm" novalidate>
            <?= csrfField() ?>

            <input type="hidden" name="action" value="change_password">

            <label>Current Password <span class="star">*</span></label>
            <input type="password" name="current_password" placeholder="Enter current password" required>

            <label>New Password <span class="star">*</span></label>
            <input type="password" name="new_password" id="newPassword" placeholder="Minimum 8 characters" required>

            <label>Confirm New Password <span class="star">*</span></label>
            <input type="password" name="confirm_password" id="confirmPassword" placeholder="Re-enter new password" required>

            <button type="submit" class="btn btn-primary">Change Password</button>
        </form>
    </div>
</div>

<script>
document.getElementById("profileForm").addEventListener("submit", function (e) {
    const name = document.querySelector("[name='name']").value.trim();
    const email = document.querySelector("[name='email']").value.trim();

    if (name === "" || email === "") {
        e.preventDefault();
        alert("Name and email are required.");
    }
});

document.getElementById("passwordForm").addEventListener("submit", function (e) {
    const newPassword = document.getElementById("newPassword").value;
    const confirmPassword = document.getElementById("confirmPassword").value;

    if (newPassword.length < 8) {
        e.preventDefault();
        alert("New password must be at least 8 characters.");
        return;
    }

    if (newPassword !== confirmPassword) {
        e.preventDefault();
        alert("New passwords do not match.");
    }
});

if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}
</script>

<?php require 'views/layouts/footer.php'; ?>