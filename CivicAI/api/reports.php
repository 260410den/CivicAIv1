<?php

error_reporting(E_ALL);
ini_set('display_errors', '0');

header("Content-Type: application/json");

require_once __DIR__ . "/../config/database.php";

$sql = "SELECT * FROM reports ORDER BY created_at DESC";

$result = $conn->query($sql);

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode([
    "success" => true,
    "data" => $data
]);

$conn->close();

?>
