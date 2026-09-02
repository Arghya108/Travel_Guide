<?php

$title = 'Browse Posts';
require 'views/layouts/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Browse Destinations</h1>
        <p class="page-sub">Search and filter approved travel posts</p>
    </div>
</div>

<div class="card filter-card">
    <div class="filter-grid">
        <div class="field">
            <label>Search</label>
            <input type="text" id="searchBox" placeholder="Search by title or country...">
        </div>

        <div class="field">
            <label>Country</label>
            <select id="countryFilter">
                <option value="">All Countries</option>
                <?php foreach ($countries as $country): ?>
                    <option value="<?= h($country) ?>"><?= h($country) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label>Genre</label>
            <select id="genreFilter">
                <option value="">All Genres</option>
                <option value="beach">Beach</option>
                <option value="mountain">Mountain</option>
                <option value="city">City</option>
                <option value="historical">Historical</option>
                <option value="adventure">Adventure</option>
                <option value="cultural">Cultural</option>
                <option value="nature">Nature</option>
            </select>
        </div>

        <div class="field">
            <label>Cost Level</label>
            <select id="costFilter">
                <option value="">All Costs</option>
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
            </select>
        </div>
    </div>
</div>

<p class="result-count">
    <span id="resultCount"><?= count($posts) ?></span> destinations found
</p>

<div class="posts-grid" id="postGrid">
    <?php if (empty($posts)): ?>
        <div class="card empty-card">
            <h3>No approved posts found</h3>
            <p>Approved posts will appear here.</p>
        </div>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <div class="post-card">
                <div class="post-card-img">
                    <?php if (!empty($post['image'])): ?>
                        <img src="<?= h($post['image']) ?>" alt="<?= h($post['title']) ?>">
                    <?php else: ?>
                        &#127748;
                    <?php endif; ?>
                </div>

                <div class="post-card-body">
                    <h3><?= h($post['title']) ?></h3>

                    <div class="post-card-meta">
                        <span class="badge badge-info"><?= h($post['country']) ?></span>
                        <span class="badge badge-primary"><?= h($post['genre']) ?></span>
                        <span class="badge badge-success"><?= h($post['cost_level']) ?></span>
                    </div>

                    <p><?= h(substr($post['short_history'], 0, 120)) ?>...</p>
                </div>

                <div class="post-card-footer">
                    <a href="index.php?page=post_detail&id=<?= h($post['id']) ?>" class="btn-sm btn-view">
                        Read More
                    </a>

                    <?php if (isGeneralUser()): ?>
                        <button type="button" class="btn-sm btn-edit" onclick="addWishlist(<?= (int)$post['id'] ?>)">
                            Wishlist
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
const CSRF_TOKEN = "<?= h(csrfToken()) ?>";
const IS_GENERAL_USER = <?= isGeneralUser() ? 'true' : 'false' ?>;

function esc(value) {
    return String(value == null ? "" : value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
}

function fetchPosts() {
    const q = document.getElementById("searchBox").value.trim();
    const country = document.getElementById("countryFilter").value;
    const genre = document.getElementById("genreFilter").value;
    const cost = document.getElementById("costFilter").value;

    const url = "index.php?page=ajax&type=search_posts"
        + "&q=" + encodeURIComponent(q)
        + "&country=" + encodeURIComponent(country)
        + "&genre=" + encodeURIComponent(genre)
        + "&cost_level=" + encodeURIComponent(cost);

    fetch(url)
    .then(response => response.json())
    .then(posts => {
        const grid = document.getElementById("postGrid");
        const count = document.getElementById("resultCount");

        if (!Array.isArray(posts)) {
            grid.innerHTML = `<div class="card empty-card"><h3>Search failed</h3><p>Please try again.</p></div>`;
            count.textContent = "0";
            return;
        }

        count.textContent = posts.length;

        if (posts.length === 0) {
            grid.innerHTML = `<div class="card empty-card"><h3>No posts found</h3><p>Try different filters.</p></div>`;
            return;
        }

        grid.innerHTML = posts.map(post => {
            const imageHtml = post.image
                ? `<img src="${esc(post.image)}" alt="${esc(post.title)}">`
                : `&#127748;`;

            const wishlistButton = IS_GENERAL_USER
                ? `<button type="button" class="btn-sm btn-edit" onclick="addWishlist(${Number(post.id)})">Wishlist</button>`
                : "";

            return `
                <div class="post-card">
                    <div class="post-card-img">${imageHtml}</div>

                    <div class="post-card-body">
                        <h3>${esc(post.title)}</h3>

                        <div class="post-card-meta">
                            <span class="badge badge-info">${esc(post.country)}</span>
                            <span class="badge badge-primary">${esc(post.genre)}</span>
                            <span class="badge badge-success">${esc(post.cost_level)}</span>
                        </div>

                        <p>${esc(post.short_history).substring(0, 120)}...</p>
                    </div>

                    <div class="post-card-footer">
                        <a href="index.php?page=post_detail&id=${Number(post.id)}" class="btn-sm btn-view">
                            Read More
                        </a>
                        ${wishlistButton}
                    </div>
                </div>
            `;
        }).join("");
    })
    .catch(() => {
        alert("Search failed. Please try again.");
    });
}

let searchTimer = null;

["searchBox", "countryFilter", "genreFilter", "costFilter"].forEach(id => {
    const element = document.getElementById(id);

    element.addEventListener("input", function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(fetchPosts, 250);
    });

    element.addEventListener("change", fetchPosts);
});

function addWishlist(postId) {
    const formData = new FormData();
    formData.append("post_id", postId);
    formData.append("csrf_token", CSRF_TOKEN);

    fetch("index.php?page=ajax&type=wishlist_add", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("Added to wishlist.");
        } else {
            alert(data.error || "Could not add to wishlist.");
        }
    })
    .catch(() => {
        alert("Network error. Please try again.");
    });
}
</script>

<?php require 'views/layouts/footer.php'; ?>