<?php
require_once "cors.php";
require_once __DIR__ . '/vendor/autoload.php';
/* ========= DB ========= */
require_once 'db_connect.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/* ========= READ JSON ========= */
$raw  = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!is_array($data)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid request"
    ]);
    exit;
}

$email = strtolower(trim($data['Email'] ?? ''));
$email = str_replace(' ', '', $email);

$password = trim((string)($data['Password'] ?? ''));

if ($email === '' || $password === '') {
    echo json_encode([
        "success" => false,
        "message" => "Email and password required"
    ]);
    exit;
}

/* ========= FETCH USER ========= */
$stmt = $pdo->prepare(
    "SELECT * FROM users
     WHERE Email = ?
     LIMIT 1"
);
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    echo json_encode(["success" => false, "message" => "Invalid email or password"]);
    exit;
}


$dbPassword = $user['Password'];

/* ========= PASSWORD CHECK ========= */
/* ========= PASSWORD CHECK ========= */
$loginOk = false;

/* Case 1: hashed password */
if (password_verify($password, $dbPassword)) {
    $loginOk = true;
}

/* Case 2: legacy plaintext password */
elseif (hash_equals($dbPassword, $password)) {
    $loginOk = true;

    /* auto-upgrade password */
    $newHash = password_hash($password, PASSWORD_DEFAULT);
    $upd = $pdo->prepare(
        "UPDATE users SET Password=? WHERE user_uuid=?"
    );
    $upd->execute([$newHash, $user['user_uuid']]);
}

if (!$loginOk) {
    echo json_encode(["success" => false, "message" => "Invalid email or password"]);
    exit;
}

$jwtSecret = null;
require_once __DIR__ . '/config.php'; // load shared JWT secret
$issuedAt = time();
$expire = $issuedAt + (60 * 60 * 24 * 365); // 365 days
$isAdmin = isset($user['is_admin']) ? (int)$user['is_admin'] : 0;

$payload = [
    "iat" => $issuedAt,
    "exp" => $expire,
    "uuid" => $user['user_uuid'],
    "username" => $user['Username'],
    "is_admin" => $isAdmin
];

$token = JWT::encode($payload, $jwtSecret, 'HS256');

echo json_encode([
    "success"  => true,
    "message"  => "Login successful",
    "token"    => $token,
    "expires"  => $expire,
    "user_uuid" => $user['user_uuid'],
    "Username" => $user['Username'],
    "is_admin" => $user['is_admin']
]);
exit;