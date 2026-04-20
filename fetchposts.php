<?php
// require_once "cors.php";
// require_once "db_connect.php";
// require_once "auth.php"; // provides $user_uuid

// header('Content-Type: application/json');

// $type   = $_GET['type'] ?? 'normal';
// $page   = max(1, (int)($_GET['page'] ?? 1));
// $limit  = 6;
// $offset = ($page - 1) * $limit;

// /* =========================
//    1️⃣ Fetch feed posts
//    ========================= */
// $sql = "SELECT
//     p.id,
//     p.user_uuid,
//     p.Username,
//     p.Description,
//     p.Created_at,
//     u.Profile_photo,

//     COUNT(DISTINCT l.id) AS like_count,
//     COUNT(DISTINCT c.id) AS comment_count,

//     MAX(CASE WHEN l.user_uuid = :uuid_like THEN 1 ELSE 0 END) AS is_liked,
//     MAX(CASE WHEN s.user_uuid = :uuid_save THEN 1 ELSE 0 END) AS is_saved,

//     CASE WHEN f.follower_uuid IS NOT NULL THEN 1 ELSE 0 END AS is_following

// FROM posts p
// LEFT JOIN users u ON u.user_uuid = p.user_uuid
// LEFT JOIN likes l ON l.post_id = p.id
// LEFT JOIN comments c ON c.post_id = p.id
// LEFT JOIN savedposts s ON s.post_id = p.id
// LEFT JOIN follows f 
//     ON f.following_uuid = p.user_uuid 
//    AND f.follower_uuid = :uuid_follow

// WHERE p.type = :type

// GROUP BY p.id

// ORDER BY
//     is_following DESC,
//     like_count DESC,
//     comment_count DESC,
//     p.Created_at DESC

// LIMIT :limit OFFSET :offset";

// try {
//     $stmt = $pdo->prepare($sql);

//     $stmt->bindValue(':uuid_like', $user_uuid, PDO::PARAM_STR);
//     $stmt->bindValue(':uuid_save', $user_uuid, PDO::PARAM_STR);
//     $stmt->bindValue(':uuid_follow', $user_uuid, PDO::PARAM_STR);
//     $stmt->bindValue(':type', $type, PDO::PARAM_STR);
//     $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
//     $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

//     $stmt->execute();
//     $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

//     /* =========================
//        2️⃣ Count total posts
//        ========================= */
//     $countSql = "SELECT COUNT(*) FROM posts WHERE type = :type";
//     $countStmt = $pdo->prepare($countSql);
//     $countStmt->bindValue(':type', $type, PDO::PARAM_STR);
//     $countStmt->execute();
//     $totalPosts = (int)$countStmt->fetchColumn();
//     $totalPages = ceil($totalPosts / $limit);

//     /* =========================
//        3️⃣ Attach media
//        ========================= */
//     $postIds = array_column($posts, 'id');

//     if (!empty($postIds)) {
//         $placeholders = implode(',', array_fill(0, count($postIds), '?'));

//         $media_sql = "SELECT Post_id, Media_url, Media_type
//                       FROM posts_media
//                       WHERE Post_id IN ($placeholders)";

//         $media_stmt = $pdo->prepare($media_sql);
//         $media_stmt->execute($postIds);
//         $mediaRows = $media_stmt->fetchAll(PDO::FETCH_ASSOC);

//         $mediaMap = [];
//         foreach ($mediaRows as $row) {
//             $mediaMap[$row['Post_id']][] = [
//                 'Media_url'  => $row['Media_url'],
//                 'Media_type' => $row['Media_type']
//             ];
//         }

//         foreach ($posts as &$post) {
//             $post['media'] = $mediaMap[$post['id']] ?? [];
//         }
//         unset($post);
//     }

//     /* =========================
//        4️⃣ Return JSON
//        ========================= */
//     echo json_encode([
//         "success" => true,
//         "page" => $page,
//         "posts" => $posts,
//         "total_posts" => $totalPosts,
//         "total_pages" => $totalPages
//     ], JSON_UNESCAPED_SLASHES);

// } catch (Exception $e) {
//     http_response_code(500);
//     echo json_encode([
//         "success" => false,
//         "error" => $e->getMessage()
//     ]);
// }


require_once "cors.php";
require_once "db_connect.php";
require_once "auth.php"; // provides $user_uuid

header('Content-Type: application/json');

/* =========================
   CONFIG
========================= */

$type  = $_GET['type'] ?? 'normal';
$limit = 6;

/* Cursor Pagination */
$last_created_at = $_GET['last_created_at'] ?? null;
$last_id         = $_GET['last_id'] ?? null;

/* =========================
   1️⃣ Fetch feed posts
========================= */

$sql = "SELECT
    p.id,
    p.user_uuid,
    p.Username,
    p.Description,
    p.Created_at,
    u.Profile_photo,
    p.like_count,
    p.comment_count,

    /* Fast existence checks */

    EXISTS (
        SELECT 1
        FROM likes l2
        WHERE l2.post_id = p.id
        AND l2.user_uuid = :uuid_like
    ) AS is_liked,

    EXISTS (
        SELECT 1
        FROM savedposts s
        WHERE s.post_id = p.id
        AND s.user_uuid = :uuid_save
    ) AS is_saved,

    EXISTS (
        SELECT 1
        FROM follows f
        WHERE f.following_uuid = p.user_uuid
        AND f.follower_uuid = :uuid_follow
    ) AS is_following

FROM posts p

LEFT JOIN users u
    ON u.user_uuid = p.user_uuid

WHERE p.type = :type

AND (
    :last_created_at IS NULL
    OR (
        p.Created_at < :last_created_at
        OR (
            p.Created_at = :last_created_at
            AND p.id < :last_id
        )
    )
)

ORDER BY
    is_following DESC,
    /* This makes posts lose value FAST, but they stay in the system */
    (
        (p.like_count + p.comment_count * 2) 
        / 
        POW((UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(p.Created_at)) / 3600 + 1, 2.0)
    ) DESC,
    p.Created_at DESC,
    p.id DESC

LIMIT :limit
";

try {

    $stmt = $pdo->prepare($sql);

    /* Bind parameters */

    $stmt->bindValue(':uuid_like', $user_uuid, PDO::PARAM_STR);
    $stmt->bindValue(':uuid_save', $user_uuid, PDO::PARAM_STR);
    $stmt->bindValue(':uuid_follow', $user_uuid, PDO::PARAM_STR);

    $stmt->bindValue(':type', $type, PDO::PARAM_STR);

    $stmt->bindValue(':last_created_at',
        $last_created_at,
        PDO::PARAM_STR
    );

    $stmt->bindValue(':last_id',
        $last_id,
        PDO::PARAM_INT
    );

    $stmt->bindValue(':limit',
        $limit,
        PDO::PARAM_INT
    );

    $stmt->execute();

    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* =========================
       2️⃣ Attach media
    ========================= */

    $postIds = array_column($posts, 'id');

    if (!empty($postIds)) {

        $placeholders =
            implode(',', array_fill(0, count($postIds), '?'));

        $media_sql = "SELECT
                Post_id,
                Media_url,
                Media_type
            FROM posts_media
            WHERE Post_id IN ($placeholders)
            ORDER BY Post_id
        ";

        $media_stmt = $pdo->prepare($media_sql);

        $media_stmt->execute($postIds);

        $mediaRows =
            $media_stmt->fetchAll(PDO::FETCH_ASSOC);

        $mediaMap = [];

        foreach ($mediaRows as $row) {

            $mediaMap[$row['Post_id']][] = [
                'Media_url'  => $row['Media_url'],
                'Media_type' => $row['Media_type']
            ];
        }

        foreach ($posts as &$post) {

            $post['media'] =
                $mediaMap[$post['id']] ?? [];
        }

        unset($post);
    }

    /* =========================
       3️⃣ Next Cursor
    ========================= */

    $next_cursor = null;

    if (!empty($posts)) {

        $last_post = end($posts);

        $next_cursor = [
            "last_created_at" =>
                $last_post['Created_at'],

            "last_id" =>
                $last_post['id']
        ];
    }

    /* =========================
       4️⃣ Return JSON
    ========================= */

    echo json_encode([
        "success" => true,
        "posts" => $posts,
        "next_cursor" => $next_cursor
    ],
    JSON_UNESCAPED_SLASHES
    | JSON_INVALID_UTF8_SUBSTITUTE);

} catch (Exception $e) {

    http_response_code(500);

    /* Do NOT expose internal error */

    echo json_encode([
        "success" => false,
        "error" => "Server error"
    ]);
}
