<?php
require_once "cors.php";
require_once "db_connect.php";
require_once "auth.php";

header('Content-Type: application/json');

$raw = file_get_contents("php://input");
$input = json_decode($raw, true);

// ❗ Fix: safe validation
if (!is_array($input)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON"
    ]);
    exit;
}

$post_id = $input['post_id'] ?? null;
$page    = max(1, (int)($input['page'] ?? 1));
$limit   = 10;
$offset  = ($page - 1) * $limit;

if (!$post_id) {
    echo json_encode([
        "success" => false,
        "message" => "Post ID required"
    ]);
    exit;
}

/* =========================
   CHECK POST EXISTS
========================= */
$stmt = $pdo->prepare("SELECT id FROM posts WHERE id = ? LIMIT 1");
$stmt->execute([$post_id]);

if (!$stmt->fetch()) {
    echo json_encode([
        "success" => false,
        "message" => "Post not found"
    ]);
    exit;
}

/* =========================
   FETCH COMMENTS (FIXED)
========================= */
$stmt = $pdo->prepare("
    SELECT 
        c.id,
        c.user_uuid,
        c.Username,
        c.Description,
        c.Parent_id,
        c.Created_at,
        u.Profile_photo
    FROM comments c
    LEFT JOIN users u ON u.user_uuid = c.user_uuid
    WHERE c.post_id = ?
    ORDER BY c.Created_at ASC
    LIMIT ? OFFSET ?
");

$stmt->bindValue(1, $post_id, PDO::PARAM_INT);
$stmt->bindValue(2, $limit, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();

$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   TOTAL COUNT (REAL SOURCE)
========================= */
$stmt = $pdo->prepare("SELECT * FROM comments WHERE post_id = ? ORDER BY Created_at DESC
");
$stmt->execute([$post_id]);
$total = (int)$stmt->fetchColumn();

/* ❌ REMOVED WRONG UPDATE QUERY */
/* DO NOT increment comment_count here */

/* =========================
   RESPONSE
========================= */
echo json_encode([
    "success" => true,
    "comments" => $comments,
    "pagination" => [
        "page" => $page,
        "limit" => $limit,
        "total" => $total,
        "has_more" => ($offset + $limit) < $total
    ]
]);
exit;