<?php
require_once "cors.php";
require_once "db_connect.php";
require_once "auth.php";

header('Content-Type: application/json');

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

$post_id = $data['post_id'] ?? null;
$comment = trim($data['comment_text'] ?? "");

if (!$post_id || $comment === "") {
    echo json_encode([
        "success" => false,
        "message" => "Invalid input"
    ]);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1️⃣ Insert comment
    $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_uuid, comment_text, Created_at)
        VALUES (:post_id, :user_uuid, :comment, NOW())
    ");

    $stmt->execute([
        ':post_id' => $post_id,
        ':user_uuid' => $user_uuid,
        ':comment' => $comment
    ]);

    // 2️⃣ Update post count (THIS IS THE FIX YOU WERE MISSING)
    $update = $pdo->prepare("UPDATE posts
        SET comment_count = comment_count + 1
        WHERE id = :post_id
    ");

    $update->execute([
        ':post_id' => $post_id
    ]);

    $pdo->commit();

    echo json_encode([
        "success" => true
    ]);

} catch (Exception $e) {
    $pdo->rollBack();

    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}