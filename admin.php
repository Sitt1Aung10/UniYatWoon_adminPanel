<?php
// CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

include 'db_connect.php';

$sql = 'SELECT id, Admin, Password, Login_at, Logout_at FROM admin ORDER BY id DESC';
$stmt = $pdo->query($sql);


$admins = $stmt->fetchAll();

header('Content-Type: application/json; charset=utf-8');
echo json_encode($admins, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
