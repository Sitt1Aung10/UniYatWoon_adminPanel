<?php
require_once 'cors.php';
require_once 'auth.php';
require_once 'db_connect.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

$user_uuid = $_GET['user_uuid'] ?? null;
$type = $_GET['type'] ?? 'followers';

if (!$user_uuid) {
    echo json_encode([
        "success" => false,
        "message" => "Missing user_uuid"
    ]);
    exit;
}

try {

    if ($type === "following") {

        $sql = "
            SELECT 
                u.user_uuid,
                u.Username,
                u.Major,
                u.Profile_photo
            FROM follows f
            JOIN users u 
                ON f.following_uuid = u.user_uuid
            WHERE f.follower_uuid = :user_uuid
        ";

    } else {

        $sql = "
            SELECT 
                u.user_uuid,
                u.Username,
                u.Major,
                u.Profile_photo
            FROM follows f
            JOIN users u 
                ON f.follower_uuid = u.user_uuid
            WHERE f.following_uuid = :user_uuid
        ";
    }

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':user_uuid' => $user_uuid
    ]);

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "type" => $type,
        "users" => $users
    ]);

} catch (PDOException $e) {

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}