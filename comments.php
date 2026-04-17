<?php
require_once "cors.php";
require_once "db_connect.php";
require_once "auth.php"; // $user_uuid

$raw = file_get_contents("php://input");
$input = json_decode($raw, true);

if ($input === null) {
    echo json_encode(["success" => false, "message" => "Invalid JSON"]);
    exit;
}

$post_id     = $input['post_id'] ?? null;
$description = trim($input['Description'] ?? '');
$parent_id = $input['parent_id'] ?? null;

if (!$post_id) {
    echo json_encode(["success" => false, "message" => "Missing id"]);
    exit;
}else if($description == ''){
    echo json_encode(["success" => false, "message" => "Missing desc"]);
}

$stmt = $pdo->prepare("SELECT user_uuid, Description FROM posts WHERE id = ? LIMIT 1");
$stmt->execute([$post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    echo json_encode(["success" => false, "message" => "Post not found"]);
    exit;
}

$post_owner = $post['user_uuid'];

/* USER STATUS */
$stmt = $pdo->prepare("SELECT Username, Can_login FROM users WHERE user_uuid = ? LIMIT 1");
$stmt->execute([$user_uuid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ((int)$user['Can_login'] === 0) {
    echo json_encode(["success" => false, "message" => "Account disabled"]);
    exit;
}

$username = $user['Username'];

/* INSERT COMMENT */
$stmt = $pdo->prepare(
    "INSERT INTO comments (user_uuid, Username, post_id, Description, Parent_id, Created_at) 
     VALUES (?, ?, ?, ?, ?, NOW())"
);
$stmt->execute([$user_uuid, $username, $post_id, $description, $parent_id]);

/* NOTIFICATION */
if ($post_owner !== $user_uuid) {
    $postDesc = trim((string)($post['Description'] ?? ''));
    $postSnippet = '';
    if ($postDesc !== '') {
        $postSnippet = mb_substr($postDesc, 0, 120, 'UTF-8');
        $postSnippet = str_replace(["\r", "\n"], ' ', $postSnippet);
        $postSnippet = str_replace('"', "'", $postSnippet);
    }

    if ($postSnippet !== '') {
        $message = "$username commented on your post \"$postSnippet\"";
    } else {
        $message = "$username commented on your post";
    }

    $stmt = $pdo->prepare(
        "INSERT INTO notifications (user_uuid, from_uuid, post_id, type, message)
         VALUES (?, ?, ?, 'comment', ?)"
    );
    $stmt->execute([$post_owner, $user_uuid, $post_id, $message]);
}

/* COUNT */
$stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?");
$stmt->execute([$post_id]);
$count = (int)$stmt->fetchColumn();

echo json_encode([
    "success" => true,
    "comment_count" => $count
]);
exit;
