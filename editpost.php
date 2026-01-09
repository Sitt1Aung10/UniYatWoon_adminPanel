<?php
session_start();
include 'db_connect.php';

$username  = $_SESSION['Username'] ?? null;
$user_uuid = $_SESSION['user_uuid'] ?? null;

$post_id     = $_POST['post_id'] ?? null;
$description = $_POST['Description'] ?? '';
$deleted_media_ids = $_POST['deleted_media'] ?? []; // array of media IDs to delete

// --- Validate ---
$missing = [];
if (!$description) $missing[] = 'Description';
if (!$post_id) $missing[] = 'Post ID';
if (!empty($missing)) {
    echo json_encode(["success" => false, "missing" => $missing]);
    exit;
}

// --- Get user info ---
$find_user = "SELECT id, Can_login FROM users WHERE Username = :username";
$stmt = $pdo->prepare($find_user);
$stmt->execute(['username' => $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || (int)$user['Can_login'] === 0) {
    echo json_encode(["success" => false, "message" => "Account disabled"]);
    exit;
}

// --- Update post description ---
$sql = "UPDATE posts SET Username = ?, User_uuid = ?, Description = ? WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$username, $user_uuid, $description, $post_id]);

// --- Delete removed media ---
if (!empty($deleted_media_ids)) {
    $in  = str_repeat('?,', count($deleted_media_ids) - 1) . '?';
    $sql = "DELETE FROM posts_media WHERE id IN ($in) AND post_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([...$deleted_media_ids, $post_id]);
}

// --- Add new media ---
if (!empty($_FILES['media']['tmp_name'])) {
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
        if (move_uploaded_file($tmp, $media_url)) {
            $sql = "INSERT INTO posts_media (post_id, Media_url, Media_type) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$post_id, $media_url, $media_type]);
        }
    }
}

echo json_encode(["success" => true]);
