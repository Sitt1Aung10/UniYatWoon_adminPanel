<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log');
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");

require_once "db_connect.php";

if (!isset($pdo)) {
    echo json_encode([
        "success" => false,
        "error" => "DB connection not initialized"
    ]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

// Add this debugging block
if ($data === null) {
    echo json_encode([
        "success" => false,
        "error" => "JSON Decode Failed or Empty Body",
        "json_error" => json_last_error_msg(),
        "raw_input" => file_get_contents("php://input"),
        "method" => $_SERVER['REQUEST_METHOD']
    ]);
    exit;
}

$since = $data['since'] ?? null;
$type  = $data['type'] ?? 'normal';

if (!$since) {
    echo json_encode([
        "success" => false,
        "error" => "Missing since parameter"
    ]);
    exit;
}

$sql = "SELECT COUNT(*) as new_count 
        FROM posts 
        WHERE Created_at > ? AND type = ?";

$stmt = $pdo->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "error" => "Prepare failed"
    ]);
    exit;
}

$stmt->execute([$since, $type]);

$row = $stmt->fetch(PDO::FETCH_ASSOC);

$count = (int)$row['new_count'];

echo json_encode([
    "success" => true,
    "has_new" => $count > 0,
    "count" => $count,
    "since" => $since,
    "type" => $type
]);

