<?php
require_once "cors.php";
require_once "db_connect.php";
require_once "auth.php"; // Provides $user_uuid

$type = $_GET['type'] ?? 'all';
$search = $_GET['search'] ?? '';
$searchParam = "%$search%";

$results = [];

if ($type === 'users' || $type === 'all') {
    $user_sql = "SELECT Username, Email, Profile_photo, user_uuid, 'user' as result_type 
                 FROM users 
                 WHERE Username LIKE ? OR Email LIKE ?
                 LIMIT 20";
    $stmt = $pdo->prepare($user_sql);
    $stmt->execute([$searchParam, $searchParam]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $results = array_merge($results, $users);
}

if ($type !== 'users') {
    // Post mapping logic from fetchposts.php
    $post_type_filter = ($type === 'all') ? '' : " AND p.type = :post_type";
    
    $post_sql = "SELECT 
        p.id, p.User_uuid as user_uuid, p.Username, p.Description, p.Created_at, p.type,
        u.Profile_photo, 'post' as result_type
        FROM posts p
        LEFT JOIN users u ON u.user_uuid = p.User_uuid
        WHERE (p.Description LIKE :search OR p.Username LIKE :search)" . $post_type_filter . "
        ORDER BY p.Created_at DESC
        LIMIT 20";

    $stmt = $pdo->prepare($post_sql);
    $stmt->bindValue(':search', $searchParam);
    if ($type !== 'all') {
        $stmt->bindValue(':post_type', $type);
    }
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Attach media
    $postIds = array_column($posts, 'id');
    if (!empty($postIds)) {
        $placeholders = implode(',', array_fill(0, count($postIds), '?'));
        $media_stmt = $pdo->prepare("SELECT Post_id, Media_url, Media_type FROM posts_media WHERE Post_id IN ($placeholders)");
        $media_stmt->execute($postIds);
        $mediaRows = $media_stmt->fetchAll(PDO::FETCH_ASSOC);

        $mediaMap = [];
        foreach ($mediaRows as $row) {
            $mediaMap[$row['Post_id']][] = $row;
        }

        foreach ($posts as &$post) {
            $post['media'] = $mediaMap[$post['id']] ?? [];
        }
    }
    $results = array_merge($results, $posts);
}

echo json_encode($results, JSON_UNESCAPED_SLASHES);

