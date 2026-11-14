<?php

header("Content-Type: application/json");

require_once __DIR__ . "/../config/database.php";

/*
|--------------------------------------------------------------------------
| Ambil input
|--------------------------------------------------------------------------
| Bisa menerima:
| 1. GET  : ?report_id=1
| 2. POST JSON : {"report_id":1}
|--------------------------------------------------------------------------
*/

$input = json_decode(
    file_get_contents("php://input"),
    true
);

$report_id = $_GET["report_id"]
    ?? ($input["report_id"] ?? 0);

$report_id = intval($report_id);

if (!$report_id) {
    echo json_encode([
        "success" => false,
        "message" => "report_id wajib diisi"
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| Cek laporan
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        id,
        title,
        description,
        category,
        location,
        status,
        priority,
        case_id
    FROM reports
    WHERE id = ?
");

$stmt->bind_param("i", $report_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Laporan tidak ditemukan"
    ]);
    exit;
}

$report = $result->fetch_assoc();
$stmt->close();

// Jika laporan sudah terhubung ke case
if (!empty($report["case_id"])) {
    echo json_encode([
        "success" => true,
        "message" => "Laporan ini sudah terhubung ke Case",
        "case_id" => $report["case_id"]
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| Buat Case baru dari laporan
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    INSERT INTO cases
    (
        title,
        description,
        category,
        location,
        status,
        priority
    )
    VALUES (?, ?, ?, ?, ?, ?)
");

$case_status = "open";

$stmt->bind_param(
    "ssssss",
    $report["title"],
    $report["description"],
    $report["category"],
    $report["location"],
    $case_status,
    $report["priority"]
);

if (!$stmt->execute()) {
    echo json_encode([
        "success" => false,
        "message" => "Gagal membuat Case",
        "error" => $stmt->error
    ]);
    exit;
}

$case_id = $stmt->insert_id;
$stmt->close();

/*
|--------------------------------------------------------------------------
| Hubungkan laporan dengan Case
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    UPDATE reports
    SET case_id = ?
    WHERE id = ?
");

$stmt->bind_param(
    "ii",
    $case_id,
    $report_id
);

if (!$stmt->execute()) {
    echo json_encode([
        "success" => false,
        "message" => "Case berhasil dibuat tetapi gagal menghubungkan laporan",
        "case_id" => $case_id,
        "error" => $stmt->error
    ]);
    exit;
}

$stmt->close();
$conn->close();

echo json_encode([
    "success" => true,
    "message" => "Case berhasil dibuat dan laporan berhasil dihubungkan",
    "case_id" => $case_id,
    "report_id" => $report_id
]);

?>