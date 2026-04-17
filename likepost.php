<?php
// require_once 'cors.php';
// require_once 'db_connect.php';
// require_once 'auth.php'; // $user_uuid comes from JWT

// /* =========================
//    1️⃣ Validate input
//    ========================= */
// $raw  = file_get_contents("php://input");
// $data = json_decode($raw, true);

// $post_id = $data['post_id'] ?? null;
// if (!$post_id) {
//     echo json_encode(["success" => false, "message" => "post_id required"]);
//     exit;
// }

// /* =========================
//    2️⃣ Check if post exists and get post owner
//    ========================= */
// $stmt = $pdo->prepare("SELECT id, User_uuid AS owner_uuid, Description FROM posts WHERE id = ? LIMIT 1");
// $stmt->execute([$post_id]);
// $post = $stmt->fetch(PDO::FETCH_ASSOC);

// if (!$post) {
//     echo json_encode(["success" => false, "message" => "Post not found"]);
//     exit;
// }

// $post_owner = $post['owner_uuid'];

// /* =========================
//    3️⃣ Check if user already liked
//    ========================= */
// $stmt = $pdo->prepare("SELECT id FROM likes WHERE post_id = ? AND user_uuid = ? LIMIT 1");
// $stmt->execute([$post_id, $user_uuid]);
// $like = $stmt->fetch(PDO::FETCH_ASSOC);

// if ($like) {
//     // Already liked → unlike
//     $stmt = $pdo->prepare("DELETE FROM likes WHERE id = ?");
//     $stmt->execute([$like['id']]);
//     $action = "unliked";

//     $stmt = $pdo->prepare("UPDATE posts
//     SET like_count = GREATEST(like_count - 1, 0)
//     WHERE id = ?");
//     $stmt->execute([$post_id]);
// } else {
//     // Add like
//     $stmt = $pdo->prepare("INSERT INTO likes (post_id, user_uuid) VALUES (?, ?)");
//     $stmt->execute([$post_id, $user_uuid]);
//     $action = "liked";

//     $stmt = $pdo->prepare("UPDATE posts
//     SET like_count = like_count + 1
//     WHERE id = ?");
//     $stmt->execute([$post_id]);


//     /* =========================
//        4️⃣ Insert notification if liking someone else's post
//        ========================= */
//     if ($post_owner !== $user_uuid) {
//     // fetch liker username
//     $stmtUser = $pdo->prepare("SELECT Username FROM users WHERE user_uuid = ? LIMIT 1");
//     $stmtUser->execute([$user_uuid]);
//     $liker = $stmtUser->fetch(PDO::FETCH_ASSOC);
//     $likerName = $liker['Username'] ?? 'Someone';

//     $postDesc = trim($post['Description'] ?? '');
//     $postSnippet = '';
//     if ($postDesc !== '') {
//         $postSnippet = mb_substr($postDesc, 0, 120, 'UTF-8');
//         $postSnippet = str_replace(["\r", "\n"], ' ', $postSnippet);
//         $postSnippet = str_replace('"', "'", $postSnippet);
//     }

//     if ($postSnippet !== '') {
//         $message = "Your post \"$postSnippet\" was liked by $likerName";
//     } else {
//         $message = "Your post was liked by $likerName";
//     }

//     $stmt = $pdo->prepare(
//         "INSERT INTO notifications (user_uuid, from_uuid, post_id, type, message) 
//          VALUES (?, ?, ?, 'like', ?)"
//     );
//     $stmt->execute([$post_owner, $user_uuid, $post_id, $message]);
// }

// }

// /* =========================
//    5️⃣ Return updated like count
//    ========================= */
// $stmt = $pdo->prepare("SELECT COUNT(*) AS like_count FROM likes WHERE post_id = ?");
// $stmt->execute([$post_id]);
// $count = $stmt->fetch(PDO::FETCH_ASSOC)['like_count'] ?? 0;

// echo json_encode([
//     "success" => true,
//     "action" => $action,
//     "like_count" => $count
// ]);
// exit;


require_once 'cors.php';
require_once 'db_connect.php';
require_once 'auth.php'; // $user_uuid comes from JWT

/* =========================
   1️⃣ Validate input
   ========================= */
$raw  = file_get_contents("php://input");
$data = json_decode($raw, true);

$post_id = $data['post_id'] ?? null;

if (!$post_id) {
    echo json_encode([
        "success" => false,
        "message" => "post_id required"
    ]);
    exit;
}

try {

    /* =========================
       START TRANSACTION
       ========================= */
    $pdo->beginTransaction();

    /* =========================
       2️⃣ Check if post exists
       ========================= */
    $stmt = $pdo->prepare("
        SELECT id, User_uuid AS owner_uuid, Description
        FROM posts
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$post_id]);

    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {

        $pdo->rollBack();

        echo json_encode([
            "success" => false,
            "message" => "Post not found"
        ]);
        exit;
    }

    $post_owner = $post['owner_uuid'];

    /* =========================
       3️⃣ Check existing like
       ========================= */
    $stmt = $pdo->prepare("
        SELECT id
        FROM likes
        WHERE post_id = ?
        AND user_uuid = ?
        LIMIT 1
    ");

    $stmt->execute([$post_id, $user_uuid]);

    $like = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($like) {

        /* =========================
           UNLIKE
           ========================= */
        $stmt = $pdo->prepare("
            DELETE FROM likes
            WHERE id = ?
        ");
        $stmt->execute([$like['id']]);

        /* Update counter */
        $stmt = $pdo->prepare("
            UPDATE posts
            SET like_count = GREATEST(like_count - 1, 0)
            WHERE id = ?
        ");
        $stmt->execute([$post_id]);

        $action = "unliked";

    } else {

        /* =========================
           LIKE
           ========================= */
        $stmt = $pdo->prepare("
            INSERT INTO likes (post_id, user_uuid)
            VALUES (?, ?)
        ");
        $stmt->execute([$post_id, $user_uuid]);

        /* Update counter */
        $stmt = $pdo->prepare("
            UPDATE posts
            SET like_count = like_count + 1
            WHERE id = ?
        ");
        $stmt->execute([$post_id]);

        $action = "liked";

        /* =========================
           4️⃣ Notification
           ========================= */
        if ($post_owner !== $user_uuid) {

            $stmtUser = $pdo->prepare("
                SELECT Username
                FROM users
                WHERE user_uuid = ?
                LIMIT 1
            ");

            $stmtUser->execute([$user_uuid]);

            $liker = $stmtUser->fetch(PDO::FETCH_ASSOC);
            $likerName = $liker['Username'] ?? 'Someone';

            $postDesc = trim($post['Description'] ?? '');
            $postSnippet = '';

            if ($postDesc !== '') {

                $postSnippet = mb_substr(
                    $postDesc,
                    0,
                    120,
                    'UTF-8'
                );

                $postSnippet = str_replace(
                    ["\r", "\n"],
                    ' ',
                    $postSnippet
                );

                $postSnippet = str_replace(
                    '"',
                    "'",
                    $postSnippet
                );
            }

            if ($postSnippet !== '') {

                $message =
                    "Your post \"$postSnippet\" was liked by $likerName";

            } else {

                $message =
                    "Your post was liked by $likerName";

            }

            $stmt = $pdo->prepare("
                INSERT INTO notifications 
                (user_uuid, from_uuid, post_id, type, message)
                VALUES (?, ?, ?, 'like', ?)
            ");

            $stmt->execute([
                $post_owner,
                $user_uuid,
                $post_id,
                $message
            ]);
        }
    }

    /* =========================
       COMMIT TRANSACTION
       ========================= */
    $pdo->commit();

    /* =========================
       5️⃣ Return counter
       ========================= */
    $stmt = $pdo->prepare("
        SELECT like_count
        FROM posts
        WHERE id = ?
    ");

    $stmt->execute([$post_id]);

    $count = $stmt->fetch(PDO::FETCH_ASSOC)['like_count'] ?? 0;

    echo json_encode([
        "success" => true,
        "action" => $action,
        "like_count" => (int)$count
    ]);

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        "success" => false,
        "message" => "Database error"
    ]);
}

exit;