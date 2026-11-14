<?php
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'petugas') {
    header("Location: ../auth/login.php?error=Unauthorized");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$allowedStatuses = ['all', 'pending', 'proses', 'selesai', 'ditolak'];
$selectedStatus = strtolower(trim($_GET['status'] ?? 'all'));
if (!in_array($selectedStatus, $allowedStatuses, true)) {
    $selectedStatus = 'all';
}

$statusCounts = array_fill_keys(array_slice($allowedStatuses, 1), 0);
$countResult = $conn->query("SELECT CASE WHEN LOWER(status) IN ('proses', 'in_progress') THEN 'proses' WHEN LOWER(status) IN ('selesai', 'resolved') THEN 'selesai' WHEN LOWER(status) IN ('ditolak', 'rejected') THEN 'ditolak' ELSE 'pending' END AS status, COUNT(*) AS total FROM reports GROUP BY CASE WHEN LOWER(status) IN ('proses', 'in_progress') THEN 'proses' WHEN LOWER(status) IN ('selesai', 'resolved') THEN 'selesai' WHEN LOWER(status) IN ('ditolak', 'rejected') THEN 'ditolak' ELSE 'pending' END");
if ($countResult) {
    while ($countRow = $countResult->fetch_assoc()) {
        $countStatus = strtolower($countRow['status']);
        if (isset($statusCounts[$countStatus])) {
            $statusCounts[$countStatus] = (int) $countRow['total'];
        }
    }
}

$query = "SELECT * FROM reports";
$queryParams = [];
$queryTypes = '';
if ($selectedStatus !== 'all') {
    $query .= " WHERE CASE WHEN LOWER(status) IN ('proses', 'in_progress') THEN 'proses' WHEN LOWER(status) IN ('selesai', 'resolved') THEN 'selesai' WHEN LOWER(status) IN ('ditolak', 'rejected') THEN 'ditolak' ELSE 'pending' END = ?";
    $queryParams[] = $selectedStatus;
    $queryTypes = 's';
}
$query .= " ORDER BY 
    CASE 
        WHEN priority = 'critical' THEN 1 
        WHEN priority = 'high' THEN 2 
        WHEN priority = 'medium' THEN 3 
        ELSE 4 
    END ASC, created_at DESC";

$reportStmt = $conn->prepare($query);
if ($queryParams) {
    $statusParam = $queryParams[0];
    $reportStmt->bind_param($queryTypes, $statusParam);
}
$reportStmt->execute();
$result = $reportStmt->get_result();

$statusLabels = ['pending' => 'Pending', 'proses' => 'Diproses', 'selesai' => 'Selesai', 'ditolak' => 'Ditolak'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Priority Queue Petugas - CivicAI Officer</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@700;900&family=IBM+Plex+Mono:wght@400;500;600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ==========================================================
           CivicAI — Shared Design System (Matched with case_action.php)
           ========================================================== */

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

        /* ---------- Buttons ---------- */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 38px;
            padding: 0 16px;
            border-radius: var(--radius);
            font-size: 13px;
            font-weight: 700;
            border: 1.5px solid var(--navy);
            transition: transform .15s ease, box-shadow .15s ease;
            white-space: nowrap;
            cursor: pointer;
            background: none;
            font-family: inherit;
        }
        .btn:hover { transform: translate(-2px, -2px); box-shadow: 3px 3px 0 var(--signal); }
        .btn-primary { color: var(--white); background: var(--navy); }
        .btn-secondary { color: var(--navy); background: transparent; border-color: var(--line); }

        /* ---------- Officer Nav ---------- */
        .officer-nav { background: var(--ink); color: var(--paper); padding: 16px 8%; display: flex; justify-content: space-between; align-items: center; }
        .officer-nav .nav-left { display: flex; align-items: center; gap: 28px; }
        .officer-nav h2 { font-family: 'Big Shoulders Display', sans-serif; font-weight: 900; text-transform: uppercase; font-size: 19px; color: var(--signal); letter-spacing: -0.2px; }
        .officer-nav .nav-links { display: flex; gap: 6px; flex-wrap: wrap; }
        .officer-nav .nav-links a { color: #B9C2BC; text-decoration: none; padding: 8px 12px; border-radius: var(--radius); font-size: 13px; font-weight: 600; transition: .15s ease; }
        .officer-nav .nav-links a:hover, .officer-nav .nav-links a.active { background: rgba(232, 99, 28, 0.16); color: var(--white); }
        .btn-logout { background: var(--danger); color: var(--white); border-color: var(--danger); padding: 7px 14px; min-height: unset; font-size: 12.5px; border-radius: var(--radius); font-weight: 700; }
        .btn-logout:hover { transform: translate(-2px, -2px); box-shadow: 3px 3px 0 var(--ink); }

        /* ---------- Main Content ---------- */
        .main-content { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .card {
            background: var(--white);
            border: 1.5px solid var(--ink);
            border-radius: var(--radius-lg);
            padding: 28px;
            box-shadow: 6px 6px 0 rgba(28, 35, 33, 0.06);
        }
        h1 {
            font-family: 'Big Shoulders Display', sans-serif;
            font-weight: 900;
            text-transform: uppercase;
            font-size: 28px;
            letter-spacing: -0.3px;
            color: var(--ink);
            margin-bottom: 6px;
        }

        /* ---------- Table Styles ---------- */
        .table-wrapper { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; margin-top: 15px; }
        table { width: 100%; border-collapse: collapse; min-width: 900px; }
        th, td { padding: 12px 16px; text-align: left; border-bottom: 1.5px solid var(--line); font-size: 13.5px; }
        th { background: var(--paper); color: var(--ink-soft); font-family: 'IBM Plex Mono', monospace; font-size: 11.5px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }

        /* ---------- Badges ---------- */
        .badge { display: inline-block; padding: 4px 9px; font-size: 11px; font-weight: 700; border-radius: var(--radius); text-transform: uppercase; letter-spacing: .3px; }
        .badge-critical { background: #FBEAE8; color: var(--danger); }
        .badge-high { background: #FCEADD; color: var(--signal); }
        .badge-medium { background: #FBF3D9; color: #8A6A11; }
        .badge-low { background: var(--paper); color: var(--ink-soft); }

        /* ---------- Status Filters ---------- */
        .queue-summary { display: flex; flex-wrap: wrap; gap: 8px; margin: 18px 0 6px; }
        .status-filter {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border: 1.5px solid var(--line);
            border-radius: 20px;
            color: var(--ink-soft);
            background: var(--white);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: .15s ease;
        }
        .status-filter:hover { border-color: var(--navy); color: var(--navy); }
        .status-filter.active { border-color: var(--navy); color: var(--white); background: var(--navy); }
        .status-filter strong { font-family: 'IBM Plex Mono', monospace; font-size: 11px; }

        @media (max-width: 768px) {
            .officer-nav { padding: 14px 18px; flex-direction: column; align-items: flex-start; gap: 12px; }
            .officer-nav .nav-left { flex-direction: column; align-items: flex-start; gap: 8px; width: 100%; }
            .officer-nav .nav-links { width: 100%; overflow-x: auto; padding-bottom: 4px; }
            .main-content { margin: 24px auto; padding: 0 12px; }
            .card { padding: 18px; }
            h1 { font-size: 22px; }
        }
    </style>
    <link rel="stylesheet" href="../assets/css/app-theme.css">
</head>
<body>

    <!-- Navbar Horizontal Petugas -->
    <nav class="officer-nav">
        <div class="nav-left">
            <h2>CivicAI Officer</h2>
            <div class="nav-links">
                <a href="index.php" class="active">Priority Queue</a>
                <a href="post_reports.php">Report Postingan</a>
                <a href="heatmap.php">Heatmap & Sebaran</a>
                <a href="../community/index.php">← Ke Web Warga</a>
            </div>
        </div>
        <div>
            <a href="../auth/logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="card">
            <h1>Daftar Antrean Prioritas Penanganan (AI Priority Queue)</h1>
            <p style="color: var(--ink-soft); font-size: 14px; margin-bottom: 16px;">Laporan di bawah ini diurutkan otomatis berdasarkan tingkat darurat yang dianalisis oleh AI[cite: 5].</p>

            <div class="queue-summary">
                <?php
                    $filters = [
                        'all' => ['label' => 'Semua', 'count' => array_sum($statusCounts)],
                        'pending' => ['label' => 'Pending', 'count' => $statusCounts['pending']],
                        'proses' => ['label' => 'Diproses', 'count' => $statusCounts['proses']],
                        'selesai' => ['label' => 'Selesai', 'count' => $statusCounts['selesai']],
                        'ditolak' => ['label' => 'Ditolak', 'count' => $statusCounts['ditolak']]
                    ];
                ?>
                <?php foreach ($filters as $filterKey => $filter): ?>
                    <a class="status-filter <?= $selectedStatus === $filterKey ? 'active' : '' ?>" href="index.php?status=<?= urlencode($filterKey) ?>">
                        <?= $filter['label'] ?> <strong>(<?= $filter['count'] ?>)</strong>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Prioritas AI</th>
                            <th>Judul & Kategori</th>
                            <th>Lokasi</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><span class="mono" style="font-size: 12px; color: var(--ink-soft);">#<?= $row['id'] ?></span></td>
                                    <td>
                                        <?php 
                                            $pClass = 'badge-low';
                                            $p = strtolower($row['priority'] ?? 'medium');
                                            if ($p === 'critical') $pClass = 'badge-critical';
                                            elseif ($p === 'high') $pClass = 'badge-high';
                                            elseif ($p === 'medium') $pClass = 'badge-medium';
                                        ?>
                                        <span class="badge <?= $pClass ?>"><?= strtoupper($row['priority']) ?></span>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($row['title']) ?></strong><br>
                                        <span style="font-size: 12px; color: var(--ink-soft);"><?= htmlspecialchars($row['category']) ?></span>
                                        <?php if (isset($row['ai_priority_score'])): ?>
                                            <br><span class="mono" style="font-size: 11px; color: var(--signal);">AI Score: <?= intval($row['ai_priority_score']) ?>/100</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['location']) ?></td>
                                    <td>
                                        <strong style="text-transform: uppercase; font-size: 11.5px; font-family: 'IBM Plex Mono', monospace; color: var(--navy);">
                                            <?= htmlspecialchars($statusLabels[$row['status']] ?? $row['status'] ?? 'Pending') ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <a href="case_action.php?id=<?= $row['id'] ?>" class="btn btn-primary" style="padding: 5px 12px; font-size: 12px; min-height: 32px;">Proses / Aksi</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--ink-soft); padding: 28px;">Belum ada antrean laporan penanganan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>