<?php
require_once 'cors.php';
require_once 'db_connect.php';
require_once 'auth.php'; 

$post_id = $_GET['post_id'] ?? null;
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

if (!$post_id) {
    echo json_encode(["success" => false, "message" => "post_id required"]);
    exit;
}

// Use prepare without execute parameters yet
$stmt = $pdo->prepare("SELECT 
        u.user_uuid,
        u.Username,
        u.Profile_photo,
        l.created_at
    FROM likes l
    JOIN users u ON u.user_uuid = l.user_uuid
    WHERE l.post_id = :post_id
    ORDER BY l.created_at DESC
    LIMIT :limit OFFSET :offset
");

// Bind values specifically to ensure they are INT for the LIMIT clause
$stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$likes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "data" => $likes
]);
exit;
