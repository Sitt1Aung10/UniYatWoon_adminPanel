<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

include 'db_connect.php';
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$data = json_decode(file_get_contents("php://input") , true);

$username = strtolower($data['Username'] ?? '');

if(empty($username)) {
    echo json_encode([
        "success" => false,
        "message" => "User not Found",
    ]);
    exit;
}

$stmt = $pdo->prepare("UPDATE users SET Can_login = 0 WHERE Username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
echo json_encode([
    "success" => true,
    "message" => "Ban Successfully",
    "username" => $username
]);


if (!$user) {
    echo json_encode(["success" => false, "message" => "User not found"]);
    exit;
}




$User_account_id = (int)$user['id'];