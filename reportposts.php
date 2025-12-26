<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With, Accept");
ini_set('session.cookie_path' , '/');
session_start();


$username  = $_SESSION['Username'] ?? null;
$user_uuid = $_SESSION['user_uuid'] ?? null;

include 'db_connect.php';

$post_id = $_POST['post_id'] ?? null;
$reported_post_id = $post_id;
$reason = $_POST['Reason'] ?? '';

$sql = "INSERT INTO report_posts ( Reporter_username , Reporter_user_uuid , Reported_post_id , Reason ) VALUES( ? , ? , ? , ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([ $username, $user_uuid, $reported_post_id, $reason]);

echo json_encode([
    "success" => true
]);
