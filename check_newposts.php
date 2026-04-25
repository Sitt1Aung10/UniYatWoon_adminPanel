<?php
header("Content-Type: application/json");
require_once "db_connect.php"; // your DB connection

$response = [
    "success" => false,
    "has_new" => false,
    "count" => 0
];

try {
    // 🔹 Get params
    $since = $_GET['since'] ?? null;
    $type  = $_GET['type'] ?? 'normal';

    if (!$since) {
        echo json_encode([
            "success" => false,
            "error" => "Missing 'since' parameter"
        ]);
        exit;
    }

    // 🔹 Lightweight query (ONLY COUNT)
    $sql = "
        SELECT COUNT(*) as new_count
        FROM posts
        WHERE Created_at > ?
        AND type = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $since, $type);
    $stmt->execute();

    $result = $stmt->get_result()->fetch_assoc();
    $count = (int)$result['new_count'];

    $response["success"] = true;
    $response["has_new"] = $count > 0;
    $response["count"] = $count;

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}