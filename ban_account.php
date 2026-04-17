<?php
require_once 'cors.php';
require_once 'db_connect.php';
require_once 'auth.php'; // provides $user_uuid

$data = json_decode(file_get_contents("php://input"), true);
$user_uuid = $data['user_uuid'] ?? '';

if (!$user_uuid) {
    echo json_encode(["success" => false, "message" => "User UUID missing"]);
    exit;
}

$banUntil = date('Y-m-d H:i:s', strtotime('+24 hours'));

$stmt = $pdo->prepare("UPDATE users 
    SET Ban_until = ?, Can_login = 0 
    WHERE user_uuid = ?");

$stmt->execute([$banUntil, $user_uuid]);

if ($stmt->rowCount() === 0) {
    echo json_encode(["success" => false, "message" => "User not found"]);
    exit;
}

echo json_encode([
    "success" => true,
    "ban_until" => $banUntil
]);
exit;
