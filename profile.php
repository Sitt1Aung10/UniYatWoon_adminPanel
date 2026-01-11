<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

ini_set('session.cookie_path', '/');
session_start();

if (isset($_GET['Username'])) {
    $username = $_GET['Username'];
} elseif (isset($_SESSION['Username'])) {
    $username = $_SESSION['Username'];
} else {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}
$isOwnProfile = isset($_SESSION['Username']) 
    && $_SESSION['Username'] === $username;


if (!$username) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}


$sql = "SELECT 
        p.id,
        p.Username,
        p.Description,
        p.Created_at,
        p.Updated_at,
        u.Profile_photo,
        u.Major
    FROM posts p
    LEFT JOIN users u ON p.Username = u.Username
    WHERE p.Username = ?
    ORDER BY p.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$username]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Attach media to each post
foreach ($posts as &$post) {
    $media_sql = "SELECT Media_url, Media_type FROM posts_media WHERE Post_id = ?";
    $media_stmt = $pdo->prepare($media_sql);
    $media_stmt->execute([$post['id']]);
    $post['media'] = $media_stmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($post);

echo json_encode([
    "isOwnProfile" => $isOwnProfile,
    "posts" => $posts
]);

