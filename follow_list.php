<?php
require_once 'cors.php';
require_once 'auth.php';
require_once 'db_connect.php'; // must return $pdo (PDO connection)

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

/* =========================
   Get input
========================= */

$input = json_decode(file_get_contents("php://input"), true);

$user_uuid =
    $_GET['user_uuid']
    ?? $_POST['user_uuid']
    ?? $input['user_uuid']
    ?? null;

$type =
    $_GET['type']
    ?? $_POST['type']
    ?? $input['type']
    ?? 'followers';

if (!$user_uuid) {
    echo json_encode([
        "success" => false,
        "message" => "Missing user_uuid"
    ]);
    exit;
}

/* =========================
   Build Query
========================= */

try {

    if ($type === "following") {

        // Users THIS USER follows
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
            ORDER BY u.Username ASC
        ";

    } else {

        // Users FOLLOWING this user
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
            ORDER BY u.Username ASC
        ";
    }

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':user_uuid' => $user_uuid
    ]);

    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "list" => $list,
        "followers" => $type === "followers" ? $list : [],
        "following" => $type === "following" ? $list : [],
        "users" => $list
    ]);

} catch (PDOException $e) {

    echo json_encode([
        "success" => false,
        "message" => "Database error",
        "error" => $e->getMessage()
    ]);
}