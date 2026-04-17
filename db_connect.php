<?php

// admin.php - fetch and return rows from admintable as JSON
$host = '127.0.0.1';
$db   = 'uniyatwoon_lts';
$user = 'uniyatwon';        // your PHP MySQL user
$pass = '8Gv#3kL!xP9qR@8Z'; // password for uniuser
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => 'Database connection failed.',
        'details' => $e->getMessage() // optional for debugging
    ]);
    exit;
}