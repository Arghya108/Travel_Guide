<?php

$title = 'Edit Request';
require 'views/layouts/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Edit Post Request</h1>
        <p class="page-sub">Only pending requests can be edited</p>
    </div>

    <a href="index.php?page=my_requests" class="btn btn-ghost">Back to My Requests</a>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= h($error) ?></div>
<?php endif; ?>

<div class="card form-card">
    <form method="POST" action="index.php?page=edit_request&id=<?= h($request['id']) ?>" enctype="multipart/form-data" class="form" id="editRequestForm" novalidate>
        <?= csrfField() ?>

        <input type="hidden" name="id" value="<?= h($request['id']) ?>">

        <label>Title <span class="star">*</span></label>
        <input
            type="text"
            name="title"
            value="<?= h($data['title'] ?? '') ?>"
            placeholder="Enter place title"
            required
        >
        <span class="error" data-error-for="title"></span>

        <label>Short History <span class="star">*</span></label>
        <textarea
            name="short_history"
            placeholder="Write a short history or description"
            required
        ><?= h($data['short_history'] ?? '') ?></textarea>
        <span class="error" data-error-for="short_history"></span>

        <label>Country Representation / Cultural Significance</label>
        <textarea
            name="country_representation"
            placeholder="Cultural value or country representation"
        ><?= h($data['country_representation'] ?? '') ?></textarea>

        <label>Country <span class="star">*</span></label>
        <input
            type="text"
            name="country"
            value="<?= h($data['country'] ?? '') ?>"
            placeholder="Enter country"
            required
        >
        <span class="error" data-error-for="country"></span>

        <label>Genre <span class="star">*</span></label>
        <select name="genre" required>
            <?php
            $genres = ['beach', 'mountain', 'city', 'historical', 'adventure', 'cultural', 'nature'];
            $selectedGenre = $data['genre'] ?? '';

            foreach ($genres as $genre):
            ?>
                <option value="<?= h($genre) ?>" <?= $selectedGenre === $genre ? 'selected' : '' ?>>
                    <?= h(ucfirst($genre)) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <span class="error" data-error-for="genre"></span>

        <label>Cost Level <span class="star">*</span></label>
        <select name="cost_level" required>
            <?php
            $costLevels = ['low', 'medium', 'high'];
            $selectedCost = $data['cost_level'] ?? '';

            foreach ($costLevels as $level):
            ?>
                <option value="<?= h($level) ?>" <?= $selectedCost === $level ? 'selected' : '' ?>>
                    <?= h(ucfirst($level)) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <span class="error" data-error-for="cost_level"></span>

        <label>Travel Medium Information <span class="star">*</span></label>
        <textarea
            name="travel_medium_info"
            placeholder="Travel route and transport information"
            required
        ><?= h($data['travel_medium_info'] ?? '') ?></textarea>
        <span class="error" data-error-for="travel_medium_info"></span>

        <?php if (!empty($data['image'])): ?>
            <div class="profile-preview">
                <p class="muted">Current Image:</p>
                <img src="<?= h($data['image']) ?>" alt="Request Image">
            </div>
        <?php endif; ?>

        <label>Replace Image</label>
        <input type="file" name="image" accept="image/*">
        <span class="error" data-error-for="image"></span>

        <div class="button-row">
            <button type="submit">Update Request</button>
            <button type="button" onclick="window.location.href='index.php?page=my_requests'">Cancel</button>
        </div>
    </form>
</div>

<!-- 
Inline JavaScript for form validation and AJAX submission.
AJAX POST done.
No full page submission.
Inline errors supported.
-->

<script>
const editRequestForm = document.getElementById("editRequestForm");

function clearInlineErrors() {
    document.querySelectorAll("[data-error-for]").forEach(span => {
        span.textContent = "";
    });

    editRequestForm.querySelectorAll(".input-error").forEach(input => {
        input.classList.remove("input-error");
    });
}

function showInlineError(field, message) {
    const span = document.querySelector(`[data-error-for="${field}"]`);
    const input = editRequestForm.querySelector(`[name="${field}"]`);

    if (span) {
        span.textContent = message;
    }

    if (input) {
        input.classList.add("input-error");
    }
}

editRequestForm.addEventListener("submit", function (e) {
    e.preventDefault();
    clearInlineErrors();

    const title = document.querySelector("[name='title']").value.trim();
    const history = document.querySelector("[name='short_history']").value.trim();
    const country = document.querySelector("[name='country']").value.trim();
    const travel = document.querySelector("[name='travel_medium_info']").value.trim();

    let hasError = false;

    if (title === "") {
        showInlineError("title", "Title is required.");
        hasError = true;
    }

    if (history === "") {
        showInlineError("short_history", "Short history is required.");
        hasError = true;
    }

    if (country === "") {
        showInlineError("country", "Country is required.");
        hasError = true;
    }

    if (travel === "") {
        showInlineError("travel_medium_info", "Travel medium information is required.");
        hasError = true;
    }

    if (hasError) {
        return;
    }

    const formData = new FormData(editRequestForm);

    fetch("index.php?page=ajax&type=edit_request_ajax", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = data.redirect || "index.php?page=my_requests";
            return;
        }

        if (data.errors) {
            Object.keys(data.errors).forEach(field => {
                showInlineError(field, data.errors[field]);
            });
            return;
        }

        alert(data.error || "Request update failed.");
    })
    .catch(() => {
        alert("Network error. Please try again.");
    });
});

if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}
</script>

<?php require 'views/layouts/footer.php'; ?>