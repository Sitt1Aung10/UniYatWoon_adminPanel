<?php
require_once 'cors.php';
require_once 'db_connect.php';
require_once 'auth.php'; // $user_uuid from JWT

/* =========================
   1️⃣ Validate input
   ========================= */
$raw  = file_get_contents("php://input");
$data = json_decode($raw, true);

$post_id = $data['post_id'] ?? null;
if (!$post_id) {
    echo json_encode(["success" => false, "message" => "post_id required"]);
    exit;
}

/* =========================
   2️⃣ Check if post exists
   ========================= */
$stmt = $pdo->prepare("SELECT id FROM posts WHERE id = ? LIMIT 1");
$stmt->execute([$post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    echo json_encode(["success" => false, "message" => "Post not found"]);
    exit;
}

/* =========================
   3️⃣ Check if already saved
   ========================= */
$stmt = $pdo->prepare(
    "SELECT id FROM savedposts WHERE post_id = ? AND user_uuid = ? LIMIT 1"
);
$stmt->execute([$post_id, $user_uuid]);
$saved = $stmt->fetch(PDO::FETCH_ASSOC);

if ($saved) {
    // Already saved → unsave
    $stmt = $pdo->prepare("DELETE FROM savedposts WHERE id = ?");
    $stmt->execute([$saved['id']]);
    $action = "unsaved";
} else {
    // Save post
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO savedposts (post_id, user_uuid) VALUES (?, ?)"
        );
        $stmt->execute([$post_id, $user_uuid]);
        $action = "saved";
    } catch (PDOException $e) {
        // If duplicate somehow slipped through, treat as already saved
        $action = "saved";
    }
}
/* =========================
   4️⃣ Return saved count
   ========================= */
$stmt = $pdo->prepare(
    "SELECT COUNT(*) AS save_count FROM savedposts WHERE post_id = ?"
);
$stmt->execute([$post_id]);
$count = $stmt->fetch(PDO::FETCH_ASSOC)['save_count'] ?? 0;

echo json_encode([
    "success"     => true,
    "action"      => $action,
    "save_count"  => $count,
    "is_saved"   => ($action === "saved" ? 1 : 0)
]);
exit;



