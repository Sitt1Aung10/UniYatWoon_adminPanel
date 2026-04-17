<?php
require_once 'cors.php';
require_once 'db_connect.php';
require_once 'auth.php'; // $user_uuid comes from JWT

$now = date('Y-m-d H:i:s');

$autoUnban = $pdo->prepare("UPDATE users 
    SET Ban_until = NULL
    WHERE Ban_until IS NOT NULL AND Ban_until <= ?
");
$autoUnban->execute([$now]);

$sql = "SELECT 
        id,
        Username,
        Major,
        Year,
        role,
        Phone,
        Email,
        Student_nrc,
        Profile_photo,
        user_uuid,
        Ban_until,
        is_admin
    FROM users
    WHERE is_admin = 0
    ORDER BY id DESC
";

$stmt = $pdo->query($sql);
$users = $stmt->fetchAll();

$countUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0");
$totalUsers = $countUsers->fetchColumn();

echo json_encode([
    'total_users' => $totalUsers,
    'users' => $users
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

