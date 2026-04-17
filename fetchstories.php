<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With, Accept");
include 'db_connect.php';
// Delete expired story media files
$sql = "SELECT sm.Media_url
    FROM stories s
    LEFT JOIN stories_media sm ON s.id = sm.story_id
    WHERE s.Created_at < NOW() - INTERVAL 24 HOUR
      AND sm.Media_url IS NOT NULL
";
$stmt = $pdo->query($sql);
$expiredMedia = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($expiredMedia as $media) {
    if (file_exists($media['Media_url'])) {
        unlink($media['Media_url']);
    }
}

// Delete expired stories + media records
$sql = "DELETE s, sm
    FROM stories s
    LEFT JOIN stories_media sm ON s.id = sm.story_id
    WHERE s.Created_at < NOW() - INTERVAL 24 HOUR
";
$pdo->exec($sql);


$sql = "SELECT s.*, sm.Media_url, sm.Media_type
    FROM stories s
    JOIN stories_media sm ON s.id = sm.story_id
    WHERE s.Created_at >= NOW() - INTERVAL 24 HOUR
    ORDER BY s.Created_at DESC
";
$stmt = $pdo->query($sql);
$stories = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($stories, JSON_UNESCAPED_SLASHES);


