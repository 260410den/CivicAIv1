<?php

header("Content-Type: application/json; charset=utf-8");

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Silakan login terlebih dahulu untuk membuat laporan"
    ]);
    exit;
}

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/ai_service.php";

function sendJson($success, $message, $extra = []) {
    echo json_encode(array_merge([
        "success" => $success,
        "message" => $message
    ], $extra), JSON_PRETTY_PRINT);
    exit;
}

function getPostInput() {
    if (!empty($_POST)) {
        return $_POST;
    }

    $contentType = $_SERVER["CONTENT_TYPE"] ?? "";
    if (stripos($contentType, "application/json") !== false) {
        $rawBody = file_get_contents("php://input");
        $decoded = json_decode($rawBody, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }

    return [];
}

function refValues($arr) {
    $refs = [];
    foreach ($arr as $key => $value) {
        $refs[$key] = &$arr[$key];
    }
    return $refs;
}

function getTableColumns($conn, $tableName) {
    $result = $conn->query("SHOW COLUMNS FROM " . $tableName);
    if (!$result) {
        return [];
    }

    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row["Field"];
    }
    return $columns;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    sendJson(false, "Method harus POST");
}

$input = getPostInput();

$user_id = (int) ($_SESSION["user_id"] ?? 0);
$title = trim((string) ($input["title"] ?? ""));
$description = trim((string) ($input["description"] ?? ""));
$category = trim((string) ($input["category"] ?? ""));
$location = trim((string) ($input["location"] ?? ""));

if ($user_id <= 0 || $title === "" || $description === "" || $location === "") {
    sendJson(false, "Data wajib belum lengkap", [
        "received" => [
            "title" => $title,
            "description" => $description,
            "category" => $category,
            "location" => $location
        ]
    ]);
}

$photo_filename = null;
if (isset($_FILES["photo"]) && is_array($_FILES["photo"])) {
    $photo = $_FILES["photo"];
    if ($photo["error"] === UPLOAD_ERR_OK) {
        $fileTmpPath = $photo["tmp_name"];
        $fileName = $photo["name"];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ["jpg", "jpeg", "png", "webp"];

        if (in_array($fileExtension, $allowedExtensions, true)) {
            $newFileName = md5(time() . $fileName) . "." . $fileExtension;
            $uploadFileDir = __DIR__ . "/../uploads/";

            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }

            $destPath = $uploadFileDir . $newFileName;
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $photo_filename = $newFileName;
            }
        }
    }
}

$video_filename = null;
if (isset($_FILES["video"]) && is_array($_FILES["video"])) {
    $video = $_FILES["video"];
    if ($video["error"] === UPLOAD_ERR_OK) {
        $fileTmpPath = $video["tmp_name"];
        $fileName = $video["name"];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ["mp4", "mov", "avi", "mkv", "webm", "wmv"]; 

        if (in_array($fileExtension, $allowedExtensions, true)) {
            $newFileName = md5(time() . $fileName) . "." . $fileExtension;
            $uploadFileDir = __DIR__ . "/../uploads/";

            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }

            $destPath = $uploadFileDir . $newFileName;
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $video_filename = $newFileName;
            }
        }
    }
}

$aiAnalysis = AIService::analyzeReportText($title, $description);
$category = $aiAnalysis["category"] ?? $category ?? "Lainnya";
$priority = $aiAnalysis["recommended_priority"] ?? "medium";
$status = "pending";

$tableColumns = getTableColumns($conn, "reports");
$columns = ["user_id", "title", "description", "category", "location", "priority", "status"];
$values = [$user_id, $title, $description, $category, $location, $priority, $status];
$types = "issssss";

$aiFields = [
    "ai_summary" => $aiAnalysis["summary"] ?? null,
    "ai_category" => $aiAnalysis["problem"] ?? null,
    "ai_priority" => $priority,
    "ai_priority_score" => $aiAnalysis["priority_score"] ?? null,
    "ai_priority_reasoning" => $aiAnalysis["ai_reasoning"] ?? null,
    "ai_problem" => $aiAnalysis["problem"] ?? null,
    "ai_duration" => $aiAnalysis["duration"] ?? null,
    "ai_risk" => $aiAnalysis["risk"] ?? null,
    "ai_location_context" => $aiAnalysis["location_context"] ?? null
];
foreach ($aiFields as $field => $value) {
    if (in_array($field, $tableColumns, true)) {
        $columns[] = $field;
        $values[] = $value;
        $types .= $field === "ai_priority_score" ? "i" : "s";
    }
}

if (in_array("photo", $tableColumns, true) && $photo_filename !== null) {
    $columns[] = "photo";
    $values[] = $photo_filename;
    $types .= "s";
}

if (in_array("video", $tableColumns, true) && $video_filename !== null) {
    $columns[] = "video";
    $values[] = $video_filename;
    $types .= "s";
}

$sql = "INSERT INTO reports (" . implode(", ", $columns) . ") VALUES (" . implode(", ", array_fill(0, count($columns), "?")) . ")";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    sendJson(false, "Gagal menyiapkan statement database", [
        "error" => $conn->error
    ]);
}

$bindArgs = [$types];
foreach ($values as $value) {
    $bindArgs[] = $value;
}

call_user_func_array([$stmt, "bind_param"], refValues($bindArgs));

if ($stmt->execute()) {
    $report_id = $stmt->insert_id;
    sendJson(true, "Laporan berhasil dibuat dan dianalisis oleh AI", [
        "report_id" => $report_id,
        "photo_uploaded" => $photo_filename ? true : false,
        "video_uploaded" => $video_filename ? true : false,
        "ai_recommendation" => [
            "priority" => $priority,
            "score" => $aiAnalysis["priority_score"] ?? null,
            "reasoning" => $aiAnalysis["ai_reasoning"] ?? ""
        ]
    ]);
}

sendJson(false, "Gagal menyimpan laporan ke database", [
    "error" => $stmt->error
]);

$stmt->close();
$conn->close();
?>
