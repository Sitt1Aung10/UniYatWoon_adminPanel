<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With, Accept");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

ini_set('session.cookie_path', '/');
session_start();

// file_put_contents(__DIR__.'/debug_raw_body.txt', date('c')." - raw:\n".file_get_contents('php://input')."\n\n", FILE_APPEND);

include 'db_connect.php';

// read raw JSON body
$raw = file_get_contents("php://input");
// debug: write raw somewhere or return it if needed
$input = json_decode($raw, true);

if ($input === null && $raw !== '') {
    // JSON parse failed — helpful debug
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON received",
        "raw" => $raw
    ]);
    exit;
}

// fall back to $_POST if needed (form-encoded)
if (!$input) $input = $_POST;

$username = isset($input['Username']) ? trim($input['Username']) : '';
$email    = isset($input['Email']) ? trim($input['Email']) : '';
$password = isset($input['Password']) ? $input['Password'] : '';

$missing = [];
if ($username === '') $missing[] = 'Username';
if ($email === '')    $missing[] = 'Email';
if ($password === '') $missing[] = 'Password';

if (!empty($missing)) {
    echo json_encode([
        "success" => false,
        "message" => "Missing fields",
        "missing" => $missing,
        "received_raw" => $raw,        // helpful debugging
        "received_parsed" => $input   // helpful debugging
    ]);
    exit;
}

// lookup user
$sql = "SELECT * FROM users WHERE Username = :username AND Email = :email";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ":username" => $username,
    ":email"    => $email
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode([
        "success" => false,
        "message" => "User not found"
    ]);
    exit;
}

if ((int)$user['Can_login'] === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Account disabled"
    ]);
    exit;
}

// NOTE: this is plain-text compare. In production use password_hash / password_verify.
if ($password !== $user['Password']) {
    echo json_encode([
        "success" => false,
        "message" => "Incorrect password"
    ]);
    exit;
}

// successful login: set session values
$_SESSION['Username'] = $user['Username'];
$_SESSION['user_uuid'] = $user['user_uuid'];

// optionally regenerate session id
// session_regenerate_id(true);

// set persistent cookies (optional)
setcookie('Username', $user['Username'], time() + (10 * 365 * 24 * 60 * 60), "/");
setcookie('Email', $user['Email'], time() + (10 * 365 * 24 * 60 * 60), "/");

echo json_encode([
    "success"   => true,
    "message"   => "Login successful",
    "Username"  => $user['Username'],
    "user_uuid" => $user['user_uuid']
]);
exit;
