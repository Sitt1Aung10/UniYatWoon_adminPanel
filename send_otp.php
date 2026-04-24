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

function sendOtpEmail(string $email, string $code): bool
{
    $subject = "Your password change verification code";
    $messageLines = [
        "Hello,",
        "",
        "Your verification code is: " . $code,
        "This code will expire in 10 minutes.",
        "",
        "If you did not request this, please ignore this email."
    ];

    $message = implode("\r\n", $messageLines);
    $headers = [
        "MIME-Version: 1.0",
        "Content-Type: text/plain; charset=UTF-8",
        "From: UniYatWoon <no-reply@uniyatwoon.local>"
    ];

    return mail($email, $subject, $message, implode("\r\n", $headers));
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

    $stmt = $pdo->prepare("
        SELECT Email, Username
        FROM users
        WHERE user_uuid = ?
        LIMIT 1
    ");
    $stmt->execute([$user_uuid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "User not found"
        ]);
        exit;
    }

    $email = strtolower(trim((string)($user['Email'] ?? '')));
    if ($email === '') {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "No email address found for this account"
        ]);
        exit;
    }

    $purpose = 'password_change';
    $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $codeHash = password_hash($code, PASSWORD_DEFAULT);
    $expiresAt = date('Y-m-d H:i:s', time() + (10 * 60));

    $pdo->prepare("
        DELETE FROM email_verifications
        WHERE user_uuid = ? AND purpose = ?
    ")->execute([$user_uuid, $purpose]);

    $insert = $pdo->prepare("
        INSERT INTO email_verifications (user_uuid, email, purpose, code_hash, expires_at)
        VALUES (?, ?, ?, ?, ?)
    ");
    $insert->execute([$user_uuid, $email, $purpose, $codeHash, $expiresAt]);

    if (!sendOtpEmail($email, $code)) {
        $pdo->prepare("
            DELETE FROM email_verifications
            WHERE user_uuid = ? AND purpose = ?
        ")->execute([$user_uuid, $purpose]);

        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Failed to send verification email"
        ]);
        exit;
    }

    echo json_encode([
        "success" => true,
        "message" => "Verification code sent successfully"
    ]);
} catch (Throwable $e) {
    error_log("send_otp.php error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Internal server error"
    ]);
}

