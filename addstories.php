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

ini_set('session.cookie_path' , '/');
session_start();

$username  = $_SESSION['Username'] ?? null;
$user_uuid = $_SESSION['user_uuid'] ?? null;


include 'db_connect.php';

$caption = $_POST['Caption'] ?? '';


$missing = [];
if ($caption === '') $missing = 'Caption';
if(!empty($missing)) {
    echo json_encode([
        "success" => false,
        "missing" => $missing
    ]);
    exit;
}

$find_user = "SELECT id , Can_login FROM users WHERE Username = :username";
$stmt = $pdo->prepare($find_user);
$stmt->execute([':username' => $username]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ((int)$user['Can_login'] === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Account disabled"
    ]);
    exit;
}

$sql = "INSERT INTO stories ( Username , User_uuid , Caption ) VALUES( ? , ? , ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$username, $user_uuid, $caption]);

echo json_encode([
    "success" => true
]);

// get story id
$story_id = $pdo->lastInsertId();


// Config
$MAX_VIDEO_SECONDS = 60;
$UPLOAD_DIR = 'uploads/';

// Make sure PDO is already initialized as $pdo
// and $story_id is set (e.g., from POST)

// 1️⃣ Check uploaded file
if (!isset($_FILES['media']) || count($_FILES['media']['name']) !== 1) {
    echo json_encode([
    "success" => false,
    "message" => "Only one image or one video is allowed."
]);
exit;

}

$tmp  = $_FILES['media']['tmp_name'][0];
$name = $_FILES['media']['name'][0];
$ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));

// 2️⃣ Detect media type
$imageExts = ['jpg','jpeg','png','gif','webp'];
$videoExts = ['mp4','mov','avi','mkv','webm'];

if (in_array($ext, $imageExts)) {
    $media_type = 'image';
} elseif (in_array($ext, $videoExts)) {
    $media_type = 'video';
} else {
    die('Invalid media type.');
}


// 4️⃣ Handle video duration (auto-trim if too long)
if ($media_type === 'video') {
    // Get duration
    $duration = (float) shell_exec(
        "ffprobe -v error -show_entries format=duration " .
        "-of default=noprint_wrappers=1:nokey=1 " .
        escapeshellarg($tmp)
    );

    // Ensure upload folder exists
    if (!is_dir($UPLOAD_DIR)) {
        mkdir($UPLOAD_DIR, 0755, true);
    }

    if ($duration > $MAX_VIDEO_SECONDS) {
        // Trim video to MAX_VIDEO_SECONDS
        $media_url = $UPLOAD_DIR . 'trimmed_' . time() . '_' . basename($name);
        shell_exec(
    "ffmpeg -i " . escapeshellarg($tmp) .
    " -t $MAX_VIDEO_SECONDS -vf 'scale=trunc(iw/2)*2:trunc(ih/2)*2' -preset fast " .
    escapeshellarg($media_url) . " 2>&1"
);
    } else {
        // Video within limit, just move file
        $media_url = $UPLOAD_DIR . time() . '_' . basename($name);
        move_uploaded_file($tmp, $media_url);
    }
} else {
    // 5️⃣ Handle image upload
    if (!is_dir($UPLOAD_DIR)) {
        mkdir($UPLOAD_DIR, 0755, true);
    }
    $media_url = $UPLOAD_DIR . time() . '_' . basename($name);
    move_uploaded_file($tmp, $media_url);
}

// 6️⃣ Insert into database
$sql = "INSERT INTO stories_media (story_id, media_url, media_type)
        VALUES (?, ?, ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$story_id, $media_url, $media_type]);

echo json_encode([
    "success" => true,
    "message" => "Story media uploaded successfully!",
    "media_url" => $media_url,
    "media_type" => $media_type
]);
exit;