<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');

session_start();
require_once __DIR__ . "/../config/database.php";

$query = "
    SELECT r.*, COUNT(c.id) as total_discussions 
    FROM reports r 
    LEFT JOIN report_comments c ON r.id = c.report_id 
    GROUP BY r.id 
    ORDER BY r.created_at DESC
";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feed Laporan Warga - CivicAI</title>
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

        /* ---------- Layout & Container ---------- */
        .container { max-width: 800px; margin: 36px auto; padding: 0 20px; }
        
        .page-title {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }
        .page-title span:first-child {
            font-family: 'Big Shoulders Display', sans-serif;
            font-weight: 900;
            text-transform: uppercase;
            font-size: 32px;
            letter-spacing: -0.5px;
            color: var(--ink);
            line-height: 1;
        }
        
        .btn-create {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 38px;
            padding: 0 16px;
            border-radius: var(--radius);
            font-size: 13px;
            font-weight: 700;
            border: 1.5px solid var(--ink);
            transition: transform .15s ease, box-shadow .15s ease;
            white-space: nowrap;
            cursor: pointer;
            background: var(--signal);
            color: var(--white);
            font-family: inherit;
        }
        .btn-create:hover { transform: translate(-2px, -2px); box-shadow: 3px 3px 0 var(--ink); }

        /* ---------- Card Laporan ---------- */
        .card {
            background: var(--white);
            border: 1.5px solid var(--ink);
            border-radius: var(--radius-lg);
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 6px 6px 0 rgba(28, 35, 33, 0.06);
            transition: transform .15s ease;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 9px;
            font-size: 11px;
            font-weight: 700;
            border-radius: var(--radius);
            text-transform: uppercase;
            letter-spacing: .3px;
            margin-bottom: 10px;
            margin-right: 4px;
        }
        .badge-critical { background: #FBEAE8; color: var(--danger); }
        .badge-high { background: #FCEADD; color: var(--signal); }
        .badge-medium { background: #FBF3D9; color: #8A6A11; }
        .badge-low { background: var(--paper); color: var(--ink-soft); }
        .badge-category { background: #E2ECF5; color: var(--navy); }
        .badge-success { background: #E6F4EA; color: var(--sage); border: 1px solid rgba(76, 122, 82, 0.2); }

        .title {
            font-family: 'Big Shoulders Display', sans-serif;
            font-weight: 900;
            text-transform: uppercase;
            font-size: 22px;
            letter-spacing: -0.2px;
            color: var(--ink);
            margin-bottom: 8px;
        }
        .desc {
            font-size: 14px;
            color: var(--ink-soft);
            margin-bottom: 14px;
            line-height: 1.5;
        }
        
        .img-preview { display: block; width: auto; max-width: 100%; max-height: 250px; object-fit: contain; border-radius: var(--radius); margin: 0 auto 14px; border: 1.5px solid var(--line); }
        .img-preview.landscape { width: 100%; max-width: 100%; max-height: none; margin-left: 0; margin-right: 0; }
        .video-preview { display: block; width: 100%; max-height: 250px; object-fit: contain; border-radius: var(--radius); margin: 0 auto 14px; background: var(--ink); border: 1.5px solid var(--line); }
        
        .officer-box {
            background: #F0F7F1;
            border-left: 4px solid var(--sage);
            padding: 12px 14px;
            border-radius: var(--radius);
            margin-bottom: 14px;
            font-size: 13px;
            color: var(--ink);
            border-top: 1.5px solid #D8EAD9;
            border-right: 1.5px solid #D8EAD9;
            border-bottom: 1.5px solid #D8EAD9;
        }
        .officer-box strong { color: var(--sage); }

        /* ---------- Footer Card (Konsisten Kiri & Kanan) ---------- */
        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 16px;
            padding-top: 14px;
            border-top: 1.5px solid var(--line);
            gap: 12px;
        }

        .card-meta-info {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 11.5px;
            color: var(--ink-soft);
        }

        .btn-detail {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 36px;
            gap: 6px;
            background: transparent;
            color: var(--navy);
            padding: 0 14px;
            border-radius: var(--radius);
            text-decoration: none;
            font-weight: 700;
            font-size: 12.5px;
            border: 1.5px solid var(--line);
            transition: all .15s ease;
            white-space: nowrap; /* Tombol tidak akan patah ke bawah */
            flex-shrink: 0;     /* Mencegah tombol ikut menyusut */
        }
        .btn-detail:hover {
            background: var(--navy);
            color: var(--white);
            border-color: var(--navy);
            transform: translate(-1px, -1px);
        }

        .empty-state {
            background: var(--white);
            border: 1.5px solid var(--ink);
            border-radius: var(--radius-lg);
            padding: 40px;
            text-align: center;
            color: var(--ink-soft);
            font-size: 14px;
            box-shadow: 6px 6px 0 rgba(28, 35, 33, 0.06);
        }

        @media (max-width: 600px) {
            header { padding: 14px 16px; flex-direction: column; align-items: flex-start; gap: 10px; }
            header nav { width: 100%; justify-content: space-between; flex-wrap: wrap; gap: 8px 12px; }
            header nav a { margin-left: 0; font-size: 12px; }
            .container { width: 100%; margin: 20px auto; padding: 0 12px; }
            .page-title { align-items: stretch; flex-direction: column; gap: 12px; margin-bottom: 18px; }
            .btn-create { text-align: center; width: 100%; }
            .card { padding: 16px; border-radius: var(--radius-lg); }
            .title { font-size: 20px; }
            .desc { font-size: 13.5px; overflow-wrap: anywhere; }
            
            /* Di HP, kita buat tetap rapi atau stack jika layarnya terlalu kecil sekali, 
               tapi card-footer tetap mempertahankan flex agar konsisten samping-sampingan */
            .card-footer {
                flex-direction: row;
                align-items: center;
            }
            .card-meta-info {
                font-size: 10.5px;
                gap: 8px;
            }
            .btn-detail {
                padding: 0 10px;
                font-size: 11.5px;
            }
        }
    </style>
    <link rel="stylesheet" href="../assets/css/app-theme.css">
</head>
<body>

    <header>
        <div class="logo">Civic<span>AI</span></div>
        <nav>
            <a href="index.php" style="color: var(--navy); font-weight: 700;">Daftar Laporan</a>
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'petugas'): ?>
                <a href="../officer/index.php">Dashboard Petugas</a>
            <?php endif; ?>
            <?php if (isset($_SESSION['user_name']) || isset($_SESSION['username'])): ?>
                <a href="../auth/logout.php" style="color: var(--danger);">Logout</a>
            <?php else: ?>
                <a href="../auth/login.php">Login</a>
            <?php endif; ?>
        </nav>
    </header>

    <div class="container">
        <div class="page-title">
            <span>Feed Laporan Warga</span>
            <a href="<?= isset($_SESSION['user_name']) || isset($_SESSION['username']) ? 'create.php' : '../auth/login.php' ?>" class="btn-create">+ Buat Laporan</a>
        </div>

        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="card">
                    <?php 
                        // Badge Prioritas
                        $p = strtolower($row['priority'] ?? 'medium');
                        $badgeClass = 'badge-medium';
                        if ($p === 'critical') $badgeClass = 'badge-critical';
                        elseif ($p === 'high') $badgeClass = 'badge-high';
                        elseif ($p === 'low') $badgeClass = 'badge-low';

                        // Badge Status
                        $st = strtolower($row['status'] ?? 'pending');
                        $statusClass = 'badge-medium';
                        $statusText = 'Pending';
                        
                        if ($st === 'proses' || $st === 'in_progress') {
                            $statusClass = 'badge-high';
                            $statusText = 'Diproses';
                        } elseif ($st === 'selesai' || $st === 'resolved') {
                            $statusClass = 'badge-success';
                            $statusText = 'Selesai';
                        } elseif ($st === 'ditolak' || $st === 'rejected') {
                            $statusClass = 'badge-critical';
                            $statusText = 'Ditolak';
                        }
                    ?>
                    <div>
                        <span class="badge <?= $badgeClass ?>">Prioritas: <?= strtoupper($row['priority']) ?></span>
                        <span class="badge badge-category"><?= htmlspecialchars($row['category']) ?></span>
                        <span class="badge <?= $statusClass ?>">Status: <?= $statusText ?></span>
                    </div>

                    <div class="title"><?= htmlspecialchars($row['title']) ?></div>
                    <div class="desc"><?= htmlspecialchars($row['description']) ?></div>

                    <?php if (!empty($row['photo']) && file_exists(__DIR__ . '/../uploads/' . $row['photo'])): ?>
                        <img src="../uploads/<?= htmlspecialchars($row['photo']) ?>" alt="Bukti Laporan" class="img-preview" onload="this.classList.toggle('landscape', this.naturalWidth > this.naturalHeight)">
                    <?php endif; ?>

                    <?php if (!empty($row['video']) && file_exists(__DIR__ . '/../uploads/' . $row['video'])): ?>
                        <video class="video-preview" controls preload="none">
                            <source src="../uploads/<?= htmlspecialchars($row['video']) ?>">
                            Browser Anda tidak mendukung pemutar video.
                        </video>
                    <?php endif; ?>

                    <?php if (!empty($row['officer_notes'])): ?>
                        <div class="officer-box">
                            <strong>Respon Petugas:</strong> <?= htmlspecialchars($row['officer_notes']) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Footer Card (Konsisten di Samping Kiri & Kanan) -->
                    <div class="card-footer">
                        <div class="card-meta-info">
                            <span>📍 <?= htmlspecialchars($row['location']) ?></span>
                            <span>👍 <?= intval($row['likes']) ?> Dukungan</span>
                            <span>💬 <?= intval($row['total_discussions']) ?> Diskusi</span>
                        </div>
                        <a href="detail.php?id=<?= $row['id'] ?>" class="btn-detail">
                            Lihat Detail & Diskusi →
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                Belum ada laporan yang dikirimkan. Jadilah yang pertama membuat laporan!
            </div>
        <?php endif; ?>
    </div>

</body>
</html>
