<?php
require_once 'cors.php';
require_once 'db_connect.php';
require_once 'auth.php'; // <-- your JWT verification file

$raw = file_get_contents("php://input");
$input = json_decode($raw, true);

$post_id = $input['post_id'] ?? null;

if (!$post_id || (int)$post_id <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid Post ID",
        "received" => $input
    ]);
    exit;
}

$post_id = (int)$post_id;


if ($post_id <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid Post ID",
        "received" => $_POST
    ]);
    exit;
}

/* FETCH POST OWNER */
$stmt = $pdo->prepare("SELECT user_uuid FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    echo json_encode([
        "success" => false,
        "message" => "Post not found"
    ]);
    exit;
}

/* CHECK PERMISSIONS: owner or admin */
if ($post['user_uuid'] !== $user_uuid && !$is_admin) {
    echo json_encode([
        "success" => false,
        "message" => "You are not allowed to delete this post"
    ]);
    exit;
}

/* DELETE POST */
$stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
$stmt->execute([$post_id]);

/* DELETE REPORTS */
$stmt = $pdo->prepare("DELETE FROM report_posts WHERE Reported_post_id = ?");
$stmt->execute([$Reported_post_id]);

echo json_encode([
    "success" => true,
    "message" => "Post deleted successfully",
    "deleted_id" => $post_id
]);
exit;
