<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With, Accept");
ini_set('session.cookie_path' , '/');
session_start();

include 'db_connect.php';


$sql = 'SELECT 
r.id AS report_id,
r.Reporter_username,
r.Reporter_user_uuid,
r.Reported_post_id,
r.Reason,

p.Username,
p.Description,

pm.id AS media_id,
pm.Media_url,
pm.Media_type

FROM report_posts r
LEFT JOIN posts p 
  ON r.Reported_post_id = p.id
LEFT JOIN posts_media pm 
  ON pm.post_id = p.id

ORDER BY r.id DESC
';


$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$reports = [];

foreach ($rows as $row) {
    $rid = $row['report_id'];

    if (!isset($reports[$rid])) {
        $reports[$rid] = [
            'id' => $row['report_id'],
            'Reporter_username' => $row['Reporter_username'],
            'Reporter_user_uuid' => $row['Reporter_user_uuid'],
            'Reported_post_id' => $row['Reported_post_id'],
            'Reason' => $row['Reason'],
            'Username' => $row['Username'],
            'Description' => $row['Description'],
            'media' => []   // ✅ THIS is what React needs
        ];
    }

    if (!empty($row['media_id'])) {
        $reports[$rid]['media'][] = [
            'Media_url' => $row['Media_url'],
            'Media_type' => $row['Media_type']
        ];
    }
}

echo json_encode(array_values($reports), JSON_UNESCAPED_SLASHES);