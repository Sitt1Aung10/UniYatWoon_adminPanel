<?php
require 'db.php';
require 'auth.php';

$user_id = getUserId();

$data = json_decode(file_get_contents("php://input"), true);
$otp = $data['otp'] ?? '';

$stmt = $conn->prepare("SELECT * FROM otp_codes WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "No OTP"]);
    exit;
}

$row = $res->fetch_assoc();

// check expiry
if (strtotime($row['expires_at']) < time()) {
    echo json_encode(["success" => false, "message" => "Expired"]);
    exit;
}

// verify
if (!password_verify($otp, $row['otp_hash'])) {
    echo json_encode(["success" => false, "message" => "Invalid OTP"]);
    exit;
}

// delete after success
$conn->query("DELETE FROM otp_codes WHERE user_id = $user_id");

echo json_encode(["success" => true]);
?>