<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With, Accept");
session_start();

include 'db_connect.php';

$sql = 'SELECT p.id,p.Username,p.Description, p.Created_at,p.Updated_at, u.Profile_photo FROM posts p
LEFT JOIN users u ON p.User_uuid = u.user_uuid
ORDER BY p.id DESC
';

$stmt = $pdo->query($sql);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Attach media to each post
foreach ($posts as &$post) {
    $media_sql = 'SELECT Media_url, Media_type FROM posts_media WHERE Post_id = ?';
    $media_stmt = $pdo->prepare($media_sql);
    $media_stmt->execute([$post['id']]);
    $post['media'] = $media_stmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($post);

echo json_encode($posts, JSON_UNESCAPED_SLASHES);
