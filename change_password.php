<?php
require 'db.php';
require 'auth.php';

$user_id = getUserId();

$data = json_decode(file_get_contents("php://input"), true);

$current = $data['current_password'];
$new = $data['new_password'];

// get current password
$stmt = $conn->prepare("SELECT Password FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();

if (!password_verify($current, $user['Password'])) {
    echo json_encode(["success" => false, "message" => "Wrong password"]);
    exit;
}

$new_hash = password_hash($new, PASSWORD_BCRYPT);

$stmt = $conn->prepare("UPDATE users SET Password=? WHERE id=?");
$stmt->bind_param("si", $new_hash, $user_id);
$stmt->execute();

echo json_encode(["success" => true]);
?>