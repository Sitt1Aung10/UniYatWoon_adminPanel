<?php
require_once 'cors.php';
require_once 'db_connect.php';
require_once 'auth.php';
require_once 'autoload.php';


/* 👤 Fetch reporter */
$sql = "SELECT Username FROM users WHERE user_uuid = :user_uuid LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([':user_uuid' => $user_uuid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode([
        "success" => false,
        "message" => "User not found"
    ]);
    exit;
}

$username = $user['Username'];

/* 📌 Input validation */
$post_id = $_POST['post_id'] ?? null;
$reason  = $_POST['Reason'] ?? '';

if (!$post_id || trim($reason) === '') {
    echo json_encode([
        "success" => false,
        "message" => "Missing post_id or reason"
    ]);
    exit;
}

/* 🚨 Insert report */
$sql = "INSERT INTO report_posts
(Reporter_username, Reporter_user_uuid, Reported_post_id, Reason)
VALUES (?, ?, ?, ?)
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$username, $user_uuid, $post_id, $reason]);

echo json_encode([
    "success" => true
]);
