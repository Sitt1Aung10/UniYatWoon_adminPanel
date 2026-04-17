<?php
require_once 'cors.php';
require_once 'db_connect.php';
require_once 'auth.php'; // JWT protection

// Only Admin can broadcast
if ($is_admin != 1) {
    echo json_encode(["success" => false, "message" => "Admin only"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$title = $data['title'] ?? 'New Broadcast';
$body = $data['body'] ?? 'Hello from UniYatwon!';

// 1. Fetch all tokens from database
$stmt = $pdo->query("SELECT push_token FROM user_push_tokens");
$tokens = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($tokens)) {
    echo json_encode(["success" => false, "message" => "No tokens found"]);
    exit;
}

// 2. Prepare message for Expo
$messages = [];
foreach ($tokens as $token) {
    $messages[] = [
        "to" => $token,
        "title" => $title,
        "body" => $body,
        "sound" => "default",
        "data" => ["type" => "broadcast"]
    ];
}

// 3. Send to Expo Push API
$ch = curl_init("https://exp.host/--/api/v2/push/send");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($messages));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Accept-Encoding: gzip, deflate'
]);

$response = curl_exec($ch);
curl_close($ch);

echo $response; // Return Expo response to Admin
