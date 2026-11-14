<?php

header("Content-Type: application/json");

require_once __DIR__ . "/../config/database.php";


/*
|--------------------------------------------------------------------------
| GET
|--------------------------------------------------------------------------
| Tanpa id:
|   /api/cases.php
|   -> mengambil semua Case
|
| Dengan id:
|   /api/cases.php?id=1
|   -> mengambil detail Case + laporan di dalamnya
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "GET") {

    $id = isset($_GET["id"])
        ? intval($_GET["id"])
        : 0;


    /*
    |--------------------------------------------------------------------------
    | Detail Case
    |--------------------------------------------------------------------------
    */

    if ($id > 0) {

        $stmt = $conn->prepare("
            SELECT *
            FROM cases
            WHERE id = ?
        ");

        $stmt->bind_param("i", $id);

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 0) {

            echo json_encode([
                "success" => false,
                "message" => "Case tidak ditemukan"
            ]);

            exit;
        }

        $case = $result->fetch_assoc();

        $stmt->close();


        /*
        |--------------------------------------------------------------------------
        | Ambil semua laporan dalam Case
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare("
            SELECT
                id,
                user_id,
                case_id,
                title,
                description,
                category,
                location,
                latitude,
                longitude,
                image,
                status,
                priority,
                ai_summary,
                ai_category,
                ai_priority,
                created_at,
                updated_at
            FROM reports
            WHERE case_id = ?
            ORDER BY created_at DESC
        ");

        $stmt->bind_param("i", $id);

        $stmt->execute();

        $reports_result = $stmt->get_result();

        $reports = [];

        while ($row = $reports_result->fetch_assoc()) {

            $reports[] = $row;

        }

        $stmt->close();


        /*
        |--------------------------------------------------------------------------
        | Response Detail Case
        |--------------------------------------------------------------------------
        */

        echo json_encode([
            "success" => true,
            "data" => [
                "case" => $case,
                "reports" => $reports,
                "report_count" => count($reports)
            ]
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Semua Case
    |--------------------------------------------------------------------------
    */

    $sql = "
        SELECT
            c.*,
            COUNT(r.id) AS report_count
        FROM cases c
        LEFT JOIN reports r
            ON r.case_id = c.id
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ";


    $result = $conn->query($sql);


    if (!$result) {

        echo json_encode([
            "success" => false,
            "message" => "Gagal mengambil data case",
            "error" => $conn->error
        ]);

        exit;
    }


    $cases = [];


    while ($row = $result->fetch_assoc()) {

        $cases[] = $row;

    }


    echo json_encode([
        "success" => true,
        "data" => $cases
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| POST
|--------------------------------------------------------------------------
| Membuat Case baru
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $input = json_decode(
        file_get_contents("php://input"),
        true
    );


    $title = $input["title"] ?? "";
    $description = $input["description"] ?? "";
    $category = $input["category"] ?? "";
    $location = $input["location"] ?? "";
    $status = $input["status"] ?? "open";
    $priority = $input["priority"] ?? "medium";


    if (
        empty($title) ||
        empty($category) ||
        empty($location)
    ) {

        echo json_encode([
            "success" => false,
            "message" => "Title, category, dan location wajib diisi"
        ]);

        exit;
    }


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


    $stmt->bind_param(
        "ssssss",
        $title,
        $description,
        $category,
        $location,
        $status,
        $priority
    );


    if ($stmt->execute()) {

        echo json_encode([
            "success" => true,
            "message" => "Case berhasil dibuat",
            "case_id" => $stmt->insert_id
        ]);

    } else {

        echo json_encode([
            "success" => false,
            "message" => "Gagal membuat case",
            "error" => $stmt->error
        ]);

    }


    $stmt->close();

    exit;
}


/*
|--------------------------------------------------------------------------
| PUT
|--------------------------------------------------------------------------
| Mengubah status / priority Case
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "PUT") {

    $input = json_decode(
        file_get_contents("php://input"),
        true
    );


    $id = $input["id"] ?? 0;

    $status = $input["status"] ?? null;
    $priority = $input["priority"] ?? null;


    if (!$id) {

        echo json_encode([
            "success" => false,
            "message" => "ID case wajib diisi"
        ]);

        exit;
    }


    $stmt = $conn->prepare("
        UPDATE cases
        SET
            status = COALESCE(?, status),
            priority = COALESCE(?, priority)
        WHERE id = ?
    ");


    $stmt->bind_param(
        "ssi",
        $status,
        $priority,
        $id
    );


    if ($stmt->execute()) {

        echo json_encode([
            "success" => true,
            "message" => "Case berhasil diperbarui"
        ]);

    } else {

        echo json_encode([
            "success" => false,
            "message" => "Gagal memperbarui case",
            "error" => $stmt->error
        ]);

    }


    $stmt->close();

    exit;
}


/*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
| Menghapus Case
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "DELETE") {

    $input = json_decode(
        file_get_contents("php://input"),
        true
    );


    $id = $input["id"] ?? 0;


    if (!$id) {

        echo json_encode([
            "success" => false,
            "message" => "ID case wajib diisi"
        ]);

        exit;
    }


    $stmt = $conn->prepare(
        "DELETE FROM cases WHERE id = ?"
    );


    $stmt->bind_param(
        "i",
        $id
    );


    if ($stmt->execute()) {

        echo json_encode([
            "success" => true,
            "message" => "Case berhasil dihapus"
        ]);

    } else {

        echo json_encode([
            "success" => false,
            "message" => "Gagal menghapus case",
            "error" => $stmt->error
        ]);

    }


    $stmt->close();

    exit;
}


/*
|--------------------------------------------------------------------------
| Method tidak didukung
|--------------------------------------------------------------------------
*/

echo json_encode([
    "success" => false,
    "message" => "Method tidak didukung"
]);


$conn->close();

?>