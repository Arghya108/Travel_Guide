<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register &mdash; Travel Guide</title>
<link rel="stylesheet" href="style.css">
</head>

<body class="auth-body">

<div class="signup-card">
    <div class="logo-center">&#127758;</div>

    <h2>Signup</h2>
    <p class="subtitle">Create your Travel Guide account</p>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-error">
            <?= h($errors['general']) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="index.php?page=register" class="form" id="registerForm" novalidate>
        <?= csrfField() ?>

        <label>Name <span class="star">*</span></label>
        <input 
            type="text" 
            name="name" 
            id="name" 
            value="<?= h($name) ?>" 
            placeholder="Enter full name"
            required
        >
        <span class="error"><?= h($errors['name'] ?? '') ?></span>

        <label>Email <span class="star">*</span></label>
        <input 
            type="email" 
            name="email" 
            id="email" 
            value="<?= h($email) ?>" 
            placeholder="example@gmail.com"
            required
        >
        <span class="error" id="emailError"><?= h($errors['email'] ?? '') ?></span>

        <label>Role <span class="star">*</span></label>
        <select name="role" id="role" required>
            <option value="user" <?= $role === 'user' ? 'selected' : '' ?>>General User</option>
            <option value="scout" <?= $role === 'scout' ? 'selected' : '' ?>>Scout</option>
            <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
        </select>
        <span class="error"><?= h($errors['role'] ?? '') ?></span>

        <label>Password <span class="star">*</span></label>
        <input 
            type="password" 
            name="password" 
            id="password" 
            placeholder="Minimum 8 characters"
            required
        >
        <span class="error"><?= h($errors['password'] ?? '') ?></span>

        <label>Confirm Password <span class="star">*</span></label>
        <input 
            type="password" 
            name="confirm_password" 
            id="confirmPassword" 
            placeholder="Re-enter password"
            required
        >
        <span class="error" id="confirmError"><?= h($errors['confirm_password'] ?? '') ?></span>

        <div class="button-row">
            <button type="submit">Register</button>
            <button type="button" onclick="window.location.href='index.php?page=register'">Refresh</button>
        </div>
    </form>

    <p class="login-text">
        Already have an account? <a href="index.php?page=login">Login</a>
    </p>
</div>

<script>
const nameInput = document.getElementById("name");
const emailInput = document.getElementById("email");
const passwordInput = document.getElementById("password");
const confirmPasswordInput = document.getElementById("confirmPassword");
const confirmError = document.getElementById("confirmError");
const registerForm = document.getElementById("registerForm");

emailInput.addEventListener("input", function () {
    emailInput.value = emailInput.value.toLowerCase();
});

confirmPasswordInput.addEventListener("input", function () {
    if (passwordInput.value !== confirmPasswordInput.value) {
        confirmError.textContent = "Passwords do not match.";
    } else {
        confirmError.textContent = "";
    }
});

registerForm.addEventListener("submit", function (e) {
    const nameValue = nameInput.value.trim();
    const emailValue = emailInput.value.trim();
    const passwordValue = passwordInput.value;
    const confirmValue = confirmPasswordInput.value;

    if (nameValue === "" || emailValue === "" || passwordValue === "" || confirmValue === "") {
        e.preventDefault();
        alert("Please fill in all required fields.");
        return;
    }

    if (passwordValue.length < 8) {
        e.preventDefault();
        alert("Password must be at least 8 characters.");
        return;
    }

    if (passwordValue !== confirmValue) {
        e.preventDefault();
        alert("Passwords do not match.");
    }
});

if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}
</script>

</body>
</html>