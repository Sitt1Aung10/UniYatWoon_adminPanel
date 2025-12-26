<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With, Accept");
ini_set('session.cookie_path' , '/');
session_start();

include 'db_connect.php';


$sql = 'SELECT 
r.id, 
r.Reporter_username, 
r.Reporter_user_uuid, 
r.Reported_post_id, 
r.Reason , 
p.Username , 
p.Description FROM report_posts r 
LEFT JOIN posts p ON r.Reported_post_id = p.id
ORDER BY id DESC
';

$stmt = $pdo->query($sql);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($reports, JSON_UNESCAPED_SLASHES);