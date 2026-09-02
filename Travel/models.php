<?php

function getUserByEmail($conn, $email) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);
    return $user ?: null;
}

function getUserById($conn, $id) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);
    return $user ?: null;
}

function emailExists($conn, $email, $excludeId = 0) {
    if ($excludeId > 0) {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'si', $email, $excludeId);
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $email);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $exists = mysqli_num_rows($result) > 0;

    mysqli_stmt_close($stmt);
    return $exists;
}

function createUser($conn, $name, $email, $password, $role, $isVerified = 0) {
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO users (name, email, password_hash, role, is_verified)
         VALUES (?, ?, ?, ?, ?)"
    );

    mysqli_stmt_bind_param($stmt, 'ssssi', $name, $email, $hash, $role, $isVerified);
    $ok = mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);
    return $ok;
}

function updateUserProfile($conn, $id, $name, $email, $profilePicture = null) {
    if ($profilePicture !== null) {
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE users SET name = ?, email = ?, profile_picture = ? WHERE id = ?"
        );

        mysqli_stmt_bind_param($stmt, 'sssi', $name, $email, $profilePicture, $id);
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE users SET name = ?, email = ? WHERE id = ?"
        );

        mysqli_stmt_bind_param($stmt, 'ssi', $name, $email, $id);
    }

    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $ok;
}

function updateUserPassword($conn, $id, $newPassword) {
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);

    $stmt = mysqli_prepare($conn, "UPDATE users SET password_hash = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'si', $hash, $id);

    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $ok;
}

function setRememberToken($conn, $userId, $tokenHash) {
    $stmt = mysqli_prepare($conn, "UPDATE users SET remember_token = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'si', $tokenHash, $userId);

    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $ok;
}

function getUserByRememberToken($conn, $tokenHash) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE remember_token = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $tokenHash);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);
    return $user ?: null;
}

function clearRememberToken($conn, $userId) {
    $stmt = mysqli_prepare($conn, "UPDATE users SET remember_token = NULL WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $userId);

    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $ok;
}

/* ================================================================
   HOME / BROWSE / POST MODELS
================================================================ */

function getLatestApprovedPosts($conn, $limit = 6) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT p.*, u.name AS scout_name
         FROM posts p
         LEFT JOIN users u ON p.scout_id = u.id
         WHERE p.status = 'approved'
         ORDER BY p.created_at DESC
         LIMIT ?"
    );

    mysqli_stmt_bind_param($stmt, 'i', $limit);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $rows = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

    mysqli_stmt_close($stmt);
    return $rows;
}

function getApprovedPosts($conn) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT p.*, u.name AS scout_name
         FROM posts p
         LEFT JOIN users u ON p.scout_id = u.id
         WHERE p.status = 'approved'
         ORDER BY p.created_at DESC"
    );

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $rows = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

    mysqli_stmt_close($stmt);
    return $rows;
}

function getApprovedPost($conn, $id) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT p.*, u.name AS scout_name
         FROM posts p
         LEFT JOIN users u ON p.scout_id = u.id
         WHERE p.id = ? AND p.status = 'approved'
         LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $post = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);
    return $post ?: null;
}

function getPostById($conn, $id) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM posts WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $post = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);
    return $post ?: null;
}

function getDistinctCountries($conn) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT DISTINCT country FROM posts WHERE status = 'approved' ORDER BY country ASC"
    );

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $countries = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $countries[] = $row['country'];
    }

    mysqli_stmt_close($stmt);
    return $countries;
}

function searchApprovedPosts($conn, $q, $country, $genre, $costLevel) {
    $allowedCosts = ['low', 'medium', 'high'];
    $allowedGenres = ['beach', 'mountain', 'city', 'historical', 'adventure', 'cultural', 'nature'];

    if (!in_array($costLevel, $allowedCosts)) {
        $costLevel = '';
    }

    if (!in_array($genre, $allowedGenres)) {
        $genre = '';
    }

    $like = '%' . $q . '%';

    $stmt = mysqli_prepare(
        $conn,
        "SELECT p.*, u.name AS scout_name
         FROM posts p
         LEFT JOIN users u ON p.scout_id = u.id
         WHERE p.status = 'approved'
           AND (? = '' OR p.title LIKE ? OR p.country LIKE ?)
           AND (? = '' OR p.country = ?)
           AND (? = '' OR p.genre = ?)
           AND (? = '' OR p.cost_level = ?)
         ORDER BY p.created_at DESC"
    );

    mysqli_stmt_bind_param(
        $stmt,
        'sssssssss',
        $q,
        $like,
        $like,
        $country,
        $country,
        $genre,
        $genre,
        $costLevel,
        $costLevel
    );

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $posts = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $posts[] = $row;
    }

    mysqli_stmt_close($stmt);
    return $posts;
}

/* ================================================================
   WISHLIST MODELS
================================================================ */

function isPostApproved($conn, $postId) {
    $stmt = mysqli_prepare($conn, "SELECT id FROM posts WHERE id = ? AND status = 'approved' LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $postId);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $exists = mysqli_num_rows($result) > 0;

    mysqli_stmt_close($stmt);
    return $exists;
}

function addToWishlist($conn, $userId, $postId) {
    if (!isPostApproved($conn, $postId)) {
        return false;
    }

    $stmt = mysqli_prepare(
        $conn,
        "INSERT IGNORE INTO wishlist (user_id, post_id) VALUES (?, ?)"
    );

    mysqli_stmt_bind_param($stmt, 'ii', $userId, $postId);
    $ok = mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);
    return $ok;
}

function removeFromWishlist($conn, $userId, $postId) {
    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM wishlist WHERE user_id = ? AND post_id = ?"
    );

    mysqli_stmt_bind_param($stmt, 'ii', $userId, $postId);
    $ok = mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);
    return $ok;
}

function getWishlist($conn, $userId) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT w.*, p.title, p.country, p.genre, p.cost_level, p.image, p.short_history
         FROM wishlist w
         INNER JOIN posts p ON w.post_id = p.id
         WHERE w.user_id = ? AND p.status = 'approved'
         ORDER BY w.added_at DESC"
    );

    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $rows = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

    mysqli_stmt_close($stmt);
    return $rows;
}

function isInWishlist($conn, $userId, $postId) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id FROM wishlist WHERE user_id = ? AND post_id = ? LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, 'ii', $userId, $postId);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $exists = mysqli_num_rows($result) > 0;

    mysqli_stmt_close($stmt);
    return $exists;
}

/* ================================================================
   SCOUT MODELS
================================================================ */

function getScoutRequestCounts($conn, $scoutId) {
    $counts = [
        'total' => 0,
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0
    ];

    $stmt = mysqli_prepare(
        $conn,
        "SELECT status, COUNT(*) AS total
         FROM post_requests
         WHERE scout_id = ?
         GROUP BY status"
    );

    mysqli_stmt_bind_param($stmt, 'i', $scoutId);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $counts[$row['status']] = (int)$row['total'];
        $counts['total'] += (int)$row['total'];
    }

    mysqli_stmt_close($stmt);
    return $counts;
}

function createPostRequest($conn, $scoutId, $postData, $originalPostId = null) {
    $json = json_encode($postData, JSON_UNESCAPED_UNICODE);

    if ($originalPostId) {
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO post_requests (scout_id, post_data, original_post_id, status)
             VALUES (?, ?, ?, 'pending')"
        );

        mysqli_stmt_bind_param($stmt, 'isi', $scoutId, $json, $originalPostId);
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO post_requests (scout_id, post_data, status)
             VALUES (?, ?, 'pending')"
        );

        mysqli_stmt_bind_param($stmt, 'is', $scoutId, $json);
    }

    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $ok;
}

function getScoutRequests($conn, $scoutId) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT *
         FROM post_requests
         WHERE scout_id = ?
         ORDER BY requested_at DESC"
    );

    mysqli_stmt_bind_param($stmt, 'i', $scoutId);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $rows = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

    mysqli_stmt_close($stmt);
    return $rows;
}

function searchScoutRequests($conn, $scoutId, $q) {
    $like = '%' . $q . '%';

    $stmt = mysqli_prepare(
        $conn,
        "SELECT *
         FROM post_requests
         WHERE scout_id = ?
           AND (post_data LIKE ? OR status LIKE ?)
         ORDER BY requested_at DESC"
    );

    mysqli_stmt_bind_param($stmt, 'iss', $scoutId, $like, $like);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $rows = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

    mysqli_stmt_close($stmt);
    return $rows;
}

function getScoutRequestById($conn, $id, $scoutId) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT *
         FROM post_requests
         WHERE id = ? AND scout_id = ?
         LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, 'ii', $id, $scoutId);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $request = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);
    return $request ?: null;
}

function updatePostRequest($conn, $id, $scoutId, $postData) {
    $json = json_encode($postData, JSON_UNESCAPED_UNICODE);

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE post_requests
         SET post_data = ?
         WHERE id = ? AND scout_id = ? AND status = 'pending'"
    );

    mysqli_stmt_bind_param($stmt, 'sii', $json, $id, $scoutId);
    $ok = mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);
    return $ok;
}

function deletePostRequest($conn, $id, $scoutId) {
    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM post_requests
         WHERE id = ? AND scout_id = ? AND status = 'pending'"
    );

    mysqli_stmt_bind_param($stmt, 'ii', $id, $scoutId);
    $ok = mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);
    return $ok;
}

function getScoutApprovedPosts($conn, $scoutId) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT *
         FROM posts
         WHERE scout_id = ? AND status = 'approved'
         ORDER BY created_at DESC"
    );

    mysqli_stmt_bind_param($stmt, 'i', $scoutId);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $rows = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

    mysqli_stmt_close($stmt);
    return $rows;
}

/* ================================================================
   ADMIN MODELS
================================================================ */

function getAdminDashboardCounts($conn) {
    $counts = [
        'total_users' => 0,
        'admins' => 0,
        'scouts' => 0,
        'users' => 0,
        'pending_requests' => 0,
        'total_posts' => 0,
        'total_comments' => 0
    ];

    $result = mysqli_query($conn, "SELECT role, COUNT(*) AS total FROM users GROUP BY role");

    while ($row = mysqli_fetch_assoc($result)) {
        if ($row['role'] === 'admin') {
            $counts['admins'] = (int)$row['total'];
        } elseif ($row['role'] === 'scout') {
            $counts['scouts'] = (int)$row['total'];
        } elseif ($row['role'] === 'user') {
            $counts['users'] = (int)$row['total'];
        }

        $counts['total_users'] += (int)$row['total'];
    }

    $result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM post_requests WHERE status = 'pending'");
    $counts['pending_requests'] = (int)mysqli_fetch_assoc($result)['total'];

    $result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM posts WHERE status = 'approved'");
    $counts['total_posts'] = (int)mysqli_fetch_assoc($result)['total'];

    $result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM comments");
    $counts['total_comments'] = (int)mysqli_fetch_assoc($result)['total'];

    return $counts;
}

function getUsers($conn) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM users ORDER BY created_at DESC");
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $users = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }

    mysqli_stmt_close($stmt);
    return $users;
}

function searchUsers($conn, $q) {
    $like = '%' . $q . '%';

    $stmt = mysqli_prepare(
        $conn,
        "SELECT *
         FROM users
         WHERE name LIKE ? OR email LIKE ? OR role LIKE ?
         ORDER BY created_at DESC"
    );

    mysqli_stmt_bind_param($stmt, 'sss', $like, $like, $like);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $users = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }

    mysqli_stmt_close($stmt);
    return $users;
}

function addUserByAdmin($conn, $name, $email, $password, $role, $isVerified) {
    return createUser($conn, $name, $email, $password, $role, $isVerified);
}

function toggleUserVerification($conn, $id, $isVerified) {
    $stmt = mysqli_prepare(
        $conn,
        "UPDATE users SET is_verified = ? WHERE id = ?"
    );

    mysqli_stmt_bind_param($stmt, 'ii', $isVerified, $id);
    $ok = mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);
    return $ok;
}

function deleteUser($conn, $id) {
    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM users WHERE id = ?"
    );

    mysqli_stmt_bind_param($stmt, 'i', $id);
    $ok = mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);
    return $ok;
}

function getPendingPostRequests($conn) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT pr.*, u.name AS scout_name, u.email AS scout_email
         FROM post_requests pr
         INNER JOIN users u ON pr.scout_id = u.id
         WHERE pr.status = 'pending'
         ORDER BY pr.requested_at DESC"
    );

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $rows = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

    mysqli_stmt_close($stmt);
    return $rows;
}

function getAllPosts($conn) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT p.*, u.name AS scout_name
         FROM posts p
         LEFT JOIN users u ON p.scout_id = u.id
         ORDER BY p.created_at DESC"
    );

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $posts = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $posts[] = $row;
    }

    mysqli_stmt_close($stmt);
    return $posts;
}

function getBaseCostByLevel($costLevel) {
    if ($costLevel === 'low') {
        return 500;
    }

    if ($costLevel === 'medium') {
        return 1500;
    }

    if ($costLevel === 'high') {
        return 3000;
    }

    return 1000;
}

function saveCostEstimate($conn, $postId, $costLevel) {
    $baseCost = getBaseCostByLevel($costLevel);
    $currency = 'USD';

    $check = mysqli_prepare($conn, "SELECT id FROM cost_estimates WHERE post_id = ? LIMIT 1");
    mysqli_stmt_bind_param($check, 'i', $postId);
    mysqli_stmt_execute($check);

    $result = mysqli_stmt_get_result($check);
    $exists = mysqli_fetch_assoc($result);
    mysqli_stmt_close($check);

    if ($exists) {
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE cost_estimates
             SET base_cost = ?, currency = ?
             WHERE post_id = ?"
        );

        mysqli_stmt_bind_param($stmt, 'dsi', $baseCost, $currency, $postId);
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO cost_estimates (post_id, base_cost, currency)
             VALUES (?, ?, ?)"
        );

        mysqli_stmt_bind_param($stmt, 'ids', $postId, $baseCost, $currency);
    }

    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $ok;
}

function approvePostRequest($conn, $requestId) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT *
         FROM post_requests
         WHERE id = ? AND status = 'pending'
         LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, 'i', $requestId);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $request = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$request) {
        return false;
    }

    $data = json_decode($request['post_data'], true);

    if (!is_array($data)) {
        return false;
    }

    $title = trim($data['title'] ?? '');
    $shortHistory = trim($data['short_history'] ?? '');
    $countryRepresentation = trim($data['country_representation'] ?? '');
    $country = trim($data['country'] ?? '');
    $genre = trim($data['genre'] ?? '');
    $costLevel = trim($data['cost_level'] ?? '');
    $travelMediumInfo = trim($data['travel_medium_info'] ?? '');
    $image = trim($data['image'] ?? '');

    if ($countryRepresentation !== '') {
        $shortHistory .= "\n\nCountry Representation: " . $countryRepresentation;
    }

    $allowedCosts = ['low', 'medium', 'high'];

    if (
        $title === '' ||
        $shortHistory === '' ||
        $country === '' ||
        $genre === '' ||
        $travelMediumInfo === '' ||
        !in_array($costLevel, $allowedCosts)
    ) {
        return false;
    }

    mysqli_begin_transaction($conn);

    try {
        if (!empty($request['original_post_id'])) {
            $postId = (int)$request['original_post_id'];

            $stmt = mysqli_prepare(
                $conn,
                "UPDATE posts
                 SET title = ?,
                     short_history = ?,
                     country = ?,
                     genre = ?,
                     cost_level = ?,
                     travel_medium_info = ?,
                     image = COALESCE(NULLIF(?, ''), image),
                     status = 'approved'
                 WHERE id = ?"
            );

            mysqli_stmt_bind_param(
                $stmt,
                'sssssssi',
                $title,
                $shortHistory,
                $country,
                $genre,
                $costLevel,
                $travelMediumInfo,
                $image,
                $postId
            );

            $ok = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            if (!$ok) {
                throw new Exception('Post update failed.');
            }

            saveCostEstimate($conn, $postId, $costLevel);
        } else {
            $scoutId = (int)$request['scout_id'];

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO posts
                 (scout_id, title, short_history, country, genre, cost_level, travel_medium_info, image, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'approved')"
            );

            mysqli_stmt_bind_param(
                $stmt,
                'isssssss',
                $scoutId,
                $title,
                $shortHistory,
                $country,
                $genre,
                $costLevel,
                $travelMediumInfo,
                $image
            );

            $ok = mysqli_stmt_execute($stmt);
            $postId = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            if (!$ok) {
                throw new Exception('Post creation failed.');
            }

            saveCostEstimate($conn, $postId, $costLevel);
        }

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE post_requests
             SET status = 'approved'
             WHERE id = ?"
        );

        mysqli_stmt_bind_param($stmt, 'i', $requestId);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if (!$ok) {
            throw new Exception('Request status update failed.');
        }

        mysqli_commit($conn);
        return true;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return false;
    }
}

function rejectPostRequest($conn, $requestId) {
    $stmt = mysqli_prepare(
        $conn,
        "UPDATE post_requests
         SET status = 'rejected'
         WHERE id = ? AND status = 'pending'"
    );

    mysqli_stmt_bind_param($stmt, 'i', $requestId);
    $ok = mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);
    return $ok;
}

function updatePost($conn, $id, $title, $shortHistory, $country, $genre, $costLevel, $travelMediumInfo) {
    $stmt = mysqli_prepare(
        $conn,
        "UPDATE posts
         SET title = ?,
             short_history = ?,
             country = ?,
             genre = ?,
             cost_level = ?,
             travel_medium_info = ?
         WHERE id = ?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        'ssssssi',
        $title,
        $shortHistory,
        $country,
        $genre,
        $costLevel,
        $travelMediumInfo,
        $id
    );

    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($ok) {
        saveCostEstimate($conn, $id, $costLevel);
    }

    return $ok;
}

function deletePost($conn, $id) {
    $stmt = mysqli_prepare($conn, "DELETE FROM posts WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);

    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $ok;
}

/* ================================================================
   COMMENT / COST MODELS
================================================================ */

function getAllComments($conn) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT c.*, u.name AS user_name, p.title AS post_title
         FROM comments c
         INNER JOIN users u ON c.user_id = u.id
         INNER JOIN posts p ON c.post_id = p.id
         ORDER BY c.created_at DESC"
    );

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $comments = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $comments[] = $row;
    }

    mysqli_stmt_close($stmt);
    return $comments;
}

function searchComments($conn, $q) {
    $like = '%' . $q . '%';

    $stmt = mysqli_prepare(
        $conn,
        "SELECT c.*, u.name AS user_name, p.title AS post_title
         FROM comments c
         INNER JOIN users u ON c.user_id = u.id
         INNER JOIN posts p ON c.post_id = p.id
         WHERE c.content LIKE ? OR u.name LIKE ? OR p.title LIKE ?
         ORDER BY c.created_at DESC"
    );

    mysqli_stmt_bind_param($stmt, 'sss', $like, $like, $like);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $comments = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $comments[] = $row;
    }

    mysqli_stmt_close($stmt);
    return $comments;
}

function deleteCommentAdmin($conn, $id) {
    $stmt = mysqli_prepare($conn, "DELETE FROM comments WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);

    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $ok;
}

function getComments($conn, $postId) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT c.*, u.name AS user_name
         FROM comments c
         INNER JOIN users u ON c.user_id = u.id
         WHERE c.post_id = ?
         ORDER BY c.created_at DESC"
    );

    mysqli_stmt_bind_param($stmt, 'i', $postId);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $comments = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $comments[] = $row;
    }

    mysqli_stmt_close($stmt);
    return $comments;
}


//AJAX can receive the new comment ID and show the comment instantly without page reload.
function addComment($conn, $postId, $userId, $content) {
    if (!isPostApproved($conn, $postId)) {
        return false;
    }

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO comments (post_id, user_id, content)
         VALUES (?, ?, ?)"
    );

    mysqli_stmt_bind_param($stmt, 'iis', $postId, $userId, $content);
    $ok = mysqli_stmt_execute($stmt);

    if ($ok) {
        $commentId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        return $commentId;
    }

    mysqli_stmt_close($stmt);
    return false;
}
//eituko user can only delete their own comment, admin can delete any comment


function deleteOwnComment($conn, $commentId, $userId) {
    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM comments
         WHERE id = ? AND user_id = ?"
    );

    mysqli_stmt_bind_param($stmt, 'ii', $commentId, $userId);
    $ok = mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);
    return $ok;
}

function getCostEstimate($conn, $postId) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT *
         FROM cost_estimates
         WHERE post_id = ?
         LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, 'i', $postId);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $estimate = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);
    return $estimate ?: null;
}

function calculateEstimatedCost($conn, $postId, $travelers, $days) {
    $post = getApprovedPost($conn, $postId);

    if (!$post) {
        return null;
    }

    $estimate = getCostEstimate($conn, $postId);

    if ($estimate) {
        $baseCost = (float)$estimate['base_cost'];
        $currency = $estimate['currency'];
    } else {
        $baseCost = getBaseCostByLevel($post['cost_level']);
        $currency = 'USD';
    }

    $total = ($baseCost * $travelers * $days) / 7;

    return [
        'base_cost' => $baseCost,
        'currency' => $currency,
        'total' => round($total, 2)
    ];
}
?>