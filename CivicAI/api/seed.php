<?php

header("Content-Type: application/json");

require_once __DIR__ . "/../config/database.php";


/*
|--------------------------------------------------------------------------
| Seed Data CivicAI
|--------------------------------------------------------------------------
| Membuat data dummy untuk prototype.
|
| Aman dijalankan berkali-kali karena setiap data memiliki
| judul khusus dan tidak akan dibuat dua kali.
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| Data Case
|--------------------------------------------------------------------------
*/

$cases = [

    [
        "title" => "Jalan Rusak di Jl. Contoh Jakarta",
        "description" => "Terdapat beberapa bagian jalan yang rusak dan berlubang sehingga mengganggu pengguna jalan.",
        "category" => "Infrastruktur",
        "location" => "Jl. Contoh Jakarta",
        "status" => "open",
        "priority" => "high"
    ],

    [
        "title" => "Lampu Jalan Mati di Kawasan Permukiman",
        "description" => "Beberapa lampu penerangan jalan tidak menyala pada malam hari.",
        "category" => "Penerangan Jalan",
        "location" => "Jl. Melati Jakarta",
        "status" => "open",
        "priority" => "medium"
    ],

    [
        "title" => "Sampah Menumpuk di Area Publik",
        "description" => "Tumpukan sampah terlihat di area publik dan belum mendapatkan penanganan.",
        "category" => "Kebersihan",
        "location" => "Jl. Mawar Jakarta",
        "status" => "open",
        "priority" => "high"
    ],

    [
        "title" => "Saluran Air Tersumbat",
        "description" => "Saluran air tersumbat oleh sampah dan material lain sehingga aliran air tidak lancar.",
        "category" => "Lingkungan",
        "location" => "Jl. Kenanga Jakarta",
        "status" => "open",
        "priority" => "high"
    ],

    [
        "title" => "Trotoar Rusak",
        "description" => "Beberapa bagian trotoar mengalami kerusakan dan menyulitkan pejalan kaki.",
        "category" => "Infrastruktur",
        "location" => "Jl. Anggrek Jakarta",
        "status" => "open",
        "priority" => "medium"
    ],

    [
        "title" => "Genangan Air Setelah Hujan",
        "description" => "Terjadi genangan air di jalan setelah hujan dengan durasi yang cukup lama.",
        "category" => "Lingkungan",
        "location" => "Jl. Flamboyan Jakarta",
        "status" => "open",
        "priority" => "high"
    ]

];


/*
|--------------------------------------------------------------------------
| Simpan Case
|--------------------------------------------------------------------------
*/

$case_ids = [];


foreach ($cases as $case) {


    /*
    |--------------------------------------------------------------------------
    | Cek apakah Case sudah ada
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        SELECT id
        FROM cases
        WHERE title = ?
        LIMIT 1
    ");

    $stmt->bind_param(
        "s",
        $case["title"]
    );

    $stmt->execute();

    $result = $stmt->get_result();


    if ($result->num_rows > 0) {

        $row = $result->fetch_assoc();

        $case_ids[$case["title"]] = $row["id"];

        $stmt->close();

        continue;
    }


    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | Buat Case baru
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


    $stmt->bind_param(
        "ssssss",
        $case["title"],
        $case["description"],
        $case["category"],
        $case["location"],
        $case["status"],
        $case["priority"]
    );


    if ($stmt->execute()) {

        $case_ids[$case["title"]] = $stmt->insert_id;

    }


    $stmt->close();

}


/*
|--------------------------------------------------------------------------
| Data Laporan Dummy
|--------------------------------------------------------------------------
*/

$reports = [

    /*
    |--------------------------------------------------------------------------
    | Jalan Rusak
    |--------------------------------------------------------------------------
    */

    [
        "case_title" => "Jalan Rusak di Jl. Contoh Jakarta",
        "title" => "Lubang besar di jalan",
        "description" => "Terdapat lubang cukup besar di badan jalan yang mengganggu kendaraan.",
        "category" => "Infrastruktur",
        "location" => "Jl. Contoh Jakarta",
        "priority" => "high"
    ],

    [
        "case_title" => "Jalan Rusak di Jl. Contoh Jakarta",
        "title" => "Permukaan jalan rusak",
        "description" => "Permukaan jalan terlihat rusak dan tidak rata di beberapa bagian.",
        "category" => "Infrastruktur",
        "location" => "Jl. Contoh Jakarta",
        "priority" => "medium"
    ],

    [
        "case_title" => "Jalan Rusak di Jl. Contoh Jakarta",
        "title" => "Jalan berlubang dekat persimpangan",
        "description" => "Ada lubang pada jalan yang berada dekat persimpangan dan cukup mengganggu pengguna jalan.",
        "category" => "Infrastruktur",
        "location" => "Jl. Contoh Jakarta",
        "priority" => "high"
    ],

    [
        "case_title" => "Jalan Rusak di Jl. Contoh Jakarta",
        "title" => "Aspal jalan mulai rusak",
        "description" => "Bagian aspal jalan mulai rusak dan terdapat beberapa retakan.",
        "category" => "Infrastruktur",
        "location" => "Jl. Contoh Jakarta",
        "priority" => "medium"
    ],


    /*
    |--------------------------------------------------------------------------
    | Lampu Jalan
    |--------------------------------------------------------------------------
    */

    [
        "case_title" => "Lampu Jalan Mati di Kawasan Permukiman",
        "title" => "Lampu jalan tidak menyala",
        "description" => "Lampu penerangan jalan tidak menyala pada malam hari.",
        "category" => "Penerangan Jalan",
        "location" => "Jl. Melati Jakarta",
        "priority" => "medium"
    ],

    [
        "case_title" => "Lampu Jalan Mati di Kawasan Permukiman",
        "title" => "Penerangan jalan mati sejak malam",
        "description" => "Beberapa lampu jalan terlihat mati sehingga area menjadi cukup gelap.",
        "category" => "Penerangan Jalan",
        "location" => "Jl. Melati Jakarta",
        "priority" => "medium"
    ],

    [
        "case_title" => "Lampu Jalan Mati di Kawasan Permukiman",
        "title" => "Lampu PJU rusak",
        "description" => "Salah satu lampu penerangan jalan umum mengalami kerusakan.",
        "category" => "Penerangan Jalan",
        "location" => "Jl. Melati Jakarta",
        "priority" => "low"
    ],


    /*
    |--------------------------------------------------------------------------
    | Sampah
    |--------------------------------------------------------------------------
    */

    [
        "case_title" => "Sampah Menumpuk di Area Publik",
        "title" => "Tumpukan sampah di pinggir jalan",
        "description" => "Terlihat tumpukan sampah yang belum diangkut di pinggir jalan.",
        "category" => "Kebersihan",
        "location" => "Jl. Mawar Jakarta",
        "priority" => "high"
    ],

    [
        "case_title" => "Sampah Menumpuk di Area Publik",
        "title" => "Sampah menumpuk dekat fasilitas umum",
        "description" => "Sampah terlihat menumpuk di sekitar area fasilitas umum.",
        "category" => "Kebersihan",
        "location" => "Jl. Mawar Jakarta",
        "priority" => "high"
    ],

    [
        "case_title" => "Sampah Menumpuk di Area Publik",
        "title" => "Tempat sampah penuh",
        "description" => "Tempat sampah sudah penuh dan sebagian sampah berada di luar tempat penampungan.",
        "category" => "Kebersihan",
        "location" => "Jl. Mawar Jakarta",
        "priority" => "medium"
    ],


    /*
    |--------------------------------------------------------------------------
    | Saluran Air
    |--------------------------------------------------------------------------
    */

    [
        "case_title" => "Saluran Air Tersumbat",
        "title" => "Selokan tersumbat sampah",
        "description" => "Saluran air terlihat tersumbat oleh sampah sehingga aliran air menjadi tidak lancar.",
        "category" => "Lingkungan",
        "location" => "Jl. Kenanga Jakarta",
        "priority" => "high"
    ],

    [
        "case_title" => "Saluran Air Tersumbat",
        "title" => "Air tidak mengalir di selokan",
        "description" => "Aliran air pada saluran terlihat tidak lancar dan menyebabkan air menggenang.",
        "category" => "Lingkungan",
        "location" => "Jl. Kenanga Jakarta",
        "priority" => "high"
    ],


    /*
    |--------------------------------------------------------------------------
    | Trotoar
    |--------------------------------------------------------------------------
    */

    [
        "case_title" => "Trotoar Rusak",
        "title" => "Trotoar pecah",
        "description" => "Beberapa bagian trotoar mengalami kerusakan dan permukaannya tidak rata.",
        "category" => "Infrastruktur",
        "location" => "Jl. Anggrek Jakarta",
        "priority" => "medium"
    ],

    [
        "case_title" => "Trotoar Rusak",
        "title" => "Trotoar berlubang",
        "description" => "Terdapat bagian trotoar yang berlubang sehingga dapat mengganggu pejalan kaki.",
        "category" => "Infrastruktur",
        "location" => "Jl. Anggrek Jakarta",
        "priority" => "medium"
    ],


    /*
    |--------------------------------------------------------------------------
    | Genangan
    |--------------------------------------------------------------------------
    */

    [
        "case_title" => "Genangan Air Setelah Hujan",
        "title" => "Genangan di badan jalan",
        "description" => "Air menggenang di badan jalan setelah hujan dan belum surut.",
        "category" => "Lingkungan",
        "location" => "Jl. Flamboyan Jakarta",
        "priority" => "high"
    ],

    [
        "case_title" => "Genangan Air Setelah Hujan",
        "title" => "Air menggenang cukup lama",
        "description" => "Genangan air masih terlihat beberapa waktu setelah hujan berhenti.",
        "category" => "Lingkungan",
        "location" => "Jl. Flamboyan Jakarta",
        "priority" => "high"
    ]

];


/*
|--------------------------------------------------------------------------
| Masukkan Laporan
|--------------------------------------------------------------------------
*/

$inserted_reports = 0;
$skipped_reports = 0;


foreach ($reports as $report) {


    $case_id =
        $case_ids[$report["case_title"]]
        ?? 0;


    if (!$case_id) {
        continue;
    }


    /*
    |--------------------------------------------------------------------------
    | Cek laporan agar tidak duplicate
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        SELECT id
        FROM reports
        WHERE title = ?
        AND location = ?
        LIMIT 1
    ");


    $stmt->bind_param(
        "ss",
        $report["title"],
        $report["location"]
    );


    $stmt->execute();

    $result = $stmt->get_result();


    if ($result->num_rows > 0) {

        $skipped_reports++;

        $stmt->close();

        continue;
    }


    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | Insert laporan
    |--------------------------------------------------------------------------
    */

    $status = "pending";

    $user_id = 1;


    $stmt = $conn->prepare("
        INSERT INTO reports
        (
            user_id,
            case_id,
            title,
            description,
            category,
            location,
            status,
            priority
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");


    $stmt->bind_param(
        "iissssss",
        $user_id,
        $case_id,
        $report["title"],
        $report["description"],
        $report["category"],
        $report["location"],
        $status,
        $report["priority"]
    );


    if ($stmt->execute()) {

        $inserted_reports++;

    }


    $stmt->close();

}


/*
|--------------------------------------------------------------------------
| Response
|--------------------------------------------------------------------------
*/

echo json_encode([
    "success" => true,
    "message" => "Seed data CivicAI berhasil diproses",
    "cases" => count($case_ids),
    "reports_added" => $inserted_reports,
    "reports_skipped_duplicate" => $skipped_reports
], JSON_PRETTY_PRINT);


$conn->close();

?>