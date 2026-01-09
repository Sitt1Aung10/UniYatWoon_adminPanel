<?php
// CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

include 'db_connect.php';

$sql = 'SELECT id, Username , Major , Year_of_study , Phone , Email , Profile_photo FROM users ORDER BY id DESC';
$stmt = $pdo->query($sql);
$users = $stmt->fetchAll();

$countUsers = $pdo->query("SELECT COUNT(*) FROM users");
$totalUsers = $countUsers->fetchColumn();

echo json_encode([
    'total_users' => $totalUsers,
    'users' => $users
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

