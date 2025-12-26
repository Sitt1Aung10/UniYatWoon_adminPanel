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

$description = $_POST['Description'] ?? '';


$missing = [];
if ($description === '') $missing = 'Description';

if(!empty($missing)) {
    echo json_encode([
        "success" => false,
        "missing" => $missing
    ]);
    exit;
}

$sql = "INSERT INTO posts ( Username , User_uuid , Description ) VALUES( ? , ? , ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$username, $user_uuid, $description]);

echo json_encode([
    "success" => true
]);

// get post id
$post_id = $pdo->lastInsertId();

foreach ($_FILES['media']['tmp_name'] as $i => $tmp) {

    $name = $_FILES['media']['name'][$i];
    $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
        $media_type = 'image';
    } elseif (in_array($ext, ['mp4','mov','avi','mkv','webm'])) {
        $media_type = 'video';
    } else {
        continue; // skip unknown files
    }

    $media_url = 'uploads/' . time() . '_' . $name;
    move_uploaded_file($tmp, $media_url);

    $sql = "INSERT INTO posts_media (post_id, Media_url, Media_type)
            VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$post_id, $media_url, $media_type]);
}
