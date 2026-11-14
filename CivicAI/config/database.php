<?php
declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_OFF);

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = (int) (getenv('DB_PORT') ?: 3306);
$dbUser = getenv('DB_USER') ?: 'root';
$dbPassword = getenv('DB_PASSWORD') ?: 'root';
$dbName = getenv('DB_NAME') ?: 'civicai';

$conn = @new mysqli($dbHost, $dbUser, $dbPassword, $dbName, $dbPort);

if ($conn->connect_error) {
    error_log('CivicAI database connection failed: ' . $conn->connect_error);
    http_response_code(500);
    if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Database belum terhubung. Periksa konfigurasi DB_HOST, DB_USER, DB_PASSWORD, dan DB_NAME.']);
    } else {
        echo 'Database belum terhubung. Periksa konfigurasi lingkungan CivicAI.';
    }
    exit;
}

$conn->set_charset('utf8mb4');
