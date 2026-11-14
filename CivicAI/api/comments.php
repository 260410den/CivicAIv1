<?php

header("Content-Type: application/json");
session_start();

require_once __DIR__ . "/../config/database.php";


/*
|--------------------------------------------------------------------------
| GET
|--------------------------------------------------------------------------
| Mengambil komentar berdasarkan case_id
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "GET") {

    $case_id = intval($_GET["case_id"] ?? 0);


    if ($case_id <= 0) {

        echo json_encode([
            "success" => false,
            "message" => "case_id wajib diisi"
        ]);

        exit;
    }


    $stmt = $conn->prepare("
        SELECT
            c.id,
            c.user_id,
            c.case_id,
            c.comment,
            c.created_at
        FROM comments c
        WHERE c.case_id = ?
        ORDER BY c.created_at ASC
    ");


    $stmt->bind_param(
        "i",
        $case_id
    );


    $stmt->execute();

    $result = $stmt->get_result();

    $comments = [];


    /*
    |--------------------------------------------------------------------------
    | Ambil komentar
    |--------------------------------------------------------------------------
    */

    while ($row = $result->fetch_assoc()) {

        $user_id = intval($row["user_id"]);

        $user_name = "User #" . $user_id;


        /*
        |--------------------------------------------------------------------------
        | Cari data user
        |--------------------------------------------------------------------------
        */

        $user_stmt = $conn->prepare("
            SELECT *
            FROM users
            WHERE id = ?
            LIMIT 1
        ");


        $user_stmt->bind_param(
            "i",
            $user_id
        );


        $user_stmt->execute();

        $user_result = $user_stmt->get_result();


        if ($user_result->num_rows > 0) {

            $user = $user_result->fetch_assoc();


            /*
            |--------------------------------------------------------------------------
            | Cari nama berdasarkan kolom yang tersedia
            |--------------------------------------------------------------------------
            */

            if (
                isset($user["name"]) &&
                $user["name"] !== ""
            ) {

                $user_name = $user["name"];

            } elseif (
                isset($user["username"]) &&
                $user["username"] !== ""
            ) {

                $user_name = $user["username"];

            } elseif (
                isset($user["full_name"]) &&
                $user["full_name"] !== ""
            ) {

                $user_name = $user["full_name"];

            } elseif (
                isset($user["nama"]) &&
                $user["nama"] !== ""
            ) {

                $user_name = $user["nama"];

            }

        }


        $user_stmt->close();


        /*
        |--------------------------------------------------------------------------
        | Tambahkan nama user
        |--------------------------------------------------------------------------
        */

        $row["user_name"] = $user_name;


        $comments[] = $row;
    }


    $stmt->close();


    echo json_encode([
        "success" => true,
        "data" => $comments,
        "comment_count" => count($comments)
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| POST
|--------------------------------------------------------------------------
| Membuat komentar baru
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Silakan login untuk menulis komentar"]);
        exit;
    }
    $input = $_POST ?: (json_decode(file_get_contents("php://input"), true) ?: []);

    $user_id = (int) $_SESSION['user_id'];

    $case_id = intval(
        $input["case_id"] ?? 0
    );

    $comment = trim(
        $input["comment"] ?? ""
    );


    /*
    |--------------------------------------------------------------------------
    | Validasi
    |--------------------------------------------------------------------------
    */

    if (
        $case_id <= 0 ||
        $comment === ""
    ) {

        echo json_encode([
            "success" => false,
            "message" => "case_id dan comment wajib diisi"
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Cek Case
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        SELECT id
        FROM cases
        WHERE id = ?
        LIMIT 1
    ");


    $stmt->bind_param(
        "i",
        $case_id
    );


    $stmt->execute();

    $result = $stmt->get_result();


    if ($result->num_rows === 0) {

        echo json_encode([
            "success" => false,
            "message" => "Case tidak ditemukan"
        ]);

        exit;
    }


    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | Cek User
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        SELECT id
        FROM users
        WHERE id = ?
        LIMIT 1
    ");


    $stmt->bind_param(
        "i",
        $user_id
    );


    $stmt->execute();

    $result = $stmt->get_result();


    if ($result->num_rows === 0) {

        echo json_encode([
            "success" => false,
            "message" => "User tidak ditemukan"
        ]);

        exit;
    }


    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | Simpan komentar
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        INSERT INTO comments
        (
            user_id,
            case_id,
            comment
        )
        VALUES (?, ?, ?)
    ");


    $stmt->bind_param(
        "iis",
        $user_id,
        $case_id,
        $comment
    );


    if ($stmt->execute()) {

        echo json_encode([
            "success" => true,
            "message" => "Komentar berhasil ditambahkan",
            "comment_id" => $stmt->insert_id
        ]);

    } else {

        echo json_encode([
            "success" => false,
            "message" => "Gagal menambahkan komentar",
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
*/

if ($_SERVER["REQUEST_METHOD"] === "DELETE") {

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Silakan login untuk menghapus komentar"]);
        exit;
    }

    $input = json_decode(
        file_get_contents("php://input"),
        true
    );


    $id = intval(
        $input["id"] ?? 0
    );


    if ($id <= 0) {

        echo json_encode([
            "success" => false,
            "message" => "ID komentar wajib diisi"
        ]);

        exit;
    }


    $stmt = $conn->prepare("
        DELETE FROM comments
        WHERE id = ? AND (user_id = ? OR ? = 'petugas')
    ");


    $user_id = (int) $_SESSION['user_id'];
    $role = (string) ($_SESSION['user_role'] ?? 'warga');
    $stmt->bind_param("iis", $id, $user_id, $role);


    if ($stmt->execute()) {

        echo json_encode([
            "success" => true,
            "message" => "Komentar berhasil dihapus"
        ]);

    } else {

        echo json_encode([
            "success" => false,
            "message" => "Gagal menghapus komentar",
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
