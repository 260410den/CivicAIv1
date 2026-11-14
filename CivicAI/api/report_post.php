<?php
header("Content-Type: application/json; charset=utf-8");
session_start();

function sendReportJson($success, $message, $extra = []) {
    echo json_encode(array_merge([
        "success" => $success,
        "message" => $message
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    sendReportJson(false, "Silakan login untuk melaporkan postingan");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    sendReportJson(false, "Method harus POST");
}

require_once __DIR__ . "/../config/database.php";

$reportId = (int) ($_POST['report_id'] ?? 0);
$reporterId = (int) $_SESSION['user_id'];
$reason = trim((string) ($_POST['reason'] ?? ''));
$details = trim((string) ($_POST['details'] ?? ''));
$allowedReasons = ['Hoaks atau informasi palsu', 'Konten tidak pantas', 'Spam atau iklan', 'Pelanggaran lainnya'];

if ($reportId <= 0 || !in_array($reason, $allowedReasons, true)) {
    http_response_code(422);
    sendReportJson(false, "Postingan dan alasan laporan wajib diisi");
}

$ownerStmt = $conn->prepare("SELECT user_id FROM reports WHERE id = ?");
$ownerStmt->bind_param("i", $reportId);
$ownerStmt->execute();
$owner = $ownerStmt->get_result()->fetch_assoc();
$ownerStmt->close();

if (!$owner) {
    http_response_code(404);
    sendReportJson(false, "Postingan tidak ditemukan");
}
if ((int) ($owner['user_id'] ?? 0) === $reporterId) {
    http_response_code(403);
    sendReportJson(false, "Anda tidak dapat melaporkan postingan sendiri");
}

$insert = $conn->prepare("INSERT INTO post_reports (report_id, reporter_id, reason, details) VALUES (?, ?, ?, ?)");
$insert->bind_param("iiss", $reportId, $reporterId, $reason, $details);
if (!$insert->execute()) {
    if ($conn->errno === 1062) {
        $insert->close();
        http_response_code(409);
        sendReportJson(false, "Anda sudah melaporkan postingan ini");
    }
    $error = $insert->error;
    $insert->close();
    http_response_code(500);
    sendReportJson(false, "Laporan gagal disimpan", ["error" => $error]);
}
$insert->close();
sendReportJson(true, "Laporan postingan berhasil dikirim ke officer");
