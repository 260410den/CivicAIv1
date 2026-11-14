<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../api/ai_service.php";

session_start();
if (!isset($_SESSION['user_name']) && !isset($_SESSION['username'])) {
    header("Location: ../auth/login.php");
    exit;
}

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? "");
    $provinsi = trim($_POST["provinsi"] ?? "");
    $kota = trim($_POST["kota"] ?? "");
    $patokan = trim($_POST["patokan"] ?? "");
    
    $location = "$patokan, Kota/Kab. $kota, Prov. $provinsi";
    
    $description = trim($_POST["description"] ?? "");
    $priority = trim($_POST["priority"] ?? "medium");
    $user_id = (int) ($_SESSION['user_id'] ?? 0);
    $ai_analysis = AIService::analyzeReportText($title, $description);
    $ai_priority = $ai_analysis['recommended_priority'] ?? 'medium';
    $category = $ai_analysis['category'] ?? 'Lainnya';
    
    // Handle Upload Foto
    $photo_name = "";
    if (isset($_FILES["photo"]) && $_FILES["photo"]["error"] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES["photo"]["tmp_name"];
        $file_ext = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($file_ext, $allowed_ext)) {
            $photo_name = uniqid("report_") . "." . $file_ext;
            $upload_dir = __DIR__ . "/../uploads/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            move_uploaded_file($file_tmp, $upload_dir . $photo_name);
        }
    }

    // Handle Upload Video
    $video_name = "";
    if (isset($_FILES["video"]) && $_FILES["video"]["error"] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES["video"]["tmp_name"];
        $file_ext = strtolower(pathinfo($_FILES["video"]["name"], PATHINFO_EXTENSION));
        $allowed_ext = ['mp4', 'mov', 'avi', 'mkv', 'webm', 'wmv'];

        if (in_array($file_ext, $allowed_ext, true)) {
            $video_name = uniqid("report_video_") . "." . $file_ext;
            $upload_dir = __DIR__ . "/../uploads/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            move_uploaded_file($file_tmp, $upload_dir . $video_name);
        }
    }

    if ($user_id <= 0) {
        $error = "Sesi login tidak ditemukan. Silakan masuk kembali.";
    } elseif (!empty($title) && !empty($provinsi) && !empty($description)) {
        // Menyertakan user_id ke dalam query insert
        $stmt = $conn->prepare("INSERT INTO reports (user_id, title, category, location, description, priority, photo, video, status, ai_summary, ai_category, ai_priority, ai_priority_score, ai_priority_reasoning, ai_problem, ai_duration, ai_risk, ai_location_context) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("isssssss" . "sssisssss", $user_id, $title, $category, $location, $description, $ai_priority, $photo_name, $video_name, $ai_analysis['summary'], $ai_analysis['problem'], $ai_priority, $ai_analysis['priority_score'], $ai_analysis['ai_reasoning'], $ai_analysis['problem'], $ai_analysis['duration'], $ai_analysis['risk'], $ai_analysis['location_context']);
            
            if ($stmt->execute()) {
                header("Location: index.php");
                exit;
            } else {
                $error = "Gagal eksekusi database: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "Gagal prepare statement: " . $conn->error;
        }
    } else {
        $error = "Mohon lengkapi judul, wilayah provinsi, dan deskripsi laporan.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Laporan Warga - CivicAI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@700;900&family=IBM+Plex+Mono:wght@400;500;600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --paper: #EDEAE0;
            --paper-deep: #E3DECC;
            --ink: #1C2321;
            --ink-soft: #5B6660;
            --navy: #1B3A5C;
            --signal: #E8631C;
            --sage: #4C7A52;
            --danger: #B3261E;
            --line: #C9C2AE;
            --white: #FFFFFF;
            --radius: 3px;
            --radius-lg: 8px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            background: var(--paper);
            color: var(--ink);
            font-family: 'Inter', Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
            line-height: 1.5;
        }
        a { color: inherit; text-decoration: none; }
        
        /* ---------- Header Navbar ---------- */
        header {
            background: var(--white);
            border-bottom: 1.5px solid var(--ink);
            padding: 16px 8%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            font-family: 'Big Shoulders Display', sans-serif;
            font-weight: 900;
            text-transform: uppercase;
            font-size: 22px;
            color: var(--ink);
            letter-spacing: -0.3px;
        }
        .logo span { color: var(--signal); }
        header nav {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        header nav a {
            text-decoration: none;
            color: var(--ink-soft);
            font-weight: 600;
            font-size: 13.5px;
            transition: color .15s ease;
        }
        header nav a:hover { color: var(--navy); }
        
        /* ---------- Container Form ---------- */
        .container { 
            max-width: 680px; 
            margin: 36px auto; 
            background: var(--white); 
            padding: 32px; 
            border-radius: var(--radius-lg); 
            box-shadow: 6px 6px 0 rgba(28, 35, 33, 0.06); 
            border: 1.5px solid var(--ink); 
        }
        
        .back-link { 
            display: inline-flex; 
            align-items: center; 
            margin-bottom: 20px; 
            color: var(--navy); 
            text-decoration: none; 
            font-weight: 600; 
            font-size: 13px; 
            font-family: 'IBM Plex Mono', monospace;
        }
        .back-link:hover { text-decoration: underline; }

        h1 { 
            font-family: 'Big Shoulders Display', sans-serif;
            font-weight: 900;
            text-transform: uppercase;
            font-size: 28px; 
            margin-bottom: 24px; 
            color: var(--ink); 
            letter-spacing: -0.3px; 
            line-height: 1;
        }
        
        .form-group { margin-bottom: 20px; }
        label { 
            display: block; 
            font-weight: 700; 
            margin-bottom: 8px; 
            font-size: 12px; 
            color: var(--ink); 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
        }
        
        .ai-note {
            font-size: 12.5px;
            color: var(--ink-soft);
            margin-top: -4px;
            margin-bottom: 4px;
            font-style: italic;
        }

        input[type="text"], select, textarea { 
            width: 100%; 
            padding: 10px 12px; 
            border: 1.5px solid var(--line); 
            border-radius: var(--radius); 
            font-size: 13.5px; 
            color: var(--ink); 
            background: var(--white);
            font-family: inherit;
            transition: border-color 0.15s ease;
        }
        
        input[type="text"]:focus, select:focus, textarea:focus { 
            outline: none; 
            border-color: var(--navy); 
        }

        textarea { resize: vertical; height: 110px; }
        
        .row-grid { display: flex; gap: 12px; }
        .row-grid .flex-1 { flex: 1; }

        .file-input-wrapper {
            border: 1.5px dashed var(--line);
            padding: 14px;
            border-radius: var(--radius);
            background: var(--paper);
            text-align: center;
        }
        input[type="file"] { font-size: 12.5px; color: var(--ink-soft); }

        .btn-submit { 
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--signal); 
            color: var(--white); 
            border: 1.5px solid var(--ink); 
            padding: 0 20px;
            min-height: 42px; 
            font-size: 13.5px; 
            font-weight: 700; 
            border-radius: var(--radius); 
            cursor: pointer; 
            width: 100%; 
            transition: transform .15s ease, box-shadow .15s ease;
            box-shadow: 3px 3px 0 var(--ink);
            margin-top: 8px;
            font-family: inherit;
        }
        .btn-submit:hover { transform: translate(-2px, -2px); box-shadow: 5px 5px 0 var(--ink); }
        .btn-submit:active { transform: scale(0.99); }
        
        .alert { padding: 12px 14px; border-radius: var(--radius); margin-bottom: 20px; font-weight: 600; font-size: 13px; border: 1.5px solid transparent; }
        .success { background: #E6F4EA; color: var(--sage); border-color: rgba(76, 122, 82, 0.3); }
        .error { background: #FBEAE8; color: var(--danger); border-color: rgba(179, 38, 30, 0.3); }

        @media (max-width: 600px) {
            header { padding: 14px 16px; flex-direction: column; align-items: flex-start; gap: 10px; }
            header nav { width: 100%; justify-content: space-between; flex-wrap: wrap; gap: 8px 12px; }
            header nav a { margin-left: 0; font-size: 12px; }
            .container { width: 100%; margin: 20px auto; padding: 20px 16px; border-radius: var(--radius-lg); }
            h1 { font-size: 24px; }
            .row-grid { flex-direction: column; gap: 10px; }
            .file-input-wrapper { padding: 10px 6px; overflow: hidden; }
            input[type="file"] { max-width: 100%; font-size: 11.5px; }
        }
    </style>
    <link rel="stylesheet" href="../assets/css/app-theme.css">
</head>
<body>

    <header>
        <div class="logo">Civic<span>AI</span></div>
        <nav>
            <a href="index.php" style="color: var(--navy); font-weight: 700;">Daftar Laporan</a>
            <a href="../officer/index.php">Portal Petugas</a>
        </nav>
    </header>

    <div class="container">
        <a href="index.php" class="back-link">← Kembali ke Feed Laporan</a>
        <h1>Formulir Pengaduan Warga</h1>

        <?php if (!empty($message)): ?>
            <div class="alert success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            
            <div class="form-group">
                <label for="title">Judul Laporan</label>
                <input type="text" id="title" name="title" placeholder="Contoh: Lampu PJU Mati di Perempatan" required>
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
                <label>Kategori & Prioritas</label>
                <p class="ai-note">Akan dianalisis otomatis oleh AI dari judul dan deskripsi laporan.</p>
            </div>

            <!-- WILAYAH LOKASI -->
            <div class="form-group">
                <label>Wilayah Lokasi</label>
                <div class="row-grid">
                    <div class="flex-1">
                        <select name="provinsi" required>
                            <option value="">Pilih Provinsi</option>
                            <option value="DKI Jakarta">DKI Jakarta</option>
                            <option value="Jawa Barat">Jawa Barat</option>
                            <option value="Jawa Tengah">Jawa Tengah</option>
                            <option value="Jawa Timur">Jawa Timur</option>
                            <option value="DI Yogyakarta">DI Yogyakarta</option>
                            <option value="Banten">Banten</option>
                            <option value="Bali">Bali</option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <input type="text" name="kota" placeholder="Kota / Kabupaten" required>
                    </div>
                </div>
            </div>

            <!-- DETAIL JALAN / PATOKAN -->
            <div class="form-group">
                <label>Detail Jalan / Patokan Tempat</label>
                <input type="text" name="patokan" placeholder="Contoh: Jl. Sudirman No. 45 (Depan Toko Berkah)" required>
            </div>

            <div class="form-group">
                <label for="description">Deskripsi Lengkap Kondisi</label>
                <textarea id="description" name="description" placeholder="Jelaskan kondisi kerusakan secara rinci..." required></textarea>
            </div>

            <div class="form-group">
                <label for="photo">Bukti Foto Kerusakan</label>
                <div class="file-input-wrapper">
                    <input type="file" id="photo" name="photo" accept="image/*">
                </div>
            </div>

            <div class="form-group">
                <label for="video">Bukti Video Kerusakan</label>
                <div class="file-input-wrapper">
                    <input type="file" id="video" name="video" accept="video/*">
                </div>
            </div>

            <button type="submit" class="btn-submit">Kirim Laporan Warga →</button>
        </form>
    </div>

</body>
</html>
