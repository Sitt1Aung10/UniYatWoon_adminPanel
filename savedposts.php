<?php 
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost:5173"); 
header("Access-Control-Allow-Credentials: true"); 
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); 
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With, Accept"); 
ini_set('session.cookie_path' , '/'); 

session_start(); 

$username = $_SESSION['Username'] ?? null; 

include 'db_connect.php'; 

$post_id = $_POST['post_id'] ?? null; 
$saved_post_id = (int)$post_id; 

$find_user_id = "SELECT id FROM users WHERE username = ?"; 
$userStmt = $pdo->prepare($find_user_id); 
$userStmt->execute([$username]); 
$user = $userStmt->fetch(PDO::FETCH_ASSOC); 
$user_id = $user['id'] ?? null; 

$sql = "INSERT INTO saved_posts ( user_id , post_id ) VALUES( ? , ? )"; 
$stmt = $pdo->prepare($sql);  
$stmt->execute([$user_id, $saved_post_id]); 

echo json_encode([ 
    "success" => true, 
    "user_id" => $user_id, 
    "post_id" => $saved_post_id 
]);