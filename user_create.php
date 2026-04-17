<?php
require_once "cors.php";
require_once 'db_connect.php';
require_once __DIR__ . '/vendor/autoload.php';

// read raw JSON body
$raw = file_get_contents("php://input");
$input = json_decode($raw, true);

if ($input === null && $raw !== '') {
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
$email = isset($input['Email']) ? strtolower(trim($input['Email'])) : '';
$email = str_replace(' ', '', $email);
$password = isset($input['Password']) ? $input['Password'] : '';
$major    = isset($input['Major'])    ? trim($input['Major']) : '';
$role     = isset($input['role'])     ? trim($input['role'])  : 'student';

// Handle optional phone with default value
$phone = (isset($input['Phone']) && trim($input['Phone']) !== '') ? trim($input['Phone']) : '09';

// Conditional values for Year and NRC based on role
$year_of_study = !empty($input['Year'])        ? trim($input['Year'])        : ($role === 'teacher' ? 'Teacher' : 'N/A');
$student_nrc   = isset($input['Student_nrc'])  ? $input['Student_nrc']       : ($role === 'teacher' ? 'Teacher' : '');
$profile_photo = isset($input['Profile_photo']) ? trim($input['Profile_photo']) : '';

// 🛡️ Validation Logic
$required = ['Username', 'Email', 'Password', 'Major'];
if ($role !== 'teacher') {
    $required[] = 'Year';
    $required[] = 'Student_nrc';
}

$missing = [];
foreach ($required as $field) {
    if (empty($input[$field])) {
        $missing[] = $field;
    }
}

if (!empty($missing)) {
    echo json_encode([
"success" => false,
        "message" => "Missing required fields or you need to wait patiently while account is creating",
        "missing" => $missing,
        "received_role" => $role
    ]);
    exit;
}

// generate UUID v4
function generate_uuid_v4() {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}
$user_uuid = generate_uuid_v4();

// INSERT Statement (Added 'role' column)
$sql = "INSERT INTO users (Username, user_uuid, Email, Password, Profile_photo, Major, Year, Student_nrc, Phone, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $pdo->prepare($sql);

try {
    $stmt->execute([
        $username, 
        $user_uuid, 
        $email, 
        password_hash($password, PASSWORD_BCRYPT), 
        $profile_photo, 
        $major, 
        $year_of_study, 
        $student_nrc, 
        $phone,
        $role
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Account created successfully"
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}

