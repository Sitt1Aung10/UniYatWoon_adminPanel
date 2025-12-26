<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With, Accept");

ini_set('session.cookie_path', '/');
session_start();

if (isset($_SESSION['Username'])) {
    echo json_encode([
        "success" => true,
        "Username" => $_SESSION['Username'],
        "user_uuid" => isset($_SESSION['user_uuid']) ? $_SESSION['user_uuid'] : null
    ]);
} else {
    echo json_encode([
        "success" => false
    ]);
}
exit;
