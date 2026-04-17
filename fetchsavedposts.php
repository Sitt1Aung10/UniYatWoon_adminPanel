<?php
require_once "cors.php";
require_once 'db_connect.php';
require_once 'auth.php'; // handles JWT

// ========== Fetch saved posts for current user ==========
$sql = 'SELECT 
s.id AS id,
s.user_uuid AS saver_uuid,
s.post_id,
p.Username,
p.Description,
p.user_uuid AS post_user_uuid,
pm.id AS media_id,
pm.Media_url,
pm.Media_type
FROM savedposts s
LEFT JOIN posts p ON s.post_id = p.id
LEFT JOIN posts_media pm ON pm.post_id = p.id
WHERE s.user_uuid = ?
ORDER BY s.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_uuid]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$saved_posts = [];

foreach ($rows as $row) {
    $sid = $row['id'];

    if (!isset($saved_posts[$sid])) {
        $saved_posts[$sid] = [
            'id' => $row['id'],
            'saver_uuid' => $row['saver_uuid'],
            'post_id' => $row['post_id'],
            'Username' => $row['Username'],
            'Description' => $row['Description'],
            'media' => []
        ];

        // Like count
        $like_count_stmt = $pdo->prepare('SELECT COUNT(*) as like_count FROM likes WHERE post_id = ?');
        $like_count_stmt->execute([$row['post_id']]);
        $saved_posts[$sid]['like_count'] = (int)($like_count_stmt->fetch(PDO::FETCH_ASSOC)['like_count'] ?? 0);

        // Has current user liked?
        $saved_posts[$sid]['is_liked'] = false;
        if (!empty($user_uuid)) {
            $check_like_stmt = $pdo->prepare('SELECT id FROM likes WHERE user_uuid = ? AND post_id = ? LIMIT 1');
            $check_like_stmt->execute([$user_uuid, $row['post_id']]);
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
