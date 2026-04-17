<?php
// ==============================
// auth.php – JWT Authentication
// ==============================

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php'; // contains $jwtSecret

header("Content-Type: application/json; charset=UTF-8");

// ------------------------------
// 1. Function: get Authorization header
// ------------------------------
function getAuthorizationHeader() {
    // Apache / PHP-FPM
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        return trim($_SERVER['HTTP_AUTHORIZATION']);
    }

    // Some servers
    if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        return trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    }

    // Fallback to apache_request_headers or getallheaders
    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
    } elseif (function_exists('getallheaders')) {
        $headers = getallheaders();
    } else {
        $headers = [];
    }

    foreach ($headers as $key => $value) {
        if (strtolower($key) === 'authorization') {
            return trim($value);
        }
    }

    return null;
}

// ------------------------------
// 2. Fetch header
// ------------------------------
$authHeader = getAuthorizationHeader();

if (!$authHeader || !preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Authorization token required"
    ]);
    exit;
}

$jwt = $matches[1];

// ------------------------------
// 3. Decode JWT
// ------------------------------
try {
    $decoded = JWT::decode($jwt, new Key($jwtSecret, 'HS256'));

    // Extract user info
    $user_uuid = $decoded->uuid ?? null;
    $is_admin  = !empty($decoded->is_admin) && ((int)$decoded->is_admin === 1);

    if (!$user_uuid) {
        throw new Exception("user_uuid not found in token");
    }

    // Optional: you can add other checks like expiration
    $now = time();
    if (isset($decoded->exp) && $decoded->exp < $now) {
        throw new Exception("Token expired");
    }

} catch (Exception $e) {
    // Log full error internally
    error_log("JWT error: " . $e->getMessage());

    // Return safe message to client
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Invalid token"
    ]);
    exit;
}

// ------------------------------
// 4. Now $user_uuid and $is_admin are ready for downstream code
// ------------------------------
// Example usage in another API file:
// require 'auth.php';
// echo "Hello user $user_uuid! Admin? " . ($is_admin ? "Yes" : "No");

