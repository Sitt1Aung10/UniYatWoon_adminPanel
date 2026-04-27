<?php
require_once "cors.php";
require_once "db_connect.php";
require_once "auth.php";

header('Content-Type: application/json');

$raw = file_get_contents("php://input");
$input = json_decode($raw, true);

// ❗ Safe validation
if (!is_array($input)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON"
    ]);
    exit;
}

$post_id = $input['post_id'] ?? null;
$cursor  = isset($input['cursor']) ? (int)$input['cursor'] : null;
$limit   = 10;

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
   FETCH COMMENTS (CURSOR BASED)
========================= */
// We use c.id ASC to get older comments first. 
// If a cursor is provided, we fetch comments that have an ID greater than the cursor.
if ($cursor) {
    $sql = "
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
        WHERE c.post_id = ? AND c.id > ?
        ORDER BY c.id ASC
        LIMIT ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(1, $post_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $cursor, PDO::PARAM_INT);
    $stmt->bindValue(3, $limit, PDO::PARAM_INT);
} else {
    // Initial load (No cursor)
    $sql = "
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
        ORDER BY c.id ASC
        LIMIT ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(1, $post_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
}

$stmt->execute();
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   TOTAL COUNT (FIXED)
========================= */
// Your previous query used SELECT * with fetchColumn(), which doesn't count rows properly. 
// Using SELECT COUNT(id) is the correct and fastest way to get the total.
$stmtCount = $pdo->prepare("SELECT COUNT(id) FROM comments WHERE post_id = ?");
$stmtCount->execute([$post_id]);
$total = (int)$stmtCount->fetchColumn();

/* =========================
   PAGINATION LOGIC
========================= */
// With cursor pagination, if the amount of rows fetched equals our limit,
// it's highly likely there is another page.
$has_more = count($comments) === $limit;

/* =========================
   RESPONSE
========================= */
echo json_encode([
    "success" => true,
    "comments" => $comments,
    "pagination" => [
        "limit" => $limit,
        "total" => $total,
        "has_more" => $has_more,
        "cursor_used" => $cursor
    ]
]);
exit;