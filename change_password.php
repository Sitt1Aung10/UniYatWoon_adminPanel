<?php
require_once "cors.php";
require_once "auth.php"; // verifies JWT
require_once "db_connect.php";

$raw  = file_get_contents("php://input");
$data = json_decode($raw, true);

$current = trim($data['current_password'] ?? '');
$new      = trim($data['new_password'] ?? '');

if ($current === '' || $new === '') {
    echo json_encode([
        "success" => false,
        "message" => "All fields required"
    ]);
    exit;
}

/* Get user */
$stmt = $pdo->prepare(
    "SELECT Password FROM users
     WHERE user_uuid=? LIMIT 1"
);
$stmt->execute([$user_uuid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode([
        "success" => false,
        "message" => "User not found"
    ]);
    exit;
}

/* Verify old password */
if (!password_verify($current, $user['Password'])) {
    echo json_encode([
        "success" => false,
        "message" => "Current password incorrect"
    ]);
    exit;
}

/* Update new password */
$newHash = password_hash($new, PASSWORD_DEFAULT);

$upd = $pdo->prepare(
    "UPDATE users
     SET Password=?
     WHERE user_uuid=?"
);

$upd->execute([$newHash, $user_uuid]);

echo json_encode([
    "success" => true,
    "message" => "Password updated"
]);
exit;