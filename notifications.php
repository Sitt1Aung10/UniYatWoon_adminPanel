<?php
require_once "cors.php";
require_once "db_connect.php";
require_once "auth.php"; // provides $user_uuid

$wsql = "SELECT
    n.id,
    n.user_uuid,
    n.type,
    n.message,
    n.created_at,
    p.id AS post_id,
    u.Username AS from_username,
    u.Profile_photo AS from_profile_photo
    FROM notifications n 
    LEFT JOIN posts p ON n.post_id = p.id
    LEFT JOIN users u ON n.from_uuid = u.user_uuid
    WHERE n.user_uuid = :current_user_uuid
    ORDER BY n.created_at DESC
    LIMIT 20";

$stmt = $pdo->prepare($wsql);
$stmt->bindValue(':current_user_uuid', $user_uuid);
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
/* =========================
   Response
   ========================= */
echo json_encode([
    "notifications" => $notifications
]);
exit;
