<?php
// 1. Setup - MUST BE AT THE TOP
header('Content-Type: application/json');
require_once 'cors.php';
require_once 'auth.php';       // Defines $user_uuid
require_once 'db_connect.php'; // Defines $pdo

try {
    /* =====================================
       INPUT
    ===================================== */
    $username = trim($_POST['Username'] ?? '');
    $phone    = trim($_POST['Phone'] ?? '');
    $year     = trim($_POST['Year'] ?? '');
    $location  = trim($_POST['Location'] ?? '');

    if (!$username) {
        echo json_encode(["success" => false, "message" => "Username required"]);
        exit;
    }

    if (!isset($user_uuid)) {
        echo json_encode(["success" => false, "message" => "User session not found"]);
        exit;
    }

    /* =====================================
       PROFILE PHOTO UPLOAD (optional)
    ===================================== */
    $photoPath = null;

    if (!empty($_FILES['Profile_photo']['name'])) {
        $allowedTypes = ['image/*'];
        $maxSize = 1 * 1024 * 1024; // 1MB

        if (!in_array($_FILES['Profile_photo']['type'], $allowedTypes)) {
            echo json_encode(["success" => false, "message" => "Invalid image type: "]);
            exit;
        }

        if ($_FILES['Profile_photo']['size'] > $maxSize) {
            echo json_encode(["success" => false, "message" => "Image too large"]);
            exit;
        }

        $uploadDir = "uploads/profile/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $ext = pathinfo($_FILES['Profile_photo']['name'], PATHINFO_EXTENSION);
        $fileName = $user_uuid . "_" . time() . "." . $ext;
        $photoPath = $uploadDir . $fileName;

        if (!move_uploaded_file($_FILES['Profile_photo']['tmp_name'], $photoPath)) {
            echo json_encode(["success" => false, "message" => "Failed to move uploaded file"]);
            exit;
        }
    }

    /* =====================================
       UPDATE QUERY
    ===================================== */
    $sql = "UPDATE users SET Username = ?, Phone = ?, Year = ?, Location = ?";
    $params = [$username, $phone, $year, $location];

    if ($photoPath) {
        $sql .= ", Profile_photo = ?";
        $params[] = $photoPath;
    }

    $sql .= " WHERE user_uuid = ? LIMIT 1";
    $params[] = $user_uuid;

    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute($params);

    echo json_encode([
        "success" => $success,
        "message" => $success ? "Profile updated successfully" : "No changes made or update failed",
        "profile_photo" => $photoPath
    ]);

} catch (Exception $e) {
    // log error internally instead
    error_log($e->getMessage());

    echo json_encode([
        "success" => false,
        "message" => "Internal server error"
    ]);
}


