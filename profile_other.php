<?php
require_once 'cors.php';
require_once 'auth.php';
require_once 'db_connect.php';

/* =========================
   Validate input
   ========================= */
$profile_uuid = $_GET['user_uuid'] ?? null;

if (!$profile_uuid) {
    echo json_encode([
        "success" => false,
        "message" => "Missing user_uuid"
    ]);
    exit;
}

/* =========================
   Fetch user info
   ========================= */
$user_stmt = $pdo->prepare(
    "SELECT user_uuid, Username, Profile_photo, Major
     FROM users
     WHERE user_uuid = ?"
);
$user_stmt->execute([$profile_uuid]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode([
        "success" => false,
        "message" => "User not found"
    ]);
    exit;
}

/* =========================
   Fetch posts (if any)
   ========================= */
$post_stmt = $pdo->prepare(
    "SELECT id, Description, like_count, comment_count, Created_at, Updated_at
     FROM posts
     WHERE user_uuid = ?
     ORDER BY id DESC"
);
$post_stmt->execute([$profile_uuid]);
$posts = $post_stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   Attach media
   ========================= */
foreach ($posts as &$post) {
    $media_stmt = $pdo->prepare(
        "SELECT Media_url, Media_type 
         FROM posts_media 
         WHERE Post_id = ?"
    );
    $media_stmt->execute([$post['id']]);
    $post['media'] = $media_stmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($post);

/* =========================
   Check follow status
   ========================= */
$current_user_uuid = $_GET['current_user_uuid'] ?? null; // Pass from frontend
$is_following = false;

if ($current_user_uuid) {
    $follow_check = $pdo->prepare(
        "SELECT 1 FROM follows WHERE follower_uuid = ? AND following_uuid = ? LIMIT 1"
    );
    $follow_check->execute([$current_user_uuid, $profile_uuid]);
    $is_following = (bool)$follow_check->fetch();
}

/* =========================
   Response
   ========================= */
echo json_encode([
    "success" => true,
    "isOwnProfile" => false,
    "isFollowing" => $is_following,
    "user" => $user,
    "posts" => $posts
]);
exit;
