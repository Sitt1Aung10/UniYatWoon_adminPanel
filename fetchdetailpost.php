<?php
require_once "cors.php";
require_once "db_connect.php";
require_once "auth.php"; // provides $user_uuid

header('Content-Type: application/json');

$post_id = $_GET['post_id'] ?? null;

if (!$post_id) {
    echo json_encode([
        "success" => false,
        "message" => "post_id is required"
    ]);
    exit;
}

try {

    /* =========================
       1️⃣ Fetch Single Post
       ========================= */

    $sql = "SELECT
        p.id,
        p.user_uuid,
        u.Username,
        u.Profile_photo,
        p.Description,
        p.like_count,
        p.comment_count,
        p.Created_at,

        MAX(CASE 
            WHEN l.user_uuid = :uuid_like 
            THEN 1 ELSE 0 
        END) AS is_liked,

        MAX(CASE 
            WHEN s.user_uuid = :uuid_save 
            THEN 1 ELSE 0 
        END) AS is_saved,

        CASE 
            WHEN f.follower_uuid IS NOT NULL 
            THEN 1 ELSE 0 
        END AS is_following

    FROM posts p

    LEFT JOIN users u 
        ON u.user_uuid = p.user_uuid

    LEFT JOIN likes l 
        ON l.post_id = p.id

    LEFT JOIN savedposts s 
        ON s.post_id = p.id

    LEFT JOIN follows f 
        ON f.following_uuid = p.user_uuid
       AND f.follower_uuid = :uuid_follow

    WHERE p.id = :post_id

    GROUP BY p.id
    LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->bindValue(':uuid_like', $user_uuid, PDO::PARAM_STR);
    $stmt->bindValue(':uuid_save', $user_uuid, PDO::PARAM_STR);
    $stmt->bindValue(':uuid_follow', $user_uuid, PDO::PARAM_STR);
    $stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);

    $stmt->execute();

    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        echo json_encode([
            "success" => false,
            "message" => "Post not found"
        ]);
        exit;
    }

    /* =========================
       2️⃣ Attach Media
       ========================= */

    $media_sql = "SELECT 
            Media_url,
            Media_type
        FROM posts_media
        WHERE Post_id = :post_id
    ";

    $media_stmt = $pdo->prepare($media_sql);
    $media_stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
    $media_stmt->execute();

    $post['media'] =
        $media_stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];



    /* =========================
       3️⃣ Fetch Comments
       (Most Important Fix)
       ========================= */

    $comments_sql = "SELECT
            c.id,
            c.post_id,
            c.user_uuid,
            u.Username,
            u.Profile_photo,
            c.Description,
            c.created_at

        FROM comments c

        LEFT JOIN users u
            ON u.user_uuid = c.user_uuid

        WHERE c.post_id = :post_id

        ORDER BY c.created_at DESC
        LIMIT 50
    ";

    $comments_stmt = $pdo->prepare($comments_sql);
    $comments_stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
    $comments_stmt->execute();

    $post['comments'] =
        $comments_stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];



    /* =========================
       4️⃣ Return JSON
       ========================= */

    echo json_encode([
        "success" => true,
        "post" => $post
    ], JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);

}