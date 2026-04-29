<?php
require_once 'cors.php';
require_once 'auth.php';
require_once 'db_connect.php';

header('Content-Type: application/json');

/* =========================
   Get parameters
========================= */

$user_uuid = $_GET['user_uuid'] ?? $_POST['user_uuid'] ?? null;
$type = $_GET['type'] ?? $_POST['type'] ?? 'followers';

if (!$user_uuid) {
    echo json_encode([
        "success" => false,
        "message" => "Missing user_uuid"
    ]);
    exit;
}

/* =========================
   Query based on type
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
            WHERE f.follower_uuid = ?
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
            WHERE f.following_uuid = ?
            ORDER BY u.Username ASC
        ";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $user_uuid);
    $stmt->execute();

    $result = $stmt->get_result();

    $users = [];

    while ($row = $result->fetch_assoc()) {
        $users[] = [
            "user_uuid" => $row["user_uuid"],
            "Username" => $row["Username"],
            "Major" => $row["Major"],
            "Profile_photo" => $row["Profile_photo"]
        ];
    }

    echo json_encode([
        "success" => true,
        "type" => $type,
        "users" => $users
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "message" => "Server error",
        "error" => $e->getMessage()
    ]);

}