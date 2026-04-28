<?php
require_once 'cors.php';
require_once 'auth.php';       // JWT → $user_uuid
require_once 'db_connect.php';

/* =========================
   1️⃣ NEW: Fetch Full User Data (ADD THIS)
   ========================= */
$user_sql = "SELECT Username, Phone, Year, Location, Major, Profile_photo 
             FROM users WHERE user_uuid = ? LIMIT 1";
$user_stmt = $pdo->prepare($user_sql);
$user_stmt->execute([$user_uuid]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

/* =========================
   2️⃣ Fetch own posts (STAYS SIMILAR)
   ========================= */
$sql = "SELECT id, user_uuid , Description, like_count , comment_count, Created_at, Updated_at 
        FROM posts WHERE user_uuid = ? ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_uuid]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   3️⃣ Attach media & user details to posts
   ========================= */
foreach ($posts as &$post) {
    // Attach media
    $media_stmt = $pdo->prepare("SELECT Media_url, Media_type FROM posts_media WHERE Post_id = ?");
    $media_stmt->execute([$post['id']]);
    $post['media'] = $media_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Attach user info so UI (PostCard) displays the avatar correctly
    $post['Username'] = $user['Username'] ?? 'User';
    $post['Profile_photo'] = $user['Profile_photo'] ?? 'default.png';
    $post['Major'] = $user['Major'] ?? 'Student';
}
unset($post);

$follower_count = $pdo->prepare(
    "SELECT COUNT(*) FROM follows WHERE following_uuid = ?"
);
$follower_count->execute([$user_uuid]);
$follower_count = $follower_count->fetchColumn();

$following_count = $pdo->prepare(
    "SELECT COUNT(*) FROM follows WHERE follower_uuid = ?"
);
$following_count->execute([$user_uuid]);
$following_count = $following_count->fetchColumn();



/* =========================
   4️⃣ Response (UPDATE THIS)
   ========================= */
echo json_encode([
    "success" => true,
    "isOwnProfile" => true,
    "user" => $user, // <--- This provides the data for your Edit Profile screen
    "posts" => $posts
]);
   exit;
