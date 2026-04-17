<?php
require_once "cors.php";
require_once "db_connect.php";
require_once "auth.php"; // gives $user_uuid

$input = json_decode(file_get_contents("php://input"), true);
$target_uuid = $input['user_uuid'] ?? null;

if (!$target_uuid || $target_uuid === $user_uuid) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid user"
    ]);
    exit;
}

// prevent duplicate follow
$stmt = $pdo->prepare(
    "SELECT id FROM follows WHERE follower_uuid = ? AND following_uuid = ?"
);
$stmt->execute([$user_uuid, $target_uuid]);

$follow = $stmt->fetch();

if ($follow) {
    // Already following -> UNFOLLOW
    $delete = $pdo->prepare("DELETE FROM follows WHERE id = ?");
    $delete->execute([$follow['id']]);
    $action = "unfollowed";
} else {
    // Not following -> FOLLOW
    $insert = $pdo->prepare("INSERT INTO follows (follower_uuid, following_uuid) VALUES (?, ?)");
    $insert->execute([$user_uuid, $target_uuid]);
    $action = "followed";

    // Insert notification for the followed user
    // fetch follower username
    $stmtUser = $pdo->prepare("SELECT Username FROM users WHERE user_uuid = ? LIMIT 1");
    $stmtUser->execute([$user_uuid]);
    $follower = $stmtUser->fetch(PDO::FETCH_ASSOC);
    $followerName = $follower['Username'] ?? 'Someone';

    $message = "$followerName started following you";

    $stmt = $pdo->prepare(
        "INSERT INTO notifications (user_uuid, from_uuid, post_id, type, message) VALUES (?, ?, NULL, 'follow', ?)"
    );
    $stmt->execute([$target_uuid, $user_uuid, $message]);
}

echo json_encode([
    "success" => true,
    "action" => $action,
    "message" => ($action === "followed" ? "Followed" : "Unfollowed")
]);
exit;

