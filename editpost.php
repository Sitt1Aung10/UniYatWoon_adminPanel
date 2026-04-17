<?php
// 1. Error Reporting (Disable display_errors for production to avoid JSON Parse errors)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

require_once "cors.php";
require_once 'db_connect.php';
require_once 'config.php';
require_once 'auth.php'; // handles JWT and sets $user_uuid

// 2. Fetch User Info
$stmt = $pdo->prepare("SELECT Username, Can_login, is_admin FROM users WHERE user_uuid = ? LIMIT 1");
$stmt->execute([$user_uuid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || (int)$user['Can_login'] === 0) {
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "Account disabled"]);
    exit;
}

header('Content-Type: application/json');

try {
    $auth_user_uuid = $user_uuid;

    // 3. Extract POST Data
    $incoming_identifier = $_POST['post_id'] ?? $_POST['id'] ?? $_POST['post_uuid'] ?? null;
    $description = $_POST['Description'] ?? '';
    $type = $_POST['type'] ?? 'normal';

    // 4. Handle Existing Media Keep List
    $apply_media_keep_list = array_key_exists('existing_media', $_POST);
    $existing_media_ids = [];
    $existing_media_urls = [];
    
    if ($apply_media_keep_list) {
        $raw = $_POST['existing_media'];
        if (is_array($raw)) {
            $incoming_media_tokens = $raw;
        } elseif (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            $incoming_media_tokens = is_array($decoded) ? $decoded : [$raw];
        } else {
            $incoming_media_tokens = [];
        }

        foreach ($incoming_media_tokens as $token) {
            if (is_numeric($token) && (int)$token > 0) {
                $existing_media_ids[] = (int)$token;
            } elseif (is_string($token) && trim($token) !== '') {
                $existing_media_urls[] = trim($token);
            }
        }
    }

    // 5. Validate Post ID
    if (!$incoming_identifier || !is_numeric($incoming_identifier)) {
        throw new Exception("Invalid or missing post identifier.");
    }
    $post_id = (int)$incoming_identifier;

    // 6. Verify Ownership/Permissions
    $stmt = $pdo->prepare("SELECT user_uuid FROM posts WHERE id = ? LIMIT 1");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        throw new Exception("Post not found.");
    }

    $isAdmin = (isset($user['is_admin']) && (int)$user['is_admin'] === 1);
    if ($post['user_uuid'] !== $auth_user_uuid && !$isAdmin) {
        throw new Exception("Unauthorized: You do not have permission to edit this post.");
    }

    // Begin Transaction
    $pdo->beginTransaction();

    // 7. Update Post Details
    $updateStmt = $pdo->prepare("UPDATE posts SET Description = ?, type = ?, Updated_at = NOW() WHERE id = ?");
    $updateStmt->execute([$description, $type, $post_id]);

    // 8. Handle Existing Media Deletions
    if ($apply_media_keep_list) {
        // Querying with Post_id (Capital P)
        $mediaStmt = $pdo->prepare("SELECT Id, Media_url FROM posts_media WHERE Post_id = ?");
        $mediaStmt->execute([$post_id]);
        $rows = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $rowId = (int)$row['Id'];
            $rowUrl = $row['Media_url'];

            $isKeptById = in_array($rowId, $existing_media_ids, true);
            $isKeptByUrl = in_array($rowUrl, $existing_media_urls, true);

            // Fuzzy match for absolute URLs vs relative paths
            if (!$isKeptByUrl) {
                foreach ($existing_media_urls as $clientUrl) {
                    if (str_contains($clientUrl, $rowUrl)) {
                        $isKeptByUrl = true;
                        break;
                    }
                }
            }

            if (!$isKeptById && !$isKeptByUrl) {
                // Delete from DB
                $delStmt = $pdo->prepare("DELETE FROM posts_media WHERE Id = ?");
                $delStmt->execute([$rowId]);

                // Delete physical file
                $filePath = __DIR__ . "/" . $rowUrl; 
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
        }
    }

    // 9. Handle NEW Media Uploads
    if (isset($_FILES['media'])) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        foreach ($_FILES['media']['tmp_name'] as $i => $tmpName) {
            if ($_FILES['media']['error'][$i] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['media']['name'][$i], PATHINFO_EXTENSION);
                $newName = "uploads/post_" . uniqid() . "_" . time() . "." . $ext;
                if (move_uploaded_file($tmpName, __DIR__ . "/" . $newName)) {
                    $mediaType = (strpos($_FILES['media']['type'][$i], 'video') !== false) ? 'video' : 'image';
                    $stmt = $pdo->prepare("INSERT INTO posts_media (Post_id, Media_url, Media_type) VALUES (?, ?, ?)");
                    $stmt->execute([$post_id, $newName, $mediaType]);
                }
            }
        }
    }

    $pdo->commit();
    echo json_encode(["success" => true, "message" => "Post updated successfully"]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>