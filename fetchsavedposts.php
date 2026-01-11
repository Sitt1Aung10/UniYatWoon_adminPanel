<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With, Accept");
ini_set('session.cookie_path' , '/');
session_start();

include 'db_connect.php';

// Get current user ID if logged in
$username = $_SESSION['Username'] ?? null;
$user_id = null;

if ($username) {
    $find_user_id = "SELECT id FROM users WHERE username = ?";
    $userStmt = $pdo->prepare($find_user_id);
    $userStmt->execute([$username]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    $user_id = $user['id'] ?? null;
}

$sql = 'SELECT 
s.id AS id,
s.user_id,
s.post_id,


p.Username,
p.Description,

pm.id AS media_id,
pm.Media_url,
pm.Media_type

FROM saved_posts s
LEFT JOIN posts p 
  ON s.post_id = p.id
LEFT JOIN posts_media pm 
  ON pm.post_id = p.id

ORDER BY s.id DESC
';


$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$saved_posts = [];

foreach ($rows as $row) {
    $sid = $row['id'];

    if (!isset($saved_posts[$sid])) {
        $saved_posts[$sid] = [
            'id' => $row['id'],
            'user_id' => $row['user_id'],
            'post_id' => $row['post_id'],
            'Username' => $row['Username'],
            'Description' => $row['Description'],
            'media' => []   // ✅ THIS is what React needs
        ];
        
        // Get like count for this post
        $like_count_sql = 'SELECT COUNT(*) as like_count FROM post_likes WHERE post_id = ?';
        $like_count_stmt = $pdo->prepare($like_count_sql);
        $like_count_stmt->execute([$row['post_id']]);
        $like_count_data = $like_count_stmt->fetch(PDO::FETCH_ASSOC);
        $saved_posts[$sid]['like_count'] = (int)($like_count_data['like_count'] ?? 0);
        
        // Check if current user has liked this post
        $saved_posts[$sid]['is_liked'] = false;
        if ($user_id) {
            $check_like_sql = 'SELECT id FROM post_likes WHERE user_id = ? AND post_id = ?';
            $check_like_stmt = $pdo->prepare($check_like_sql);
            $check_like_stmt->execute([$user_id, $row['post_id']]);
            $saved_posts[$sid]['is_liked'] = $check_like_stmt->fetch(PDO::FETCH_ASSOC) !== false;
        }
    }

    if (!empty($row['media_id'])) {
        $saved_posts[$sid]['media'][] = [
            'Media_url' => $row['Media_url'],
            'Media_type' => $row['Media_type']
        ];
    }
}

echo json_encode(array_values($saved_posts), JSON_UNESCAPED_SLASHES);