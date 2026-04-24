<?php
require_once 'cors.php';
require_once 'auth.php';
require_once 'db_connect.php';

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method not allowed"
    ]);
    exit;
}

function ensureEmailVerificationTable(PDO $pdo): void
{
    $sql = "
        CREATE TABLE IF NOT EXISTS email_verifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_uuid CHAR(36) NOT NULL,
            email VARCHAR(255) NOT NULL,
            purpose VARCHAR(50) NOT NULL DEFAULT 'password_change',
            code_hash VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL,
            verified_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_purpose (user_uuid, purpose),
            INDEX idx_expires_at (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    $pdo->exec($sql);
}

try {
    if (!isset($user_uuid) || trim((string)$user_uuid) === '') {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Invalid token"
        ]);
        exit;
    }

    ensureEmailVerificationTable($pdo);

    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        $data = $_POST;
    }

    $code = trim((string)($data['verification_code'] ?? $data['code'] ?? ''));
    if ($code === '') {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Verification code is required"
        ]);
        exit;
    }

    $purpose = 'password_change';
    $stmt = $pdo->prepare("
        SELECT id, code_hash, expires_at, verified_at
        FROM email_verifications
        WHERE user_uuid = ? AND purpose = ?
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute([$user_uuid, $purpose]);
    $verification = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$verification) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "No verification request found"
        ]);
        exit;
    }

    if (!empty($verification['verified_at'])) {
        echo json_encode([
            "success" => true,
            "message" => "Email already verified"
        ]);
        exit;
    }

    if (strtotime((string)$verification['expires_at']) < time()) {
        $pdo->prepare("DELETE FROM email_verifications WHERE id = ?")->execute([$verification['id']]);

        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Verification code has expired"
        ]);
        exit;
    }

    if (!password_verify($code, (string)$verification['code_hash'])) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid verification code"
        ]);
        exit;
    }

    $update = $pdo->prepare("
        UPDATE email_verifications
        SET verified_at = NOW()
        WHERE id = ?
    ");
    $update->execute([$verification['id']]);

    echo json_encode([
        "success" => true,
        "message" => "Email verified successfully"
    ]);
} catch (Throwable $e) {
    error_log("verify_otp.php error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Internal server error"
    ]);
}
