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
$profile_photo = isset($input['Profile_photo']) ? trim($input['Profile_photo']) : '';
$major = isset($input['Major']) ? trim($input['Major']) : '';
$year_of_study = isset($input['Year_of_study']) ? trim($input['Year_of_study']) : '';
$phone = isset($input['Phone']) ? trim($input['Phone']) : '';
// generate UUID v4
function generate_uuid_v4() {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,  // version 4
        mt_rand(0, 0x3fff) | 0x8000,  // variant
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

$user_uuid = generate_uuid_v4();


$missing = [];
foreach (['Username', 'user_uuid' , 'Email', 'Password', 'Major', 'Year_of_study'] as $field) {
    if (empty($input[$field])) {
        $missing[] = $field;
    }
}
if (!empty($missing)) {
    echo json_encode([
        "success" => false,
        "message" => "Missing fields",
        "missing" => $missing,
        "received_raw" => $raw,
        "received_parsed" => $input
    ]);
    exit;
}



$sql = "INSERT INTO users ( Username , user_uuid , Email , Password , Profile_photo , Major , Year_of_study , Phone ) VALUES( ? , ? , ? , ? , ? , ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$username, $user_uuid, $email, password_hash($password, PASSWORD_BCRYPT), $profile_photo, $major, $year_of_study , $phone]);
echo json_encode([
    "success" => true
]);