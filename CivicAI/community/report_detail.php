<?php

require_once __DIR__ . "/../config/database.php";

$id = $_GET["id"] ?? null;

if (!$id) {
    die("ID laporan tidak ditemukan.");
}

$stmt = $conn->prepare("SELECT * FROM reports WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$report = $result->fetch_assoc();

if (!$report) {
    die("Laporan tidak ditemukan.");
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars($report["title"]) ?> - CivicAI
    </title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f5f7fa;
            color: #222;
        }

        header {
            background: white;
            border-bottom: 1px solid #ddd;
            padding: 18px 7%;

            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
        }

        .logo span {
            color: #2563eb;
        }

        header a {
            text-decoration: none;
            color: #333;
        }

        .container {
            max-width: 850px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
            text-decoration: none;
            color: #2563eb;
        }

        .card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 30px;
        }

        h1 {
            font-size: 32px;
            margin-bottom: 15px;
        }

        .description {
            color: #555;
            line-height: 1.7;
            margin: 20px 0;
        }

        .info {
            padding: 15px 0;
            border-top: 1px solid #eee;
        }

        .label {
            font-size: 13px;
            color: #777;
            margin-bottom: 5px;
        }

        .value {
            font-weight: bold;
        }

        .badges {
            margin-top: 20px;
        }

        .badge {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 20px;
            margin-right: 8px;
            font-size: 13px;
            background: #e8f0fe;
            color: #2563eb;
        }

        .status {
            background: #fff3cd;
            color: #856404;
        }

    </style>

    <link rel="stylesheet" href="../assets/css/app-theme.css">
</head>

<body>

<header>

    <div class="logo">
        Civic<span>AI</span>
    </div>

    <a href="index.php">
        Home
    </a>

</header>


<div class="container">

    <a href="index.php" class="back">
        ← Kembali ke laporan
    </a>


    <div class="card">

        <h1>
            <?= htmlspecialchars($report["title"]) ?>
        </h1>


        <div class="badges">

            <span class="badge">
                <?= htmlspecialchars($report["category"]) ?>
            </span>

            <span class="badge status">
                <?= htmlspecialchars($report["status"]) ?>
            </span>

            <span class="badge">
                Prioritas:
                <?= htmlspecialchars($report["priority"]) ?>
            </span>

        </div>


        <p class="description">
            <?= nl2br(htmlspecialchars($report["description"])) ?>
        </p>


        <div class="info">

            <div class="label">
                Lokasi
            </div>

            <div class="value">
                📍 <?= htmlspecialchars($report["location"]) ?>
            </div>

        </div>


        <div class="info">

            <div class="label">
                Laporan dibuat
            </div>

            <div class="value">
                <?= htmlspecialchars($report["created_at"]) ?>
            </div>

        </div>


        <div class="info">

            <div class="label">
                Terakhir diperbarui
            </div>

            <div class="value">
                <?= htmlspecialchars($report["updated_at"]) ?>
            </div>

        </div>

    </div>

</div>

</body>

</html>