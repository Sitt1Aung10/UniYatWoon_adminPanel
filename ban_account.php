<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$username = $data['Username'] ?? '';

if (!$username) {
    echo json_encode(["success" => false, "message" => "Username missing"]);
    exit;
}

$banUntil = date('Y-m-d H:i:s', strtotime('+24 hours'));

$stmt = $pdo->prepare("UPDATE users 
    SET Ban_until = ?, Can_login = 0 
    WHERE Username = ?
");
$stmt->execute([$banUntil, $username]);

if ($stmt->rowCount() === 0) {
    echo json_encode(["success" => false, "message" => "User not found"]);
    exit;
}

echo json_encode([
    "success" => true,
    "ban_until" => $banUntil
]);
exit;
