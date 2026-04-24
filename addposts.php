<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "cors.php";
require_once 'db_connect.php';
require_once 'config.php';
require_once 'auth.php'; // handles JWT and sets $user_uuid

// ========== FETCH USER INFO ==========
$stmt = $pdo->prepare("SELECT Username, Can_login, is_admin FROM users WHERE user_uuid = ? LIMIT 1");
$stmt->execute([$user_uuid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || (int)$user['Can_login'] === 0) {
    echo json_encode(["success" => false, "message" => "Account disabled"]);
    exit;
}

// ========== HARD-CODED LIMITS ==========
$MAX_VIDEO_SIZE = 50 * 1024 * 1024; // 50MB
$MAX_VIDEO_DURATION = 60; // seconds

// Early check: prevent uploads bigger than MAX_VIDEO_SIZE
if (isset($_SERVER['CONTENT_LENGTH']) && (int)$_SERVER['CONTENT_LENGTH'] > $MAX_VIDEO_SIZE) {
    echo json_encode([
        "success" => false,
        "Alert" => "Video must be less than 50MB"
    ]);
    exit;
}

// ========== VALIDATE DESCRIPTION ==========
$description = trim($_POST['Description'] ?? '');
if ($description === '' && empty($_FILES['media'])) {
    echo json_encode([
        "success" => false,
        "Alert" => "Post must contain text or a video"
    ]);
    exit;
}

// Optional: description length limit
if (mb_strlen($description, 'UTF-8') > 20000) {
    echo json_encode([
        "success" => false,
        "Alert" => "Description is too long"
    ]);
    exit;
}

// ========== POST TYPE ==========
$requested_type = strtolower($_POST['type'] ?? 'normal');
$isAdmin = !empty($user['is_admin']) && ((int)$user['is_admin'] === 1);
$allowed_types = ['announcement', 'lost_found', 'normal'];

if (!in_array($requested_type, $allowed_types, true)) $requested_type = 'normal';
$type = ($requested_type === 'announcement' && $isAdmin) ? 'announcement' : $requested_type;

// ========== PRE-CHECK VIDEO COUNT ==========
$video_exts = ['mp4','mov','avi','mkv','webm'];
$video_count = 0;

if (!empty($_FILES['media']['name'])) {
    if (is_array($_FILES['media']['name'])) {
        foreach ($_FILES['media']['name'] as $name) {
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (in_array($ext, $video_exts, true)) $video_count++;
        }
    } else {
        $ext = strtolower(pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $video_exts, true)) $video_count = 1;
    }
    if ($video_count > 1) {
        echo json_encode([
            "success" => false,
            "Alert" => "Only one video is allowed per post"
        ]);
        exit;
    }
}

// ========== INSERT POST ==========
$sql = "INSERT INTO posts (Username, user_uuid, Description, type) VALUES (?, ?, ?, ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user['Username'], $user_uuid, $description, $type]);
$post_id = $pdo->lastInsertId();

// ========== HANDLE MEDIA UPLOAD ==========
// Ensure we have an array to loop through safely
if (!empty($_FILES['media']['tmp_name']) && is_array($_FILES['media']['tmp_name'])) {
    if (!is_dir('uploads')) mkdir('uploads', 0755, true);

    foreach ($_FILES['media']['tmp_name'] as $i => $tmp) {
        if (empty($tmp) || !is_uploaded_file($tmp)) continue;

        // FIX: Ensure filename is a string, default to empty string if null
        $originalName = basename($_FILES['media']['name'][$i] ?? '');
        
        // FIX: Ensure extension check doesn't receive null
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $media_type = in_array($ext, ['mp4','mov','avi','mkv','webm']) ? 'video' : 'image';

        // ========== IMAGE HANDLING ==========
        if ($media_type === 'image') {
            $media_name = time() . '_' . bin2hex(random_bytes(4)) . '_' . $originalName;
            $media_path = 'uploads/' . $media_name;
            if (!move_uploaded_file($tmp, $media_path)) continue;

            $sql = "INSERT INTO posts_media (Post_id, Media_url, Media_type) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$post_id, $media_path, $media_type]);

        // ========== VIDEO HANDLING ==========
        } else {
            $file_size = $_FILES['media']['size'][$i] ?? filesize($tmp);
            if ($file_size > $MAX_VIDEO_SIZE) {
                echo json_encode(["success" => false, "Alert" => "Video must be less than 50MB"]);
                exit;
            }

            $media_name = time() . '_' . bin2hex(random_bytes(4)) . '_' . $originalName;
            $media_path = 'uploads/' . $media_name;
            if (!move_uploaded_file($tmp, $media_path)) continue;

            $duration = null;
            if (isset($_POST['media_duration'])) {
                if (is_array($_POST['media_duration'])) {
                    $duration = floatval($_POST['media_duration'][$i] ?? 0);
                } else {
                    $duration = floatval($_POST['media_duration']);
                }
            }

            if (empty($duration)) {
                // FIX: Ensure shell_exec output is treated as a string before trim
                $ffprobe = @shell_exec('which ffprobe 2>/dev/null');
                $ffprobe = trim($ffprobe ?? ''); 

                if ($ffprobe !== '') {
                    $cmd = escapeshellcmd($ffprobe) .
                        ' -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 ' .
                        escapeshellarg($media_path) . ' 2>&1';
                    
                    $output = @shell_exec($cmd);
                    // FIX: Check for null before trimming output
                    $val = trim($output ?? '');
                    if (is_numeric($val)) $duration = floatval($val);
                }
            }

            if ($duration !== null && $duration > $MAX_VIDEO_DURATION) {
                @unlink($media_path);
                echo json_encode(["success" => false, "Alert" => "Video must be 60 seconds or shorter"]);
                exit;
            }

            $sql = "INSERT INTO posts_media (Post_id, Media_url, Media_type) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$post_id, $media_path, $media_type]);
        }
    }
}

// ========== RESPONSE ==========
echo json_encode([
    "success" => true,
    "post_id" => $post_id,
    "type"    => $type
]);
exit;
