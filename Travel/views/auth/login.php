<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login &mdash; Travel Guide</title>
<link rel="stylesheet" href="style.css">
</head>

<body class="auth-body">

<div class="login-card">
    <div class="logo-center">&#127758;</div>

    <h2>Login</h2>
    <p class="subtitle">Travel Guide Management System</p>

    <?php if (!empty($flash)): ?>
        <div class="alert alert-<?= h($flash['type']) ?>">
            <?= h($flash['message']) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <?= h($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="index.php?page=login" class="form" id="loginForm" autocomplete="off" novalidate>
        <?= csrfField() ?>

        <!-- Fake hidden fields to stop browser autofill -->
        <input type="text" name="fake_email" autocomplete="username" style="display:none;">
        <input type="password" name="fake_password" autocomplete="current-password" style="display:none;">

        <label>Email Address <span class="star">*</span></label>
        <input 
            type="email" 
            name="email" 
            id="loginEmail"
            value="" 
            placeholder="sample@email.com" 
            autocomplete="off"
            required
            autofocus
        >

        <label>Password <span class="star">*</span></label>
        <div class="password-field">
            <input 
                type="password" 
                name="password" 
                id="loginPassword"
                value=""
                placeholder="Enter password" 
                autocomplete="new-password"
                required
            >

            <button type="button" class="toggle-pass" onclick="togglePassword()">
                👁
            </button>
        </div>

        <label class="checkbox">
            <input type="checkbox" name="remember">
            <span>Remember me for 30 days</span>
        </label>

        <div class="button-row">
            <button type="submit">Login</button>
            <button type="button" onclick="window.location.href='index.php?page=login'">Refresh</button>
        </div>
    </form>

    <p class="signup-text">
        New here? <a href="index.php?page=register">Create Account</a>
    </p>

    <div class="hint">
        <strong>Default Admin Login:</strong><br>
        Email: arghyadas108@gmail.com<br>
        Password: admin123
    </div>
</div>

<script>
function togglePassword() {
    const passwordInput = document.getElementById("loginPassword");
    const toggleButton = document.querySelector(".toggle-pass");

    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        toggleButton.textContent = "🙈";
    } else {
        passwordInput.type = "password";
        toggleButton.textContent = "👁";
    }
}

function clearLoginFields() {
    const email = document.getElementById("loginEmail");
    const password = document.getElementById("loginPassword");

    if (email) {
        email.value = "";
    }

    if (password) {
        password.value = "";
    }
}

window.addEventListener("load", function () {
    clearLoginFields();

    setTimeout(clearLoginFields, 200);
    setTimeout(clearLoginFields, 800);
});

if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}
</script>

</body>
</html>