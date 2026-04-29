<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'cors.php';
require_once 'auth.php';
require_once 'db_connect.php';

header('Content-Type: application/json');

/* =========================
   Accept multiple param styles
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

        $sql = "
            SELECT 
                u.user_uuid,
                u.Username,
                u.Major,
                u.Profile_photo
            FROM follows f
            JOIN users u 
                ON f.following_uuid = u.user_uuid
            WHERE f.follower_uuid = ?
            ORDER BY u.Username ASC
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
            WHERE f.following_uuid = ?
            ORDER BY u.Username ASC
        ";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->bind_param("s", $user_uuid);
    $stmt->execute();

    $result = $stmt->get_result();

    $list = [];

    while ($row = $result->fetch_assoc()) {
        $list[] = [
            "user_uuid" => $row["user_uuid"],
            "Username" => $row["Username"],
            "Major" => $row["Major"],
            "Profile_photo" => $row["Profile_photo"]
        ];
    }

    echo json_encode([
        "success" => true,
        "list" => $list,
        "followers" => $type === "followers" ? $list : [],
        "following" => $type === "following" ? $list : []
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "message" => "Database error",
        "error" => $e->getMessage()
    ]);

}