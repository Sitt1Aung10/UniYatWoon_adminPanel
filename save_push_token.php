<?php
require_once 'cors.php';
require_once 'db_connect.php';
require_once 'auth.php'; // provides $user_uuid from JWT

// Expect JSON body with either 'token' or 'push_token'
$data = json_decode(file_get_contents('php://input'), true);
$token = '';
if (is_array($data)) {
    $token = trim($data['token'] ?? $data['push_token'] ?? '');
}

if ($token === '') {
    // also accept form-encoded POST for legacy clients
    $token = trim($_POST['token'] ?? $_POST['push_token'] ?? '');
}

if (empty($user_uuid)) {
    echo json_encode(["success" => false, "message" => "Authentication required"]);
    exit;
}

if ($token === '') {
    echo json_encode(["success" => false, "message" => "No push token provided"]);
    exit;
}

// Upsert token for this user
try {
    $select = $pdo->prepare('SELECT id FROM user_push_tokens WHERE user_uuid = ? LIMIT 1');
    $select->execute([$user_uuid]);
    $row = $select->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $upd = $pdo->prepare('UPDATE user_push_tokens SET push_token = ?, updated_at = NOW() WHERE id = ?');
        $upd->execute([$token, $row['id']]);
    } else {
        $ins = $pdo->prepare('INSERT INTO user_push_tokens (user_uuid, push_token, created_at) VALUES (?, ?, NOW())');
        $ins->execute([$user_uuid, $token]);
    }

    echo json_encode(["success" => true, "message" => "Token saved"]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error", "details" => $e->getMessage()]);
}
