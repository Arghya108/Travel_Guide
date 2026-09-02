<?php

$title = $originalPostId > 0 ? 'Request Changes' : 'Create Request';
require 'views/layouts/header.php';

function prefillValue($prefill, $key) {
    if (is_array($prefill) && isset($prefill[$key])) {
        return $prefill[$key];
    }

    return '';
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <?= $originalPostId > 0 ? 'Request Changes' : 'Create Post Request' ?>
        </h1>
        <p class="page-sub">
            <?= $originalPostId > 0
                ? 'Submit a change request for your approved post'
                : 'Submit destination information for admin approval'
            ?>
        </p>
    </div>

    <a href="index.php?page=my_requests" class="btn btn-ghost">My Requests</a>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= h($error) ?></div>
<?php endif; ?>

<div class="card form-card">
    <form method="POST" action="index.php?page=create_request" enctype="multipart/form-data" class="form" id="requestForm" novalidate>
        <?= csrfField() ?>

        <input type="hidden" name="original_post_id" value="<?= h($originalPostId) ?>">

        <label>Title <span class="star">*</span></label>
        <input
            type="text"
            name="title"
            value="<?= h(prefillValue($prefill, 'title')) ?>"
            placeholder="Example: Cox's Bazar Sea Beach"
            required
        >

        <label>Short History <span class="star">*</span></label>
        <textarea
            name="short_history"
            placeholder="Write a short history or description of the place"
            required
        ><?= h(prefillValue($prefill, 'short_history')) ?></textarea>

        <label>Country Representation / Cultural Significance</label>
        <textarea
            name="country_representation"
            placeholder="Example: Cultural value, local identity, famous landmark, national representation..."
        ></textarea>

        <label>Country <span class="star">*</span></label>
        <input
            type="text"
            name="country"
            value="<?= h(prefillValue($prefill, 'country')) ?>"
            placeholder="Example: Bangladesh"
            required
        >

        <label>Genre <span class="star">*</span></label>
        <select name="genre" required>
            <?php
            $genres = ['beach', 'mountain', 'city', 'historical', 'adventure', 'cultural', 'nature'];
            $selectedGenre = prefillValue($prefill, 'genre');

            foreach ($genres as $genre):
            ?>
                <option value="<?= h($genre) ?>" <?= $selectedGenre === $genre ? 'selected' : '' ?>>
                    <?= h(ucfirst($genre)) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Cost Level <span class="star">*</span></label>
        <select name="cost_level" required>
            <?php
            $costLevels = ['low', 'medium', 'high'];
            $selectedCost = prefillValue($prefill, 'cost_level');

            foreach ($costLevels as $level):
            ?>
                <option value="<?= h($level) ?>" <?= $selectedCost === $level ? 'selected' : '' ?>>
                    <?= h(ucfirst($level)) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Travel Medium Information <span class="star">*</span></label>
        <textarea
            name="travel_medium_info"
            placeholder="Example: Flight, bus, train, local transport, route details..."
            required
        ><?= h(prefillValue($prefill, 'travel_medium_info')) ?></textarea>

        <label>Post Image</label>
        <input type="file" name="image" accept="image/*">

        <?php if ($originalPostId > 0 && !empty($prefill['image'])): ?>
            <div class="profile-preview">
                <p class="muted">Current Image:</p>
                <img src="<?= h($prefill['image']) ?>" alt="Current Post Image">
            </div>
        <?php endif; ?>

        <div class="button-row">
            <button type="submit">
                <?= $originalPostId > 0 ? 'Submit Change Request' : 'Submit Request' ?>
            </button>
            <button type="button" onclick="window.location.href='index.php?page=my_requests'">Cancel</button>
        </div>
    </form>
</div>

<script>
document.getElementById("requestForm").addEventListener("submit", function (e) {
    const title = document.querySelector("[name='title']").value.trim();
    const history = document.querySelector("[name='short_history']").value.trim();
    const country = document.querySelector("[name='country']").value.trim();
    const travel = document.querySelector("[name='travel_medium_info']").value.trim();

    if (title === "" || history === "" || country === "" || travel === "") {
        e.preventDefault();
        alert("Please fill in all required fields.");
    }
});

if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}
</script>

<?php require 'views/layouts/footer.php'; ?>