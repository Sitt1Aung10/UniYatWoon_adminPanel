<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

include 'db_connect.php';

/* READ JSON BODY */
$data = json_decode(file_get_contents("php://input"), true);

/* DEBUG (temporary) */
// echo json_encode($data); exit;

$Reported_post_id = isset($data['Reported_post_id'])
    ? (int)$data['Reported_post_id']
    : 0;

if ($Reported_post_id <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid Post ID",
        "received" => $data
    ]);
    exit;
}

$stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
$stmt->execute([$Reported_post_id]);

echo json_encode([
    "success" => true,
    "message" => "Post deleted successfully",
    "deleted_id" => $Reported_post_id
]);

$stmt = $pdo->prepare("DELETE FROM report_posts WHERE Reported_post_id = ?");
$stmt->execute([$Reported_post_id]);
exit;
