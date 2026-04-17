<?php
require_once "cors.php";
require_once "db_connect.php";
require_once "auth.php"; // Provides $user_uuid

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->prepare("SELECT id, search_text, target_uuid, target_type FROM search_history WHERE user_uuid = ? ORDER BY created_at DESC LIMIT 15");
    $stmt->execute([$user_uuid]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
    $text = $input['search_text'] ?? '';
    if (empty($text)) {
        // DEBUG: keep — this early return is intentional and has no echo
        exit;
    }

    // Delete existing same search for this user to keep it at top
    $pdo->prepare("DELETE FROM search_history WHERE user_uuid = ? AND search_text = ?")->execute([$user_uuid, $text]);

    $stmt = $pdo->prepare("INSERT INTO search_history (user_uuid, search_text, target_uuid, target_type) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $user_uuid,
        $text,
        $input['target_uuid'] ?? null,
        $input['target_type'] ?? 'query'
    ]);
    echo json_encode(["success" => true]);

} elseif ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if ($id) {
        $stmt = $pdo->prepare("DELETE FROM search_history WHERE id = ? AND user_uuid = ?");
        $stmt->execute([$id, $user_uuid]);
    }
    echo json_encode(["success" => true]);
}
