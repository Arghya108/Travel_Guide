<?php
// Handles page logic, validation, routing to views, and AJAX actions


function redirectAfterLogin($user) {
    if ($user['is_verified'] != 1) {
        redirect('index.php?page=home');
    }

    if ($user['role'] === 'admin') {
        redirect('index.php?page=admin_dashboard');
    }

    if ($user['role'] === 'scout') {
        redirect('index.php?page=scout_dashboard');
    }

    redirect('index.php?page=home');
}

//eita PHP re data read korte dey AJAX delete request theke
function requestBodyData() {
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'DELETE' || $method === 'PUT' || $method === 'PATCH') {
        $raw = file_get_contents('php://input');
        $data = [];
        parse_str($raw, $data);
        return $data;
    }

    return $_POST;
}


/* AUTH CONTROLLERS */

function loginCtrl($conn) {
    $error = '';
    $prefill = $_COOKIE['remember_email'] ?? '';
    $flash = getFlash();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid request. Please refresh and try again.';
        } else {
            $email = strtolower(cleanInput($_POST['email'] ?? ''));
            $password = $_POST['password'] ?? '';
            $remember = isset($_POST['remember']);

            if ($email === '' || $password === '') {
                $error = 'Please fill in both email and password.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } else {
                $user = getUserByEmail($conn, $email);

                if ($user && password_verify($password, $user['password_hash'])) {
                    session_regenerate_id(true);
                    setLoginSession($user);

                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        $tokenHash = hash('sha256', $token);

                        setRememberToken($conn, $user['id'], $tokenHash);
                        setSecureCookie('remember_token', $token, time() + 86400 * 30);
                        setSecureCookie('remember_email', $email, time() + 86400 * 30);
                    }

                    redirectAfterLogin($user);
                } else {
                    $error = 'Invalid email or password.';
                }
            }
        }
    }

    require 'views/auth/login.php';
}

function registerCtrl($conn) {
    $errors = [];
    $name = '';
    $email = '';
    $role = 'user';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            $errors['general'] = 'Invalid request. Please refresh and try again.';
        } else {
            $name = cleanInput($_POST['name'] ?? '');
            $email = strtolower(cleanInput($_POST['email'] ?? ''));
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            $role = cleanInput($_POST['role'] ?? 'user');

            $allowedRoles = ['admin', 'scout', 'user'];

            if ($name === '') {
                $errors['name'] = 'Name is required.';
            } elseif (!preg_match("/^[a-zA-Z-' ]+$/", $name)) {
                $errors['name'] = 'Only letters and spaces are allowed.';
            }

            if ($email === '') {
                $errors['email'] = 'Email is required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email format.';
            } elseif (emailExists($conn, $email)) {
                $errors['email'] = 'This email is already registered.';
            }

            if ($password === '') {
                $errors['password'] = 'Password is required.';
            } elseif (strlen($password) < 8) {
                $errors['password'] = 'Password must be at least 8 characters.';
            }

            if ($confirmPassword === '') {
                $errors['confirm_password'] = 'Confirm password is required.';
            } elseif ($password !== $confirmPassword) {
                $errors['confirm_password'] = 'Passwords do not match.';
            }

            if (!in_array($role, $allowedRoles)) {
                $errors['role'] = 'Invalid role selected.';
            }

            if (empty($errors)) {
                $ok = createUser($conn, $name, $email, $password, $role, 0);

                if ($ok) {
                    setFlash('success', 'Registration successful. Please wait for admin verification.');
                    redirect('index.php?page=login');
                }

                $errors['general'] = 'Registration failed. Please try again.';
            }
        }
    }

    require 'views/auth/register.php';
}

function logoutCtrl($conn) {
    if (isset($_SESSION['user'])) {
        clearRememberToken($conn, $_SESSION['user']['id']);
    }

    $_SESSION = [];
    session_destroy();

    clearCookieValue('remember_token');
    clearCookieValue('remember_email');

    redirect('index.php?page=login');
}

/* 
   USER CONTROLLERS
 */

function homeCtrl($conn) {
    $posts = getLatestApprovedPosts($conn, 6);
    require 'views/user/home.php';
}

function profileCtrl($conn) {
    $user = getUserById($conn, $_SESSION['user']['id']);
    $success = '';
    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid request. Please refresh and try again.';
        } else {
            $action = $_POST['action'] ?? '';

            if ($action === 'update_profile') {
                $name = cleanInput($_POST['name'] ?? '');
                $email = strtolower(cleanInput($_POST['email'] ?? ''));
                $profilePicture = null;

                if ($name === '' || $email === '') {
                    $error = 'Name and email are required.';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Invalid email format.';
                } elseif (emailExists($conn, $email, $user['id'])) {
                    $error = 'This email is already used by another account.';
                } else {
                    if (!empty($_FILES['profile_picture']['name'])) {
                        $uploadError = '';
                        $uploaded = uploadImage($_FILES['profile_picture'], PROFILE_UPLOAD_DIR, PROFILE_UPLOAD_WEB, $uploadError);

                        if ($uploaded === false) {
                            $error = $uploadError;
                        } else {
                            $profilePicture = $uploaded;
                        }
                    }

                    if ($error === '') {
                        $ok = updateUserProfile($conn, $user['id'], $name, $email, $profilePicture);

                        if ($ok) {
                            $updated = getUserById($conn, $user['id']);
                            setLoginSession($updated);
                            $user = $updated;
                            $success = 'Profile updated successfully.';
                        } else {
                            $error = 'Profile update failed.';
                        }
                    }
                }
            }

            if ($action === 'change_password') {
                $currentPassword = $_POST['current_password'] ?? '';
                $newPassword = $_POST['new_password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';

                if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
                    $error = 'All password fields are required.';
                } elseif (!password_verify($currentPassword, $user['password_hash'])) {
                    $error = 'Current password is incorrect.';
                } elseif (strlen($newPassword) < 8) {
                    $error = 'New password must be at least 8 characters.';
                } elseif ($newPassword !== $confirmPassword) {
                    $error = 'New passwords do not match.';
                } else {
                    $ok = updateUserPassword($conn, $user['id'], $newPassword);
                    $success = $ok ? 'Password changed successfully.' : 'Password change failed.';
                }
            }
        }
    }

    require 'views/user/profile.php';
}

function wishlistCtrl($conn) {
    $items = getWishlist($conn, $_SESSION['user']['id']);
    require 'views/user/wishlist.php';
}

function browseCtrl($conn) {
    $posts = getApprovedPosts($conn);
    $countries = getDistinctCountries($conn);
    require 'views/user/browse.php';
}

function postDetailCtrl($conn) {
    $id = intval($_GET['id'] ?? 0);
    $post = getApprovedPost($conn, $id);

    if (!$post) {
        redirect('index.php?page=browse');
    }

    $comments = getComments($conn, $id);
    $costEstimate = getCostEstimate($conn, $id);

    if (!$costEstimate) {
        $baseCost = getBaseCostByLevel($post['cost_level']);
        $currency = 'USD';
    } else {
        $baseCost = (float)$costEstimate['base_cost'];
        $currency = $costEstimate['currency'];
    }

    require 'views/user/post_detail.php';
}

/* 
   SCOUT CONTROLLERS
 */

function scoutDashboardCtrl($conn) {
    $counts = getScoutRequestCounts($conn, $_SESSION['user']['id']);
    require 'views/scout/scout_dashboard.php';
}

function createRequestCtrl($conn) {
    $error = '';
    $prefill = null;
    $originalPostId = intval($_GET['change_for'] ?? 0);

    if ($originalPostId > 0) {
        $post = getApprovedPost($conn, $originalPostId);

        if ($post && $post['scout_id'] == $_SESSION['user']['id']) {
            $prefill = $post;
        } else {
            $originalPostId = 0;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid request. Please refresh and try again.';
        } else {
            $title = cleanInput($_POST['title'] ?? '');
            $shortHistory = cleanInput($_POST['short_history'] ?? '');
            $countryRepresentation = cleanInput($_POST['country_representation'] ?? '');
            $country = cleanInput($_POST['country'] ?? '');
            $genre = cleanInput($_POST['genre'] ?? '');
            $costLevel = cleanInput($_POST['cost_level'] ?? '');
            $travelMediumInfo = cleanInput($_POST['travel_medium_info'] ?? '');
            $originalPostId = intval($_POST['original_post_id'] ?? 0);

            $allowedGenres = ['beach', 'mountain', 'city', 'historical', 'adventure', 'cultural', 'nature'];
            $allowedCosts = ['low', 'medium', 'high'];

            if ($originalPostId > 0) {
                $oldPost = getApprovedPost($conn, $originalPostId);

                if (!$oldPost || $oldPost['scout_id'] != $_SESSION['user']['id']) {
                    $error = 'Invalid change request.';
                }
            }

            if ($error === '') {
                if ($title === '' || $shortHistory === '' || $country === '' || $genre === '' || $costLevel === '' || $travelMediumInfo === '') {
                    $error = 'Please fill in all required fields.';
                } elseif (!in_array($genre, $allowedGenres)) {
                    $error = 'Invalid genre selected.';
                } elseif (!in_array($costLevel, $allowedCosts)) {
                    $error = 'Invalid cost level selected.';
                }
            }

            if ($error === '') {
                $imagePath = '';

                if (!empty($_FILES['image']['name'])) {
                    $uploadError = '';
                    $uploaded = uploadImage($_FILES['image'], POST_UPLOAD_DIR, POST_UPLOAD_WEB, $uploadError);

                    if ($uploaded === false) {
                        $error = $uploadError;
                    } else {
                        $imagePath = $uploaded;
                    }
                }

                if ($error === '') {
                    $postData = [
                        'title' => $title,
                        'short_history' => $shortHistory,
                        'country_representation' => $countryRepresentation,
                        'country' => $country,
                        'genre' => $genre,
                        'cost_level' => $costLevel,
                        'travel_medium_info' => $travelMediumInfo,
                        'image' => $imagePath
                    ];

                    $ok = createPostRequest(
                        $conn,
                        $_SESSION['user']['id'],
                        $postData,
                        $originalPostId > 0 ? $originalPostId : null
                    );

                    if ($ok) {
                        setFlash('success', 'Post request submitted successfully.');
                        redirect('index.php?page=my_requests');
                    }

                    $error = 'Request submission failed.';
                }
            }
        }
    }

    require 'views/scout/create_request.php';
}

function myRequestsCtrl($conn) {
    $requests = getScoutRequests($conn, $_SESSION['user']['id']);
    $flash = getFlash();
    require 'views/scout/my_requests.php';
}

function editRequestCtrl($conn) {
    $id = intval($_GET['id'] ?? 0);
    $request = getScoutRequestById($conn, $id, $_SESSION['user']['id']);

    if (!$request || $request['status'] !== 'pending') {
        redirect('index.php?page=my_requests');
    }

    $data = json_decode($request['post_data'], true);

    if (!is_array($data)) {
        $data = [];
    }

    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid request. Please refresh and try again.';
        } else {
            $title = cleanInput($_POST['title'] ?? '');
            $shortHistory = cleanInput($_POST['short_history'] ?? '');
            $countryRepresentation = cleanInput($_POST['country_representation'] ?? '');
            $country = cleanInput($_POST['country'] ?? '');
            $genre = cleanInput($_POST['genre'] ?? '');
            $costLevel = cleanInput($_POST['cost_level'] ?? '');
            $travelMediumInfo = cleanInput($_POST['travel_medium_info'] ?? '');

            $allowedGenres = ['beach', 'mountain', 'city', 'historical', 'adventure', 'cultural', 'nature'];
            $allowedCosts = ['low', 'medium', 'high'];

            if ($title === '' || $shortHistory === '' || $country === '' || $genre === '' || $costLevel === '' || $travelMediumInfo === '') {
                $error = 'Please fill in all required fields.';
            } elseif (!in_array($genre, $allowedGenres) || !in_array($costLevel, $allowedCosts)) {
                $error = 'Invalid genre or cost level.';
            } else {
                $imagePath = $data['image'] ?? '';

                if (!empty($_FILES['image']['name'])) {
                    $uploadError = '';
                    $uploaded = uploadImage($_FILES['image'], POST_UPLOAD_DIR, POST_UPLOAD_WEB, $uploadError);

                    if ($uploaded === false) {
                        $error = $uploadError;
                    } else {
                        $imagePath = $uploaded;
                    }
                }

                if ($error === '') {
                    $newData = [
                        'title' => $title,
                        'short_history' => $shortHistory,
                        'country_representation' => $countryRepresentation,
                        'country' => $country,
                        'genre' => $genre,
                        'cost_level' => $costLevel,
                        'travel_medium_info' => $travelMediumInfo,
                        'image' => $imagePath
                    ];

                    $ok = updatePostRequest($conn, $id, $_SESSION['user']['id'], $newData);

                    if ($ok) {
                        setFlash('success', 'Request updated successfully.');
                        redirect('index.php?page=my_requests');
                    }

                    $error = 'Request update failed.';
                }
            }
        }
    }

    require 'views/scout/edit_request.php';
}

function approvedPostsCtrl($conn) {
    $posts = getScoutApprovedPosts($conn, $_SESSION['user']['id']);
    require 'views/scout/approved_posts.php';
}

/* 
   ADMIN CONTROLLERS
 */

function adminDashboardCtrl($conn) {
    $counts = getAdminDashboardCounts($conn);
    require 'views/admin/admin_dashboard.php';
}

function usersCtrl($conn) {
    $error = '';
    $success = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid request. Please refresh and try again.';
        } else {
            $name = cleanInput($_POST['name'] ?? '');
            $email = strtolower(cleanInput($_POST['email'] ?? ''));
            $password = $_POST['password'] ?? '';
            $role = cleanInput($_POST['role'] ?? 'user');
            $isVerified = intval($_POST['is_verified'] ?? 0);

            $allowedRoles = ['admin', 'scout', 'user'];

            if ($name === '' || $email === '' || $password === '') {
                $error = 'Name, email, and password are required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Invalid email format.';
            } elseif (emailExists($conn, $email)) {
                $error = 'Email already exists.';
            } elseif (strlen($password) < 8) {
                $error = 'Password must be at least 8 characters.';
            } elseif (!in_array($role, $allowedRoles)) {
                $error = 'Invalid role selected.';
            } else {
                $ok = addUserByAdmin($conn, $name, $email, $password, $role, $isVerified);
                $success = $ok ? 'User added successfully.' : 'User creation failed.';
            }
        }
    }

    $users = getUsers($conn);
    require 'views/admin/users.php';
}

function postModerationCtrl($conn) {
    $error = '';
    $success = '';
    $editPost = null;

    if (isset($_GET['edit'])) {
        $editPost = getPostById($conn, intval($_GET['edit']));
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid request. Please refresh and try again.';
        } else {
            $action = $_POST['action'] ?? '';

            if ($action === 'update_post') {
                $id = intval($_POST['id'] ?? 0);
                $title = cleanInput($_POST['title'] ?? '');
                $shortHistory = cleanInput($_POST['short_history'] ?? '');
                $country = cleanInput($_POST['country'] ?? '');
                $genre = cleanInput($_POST['genre'] ?? '');
                $costLevel = cleanInput($_POST['cost_level'] ?? '');
                $travelMediumInfo = cleanInput($_POST['travel_medium_info'] ?? '');

                $allowedCosts = ['low', 'medium', 'high'];

                if ($title === '' || $shortHistory === '' || $country === '' || $genre === '' || $travelMediumInfo === '') {
                    $error = 'Please fill in all post fields.';
                    $editPost = getPostById($conn, $id);
                } elseif (!in_array($costLevel, $allowedCosts)) {
                    $error = 'Invalid cost level.';
                    $editPost = getPostById($conn, $id);
                } else {
                    $ok = updatePost($conn, $id, $title, $shortHistory, $country, $genre, $costLevel, $travelMediumInfo);
                    $success = $ok ? 'Post updated successfully.' : 'Post update failed.';
                    $editPost = null;
                }
            }
        }
    }

    $pendingRequests = getPendingPostRequests($conn);
    $posts = getAllPosts($conn);

    require 'views/admin/post_moderation.php';
}

function commentModerationCtrl($conn) {
    $comments = getAllComments($conn);
    require 'views/admin/comment_moderation.php';
}

/* 
   AJAX CONTROLLER
 */
function ajaxCtrl($conn) {
    header('Content-Type: application/json');

    $type = $_GET['type'] ?? '';
    $body = requestBodyData();

    // Public AJAX: email check
    if ($type === 'check_email') {
        $email = strtolower(cleanInput($_GET['email'] ?? ''));

        echo json_encode([
            'exists' => emailExists($conn, $email)
        ]);
        exit;
    }

    // Search posts: verified users can browse/search
    if ($type === 'search_posts') {
        if (!isLoggedIn() || !isVerified()) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $q = cleanInput($_GET['q'] ?? '');
        $country = cleanInput($_GET['country'] ?? '');
        $genre = cleanInput($_GET['genre'] ?? '');
        $costLevel = cleanInput($_GET['cost_level'] ?? '');

        echo json_encode(searchApprovedPosts($conn, $q, $country, $genre, $costLevel));
        exit;
    }

    // After this point, login is required
    if (!isLoggedIn()) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    // CSRF check for write actions
    $writeActions = [
        'wishlist_add',
        'wishlist_remove',
        'delete_request',
        'edit_request_ajax',
        'verify_toggle',
        'approve_request',
        'reject_request',
        'delete_user',
        'delete_post',
        'delete_comment_admin',
        'add_comment',
        'delete_comment',
        'calculate_cost'
    ];

    if (in_array($type, $writeActions)) {
        if (!verifyCsrf($body['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }
    }

    // Wishlist Add - POST
    if ($type === 'wishlist_add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isGeneralUser()) {
            http_response_code(403);
            echo json_encode(['error' => 'Only verified general users can use wishlist.']);
            exit;
        }

        $postId = intval($body['post_id'] ?? 0);
        $ok = $postId > 0 ? addToWishlist($conn, $_SESSION['user']['id'], $postId) : false;

        echo json_encode(['success' => $ok]);
        exit;
    }

    // Wishlist Remove - DELETE
    if ($type === 'wishlist_remove' && $_SERVER['REQUEST_METHOD'] === 'DELETE') {
        if (!isGeneralUser()) {
            http_response_code(403);
            echo json_encode(['error' => 'Only verified general users can use wishlist.']);
            exit;
        }

        $postId = intval($body['post_id'] ?? 0);
        $ok = $postId > 0 ? removeFromWishlist($conn, $_SESSION['user']['id'], $postId) : false;

        echo json_encode(['success' => $ok]);
        exit;
    }

    // Scout Delete Request - DELETE
    if ($type === 'delete_request' && $_SERVER['REQUEST_METHOD'] === 'DELETE') {
        if (!isScout()) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $id = intval($body['id'] ?? 0);
        $ok = $id > 0 ? deletePostRequest($conn, $id, $_SESSION['user']['id']) : false;

        echo json_encode(['success' => $ok]);
        exit;
    }

    // Scout Edit Request - AJAX POST
    if ($type === 'edit_request_ajax' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isScout()) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $id = intval($body['id'] ?? 0);
        $request = getScoutRequestById($conn, $id, $_SESSION['user']['id']);

        if (!$request || $request['status'] !== 'pending') {
            echo json_encode(['error' => 'Only pending requests can be edited.']);
            exit;
        }

        $oldData = json_decode($request['post_data'], true);
        if (!is_array($oldData)) {
            $oldData = [];
        }

        $title = cleanInput($body['title'] ?? '');
        $shortHistory = cleanInput($body['short_history'] ?? '');
        $countryRepresentation = cleanInput($body['country_representation'] ?? '');
        $country = cleanInput($body['country'] ?? '');
        $genre = cleanInput($body['genre'] ?? '');
        $costLevel = cleanInput($body['cost_level'] ?? '');
        $travelMediumInfo = cleanInput($body['travel_medium_info'] ?? '');

        $allowedGenres = ['beach', 'mountain', 'city', 'historical', 'adventure', 'cultural', 'nature'];
        $allowedCosts = ['low', 'medium', 'high'];

        $errors = [];

        if ($title === '') {
            $errors['title'] = 'Title is required.';
        }

        if ($shortHistory === '') {
            $errors['short_history'] = 'Short history is required.';
        }

        if ($country === '') {
            $errors['country'] = 'Country is required.';
        }

        if (!in_array($genre, $allowedGenres)) {
            $errors['genre'] = 'Invalid genre selected.';
        }

        if (!in_array($costLevel, $allowedCosts)) {
            $errors['cost_level'] = 'Invalid cost level selected.';
        }

        if ($travelMediumInfo === '') {
            $errors['travel_medium_info'] = 'Travel medium information is required.';
        }

        if (!empty($errors)) {
            echo json_encode([
                'success' => false,
                'errors' => $errors
            ]);
            exit;
        }

        $imagePath = $oldData['image'] ?? '';

        if (!empty($_FILES['image']['name'])) {
            $uploadError = '';
            $uploaded = uploadImage($_FILES['image'], POST_UPLOAD_DIR, POST_UPLOAD_WEB, $uploadError);

            if ($uploaded === false) {
                echo json_encode([
                    'success' => false,
                    'errors' => [
                        'image' => $uploadError
                    ]
                ]);
                exit;
            }

            $imagePath = $uploaded;
        }

        $newData = [
            'title' => $title,
            'short_history' => $shortHistory,
            'country_representation' => $countryRepresentation,
            'country' => $country,
            'genre' => $genre,
            'cost_level' => $costLevel,
            'travel_medium_info' => $travelMediumInfo,
            'image' => $imagePath
        ];

        $ok = updatePostRequest($conn, $id, $_SESSION['user']['id'], $newData);

        echo json_encode([
            'success' => $ok,
            'redirect' => 'index.php?page=my_requests'
        ]);
        exit;
    }

    // Scout Search Requests - GET
    
    if ($type === 'search_requests') {
        if (!isScout()) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $q = cleanInput($_GET['q'] ?? '');

        $rows = $q === ''
            ? getScoutRequests($conn, $_SESSION['user']['id'])
            : searchScoutRequests($conn, $_SESSION['user']['id'], $q);

        $result = [];

        foreach ($rows as $row) {
            $data = json_decode($row['post_data'], true);

            if (!is_array($data)) {
                $data = [];
            }

            $data['id'] = $row['id'];
            $data['status'] = $row['status'];
            $data['requested_at'] = $row['requested_at'];
            $data['original_post_id'] = $row['original_post_id'];

            $result[] = $data;
        }

        echo json_encode($result);
        exit;
    }

    // Admin Search Users - GET
    if ($type === 'search_users') {
        if (!isAdmin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $q = cleanInput($_GET['q'] ?? '');

        echo json_encode($q === '' ? getUsers($conn) : searchUsers($conn, $q));
        exit;
    }

    // Admin Search Comments - GET
    if ($type === 'search_comments') {
        if (!isAdmin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $q = cleanInput($_GET['q'] ?? '');

        echo json_encode($q === '' ? getAllComments($conn) : searchComments($conn, $q));
        exit;
    }

    
    // Admin Verify Toggle - POST
    
    if ($type === 'verify_toggle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isAdmin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $id = intval($body['id'] ?? 0);
        $isVerified = intval($body['is_verified'] ?? 0);

        if ($id === $_SESSION['user']['id'] && $isVerified === 0) {
            echo json_encode([
                'error' => 'You cannot unverify your own admin account.'
            ]);
            exit;
        }

        $ok = $id > 0 ? toggleUserVerification($conn, $id, $isVerified) : false;

        echo json_encode(['success' => $ok]);
        exit;
    }

    // Admin Approve Request - POST
    if ($type === 'approve_request' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isAdmin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $id = intval($body['id'] ?? 0);
        $ok = $id > 0 ? approvePostRequest($conn, $id) : false;

        echo json_encode(['success' => $ok]);
        exit;
    }

    // Admin Reject Request - POST
    if ($type === 'reject_request' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isAdmin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $id = intval($body['id'] ?? 0);
        $ok = $id > 0 ? rejectPostRequest($conn, $id) : false;

        echo json_encode(['success' => $ok]);
        exit;
    }

    // Admin Delete User - POST
    if ($type === 'delete_user' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isAdmin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $id = intval($body['id'] ?? 0);

        if ($id === $_SESSION['user']['id']) {
            echo json_encode([
                'error' => 'You cannot delete your own account.'
            ]);
            exit;
        }

        $ok = $id > 0 ? deleteUser($conn, $id) : false;

        echo json_encode(['success' => $ok]);
        exit;
    }

    // Admin Delete Post - POST
    if ($type === 'delete_post' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isAdmin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $id = intval($body['id'] ?? 0);
        $ok = $id > 0 ? deletePost($conn, $id) : false;

        echo json_encode(['success' => $ok]);
        exit;
    }

    // Admin Delete Comment - POST
    if ($type === 'delete_comment_admin' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isAdmin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $id = intval($body['id'] ?? 0);
        $ok = $id > 0 ? deleteCommentAdmin($conn, $id) : false;

        echo json_encode(['success' => $ok]);
        exit;
    }

    
    // Add Comment - AJAX POST
    if ($type === 'add_comment' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isGeneralUser()) {
            http_response_code(403);
            echo json_encode(['error' => 'Only verified general users can comment.']);
            exit;
        }

        $postId = intval($body['post_id'] ?? 0);
        $content = cleanInput($body['content'] ?? '');

        if ($postId <= 0) {
            echo json_encode(['error' => 'Invalid post.']);
            exit;
        }

        if ($content === '') {
            echo json_encode([
                'success' => false,
                'errors' => [
                    'content' => 'Comment cannot be empty.'
                ]
            ]);
            exit;
        }

        if (strlen($content) > 1000) {
            echo json_encode([
                'success' => false,
                'errors' => [
                    'content' => 'Comment must be within 1000 characters.'
                ]
            ]);
            exit;
        }

        $commentId = addComment($conn, $postId, $_SESSION['user']['id'], $content);

        if (!$commentId) {
            echo json_encode(['error' => 'Comment submission failed.']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'comment_id' => $commentId,
            'user_name' => $_SESSION['user']['name'],
            'content' => $content,
            'created_at' => date('M d, Y h:i A')
        ]);
        exit;
    }

    // Delete Own Comment - DELETE
    //DELETE support korbe ebar
    //AJAX comment instant support korbe
    //AJAX scout edit endpoint ebar banabo
    //JSON inline errors support korbe AJAX endpoints ebar

    if ($type === 'delete_comment' && $_SERVER['REQUEST_METHOD'] === 'DELETE') {
        if (!isGeneralUser()) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $id = intval($body['id'] ?? 0);
        $ok = $id > 0 ? deleteOwnComment($conn, $id, $_SESSION['user']['id']) : false;

        echo json_encode(['success' => $ok]);
        exit;
    }

    // Cost Calculator - POST
    if ($type === 'calculate_cost' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isGeneralUser()) {
            http_response_code(403);
            echo json_encode(['error' => 'Only verified general users can calculate trip cost.']);
            exit;
        }

        $postId = intval($body['post_id'] ?? 0);
        $travelers = intval($body['travelers'] ?? 0);
        $days = intval($body['days'] ?? 0);

        $errors = [];

        if ($postId <= 0) {
            $errors['post_id'] = 'Invalid post.';
        }

        if ($travelers < 1 || $travelers > 10) {
            $errors['travelers'] = 'Travelers must be between 1 and 10.';
        }

        if ($days < 1 || $days > 90) {
            $errors['days'] = 'Days must be between 1 and 90.';
        }

        if (!empty($errors)) {
            echo json_encode([
                'success' => false,
                'errors' => $errors
            ]);
            exit;
        }

        $estimate = calculateEstimatedCost($conn, $postId, $travelers, $days);

        if (!$estimate) {
            echo json_encode(['error' => 'Cost calculation failed.']);
            exit;
        }

        echo json_encode(['success' => true] + $estimate);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'Invalid AJAX request']);
    exit;
}



?>