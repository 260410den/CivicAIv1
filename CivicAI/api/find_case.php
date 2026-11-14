<?php

header("Content-Type: application/json");

require_once __DIR__ . "/../config/database.php";


/*
|--------------------------------------------------------------------------
| Ambil input
|--------------------------------------------------------------------------
*/

$category = "";
$location = "";


/*
|--------------------------------------------------------------------------
| GET
| Contoh:
| find_case.php?category=Infrastruktur&location=Jl.%20Contoh%20Jakarta
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "GET") {

    $category = trim($_GET["category"] ?? "");
    $location = trim($_GET["location"] ?? "");

}


/*
|--------------------------------------------------------------------------
| POST
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $input = json_decode(
        file_get_contents("php://input"),
        true
    );

    $category = trim($input["category"] ?? "");
    $location = trim($input["location"] ?? "");

}


/*
|--------------------------------------------------------------------------
| Validasi
|--------------------------------------------------------------------------
*/

if ($category === "" || $location === "") {

    echo json_encode([
        "success" => false,
        "message" => "Category dan location wajib diisi"
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Cari Case
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
        created_at,
        updated_at
    FROM cases
    WHERE category = ?
    AND location = ?
    ORDER BY created_at DESC
");

$stmt->bind_param(
    "ss",
    $category,
    $location
);

$stmt->execute();

$result = $stmt->get_result();

$cases = [];

while ($row = $result->fetch_assoc()) {
    $cases[] = $row;
}

$stmt->close();


/*
|--------------------------------------------------------------------------
| Response
|--------------------------------------------------------------------------
*/

if (count($cases) === 0) {

    echo json_encode([
        "success" => true,
        "message" => "Case tidak ditemukan",
        "data" => []
    ]);

    exit;
}


echo json_encode([
    "success" => true,
    "message" => "Case ditemukan",
    "data" => $cases
]);


$conn->close();

?>