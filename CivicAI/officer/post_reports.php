<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'petugas') {
    header("Location: ../auth/login.php?error=Unauthorized");
    exit;
}
require_once __DIR__ . "/../config/database.php";

$allowedStatuses = ['all', 'pending', 'reviewed', 'valid', 'rejected'];
$selectedStatus = strtolower(trim($_GET['status'] ?? 'all'));
if (!in_array($selectedStatus, $allowedStatuses, true)) $selectedStatus = 'all';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['moderate_report'])) {
    $moderationId = (int) ($_POST['id'] ?? 0);
    $status = trim($_POST['status'] ?? 'reviewed');
    $notes = trim($_POST['officer_notes'] ?? '');
    if ($moderationId > 0 && in_array($status, array_slice($allowedStatuses, 1), true)) {
        $update = $conn->prepare("UPDATE post_reports SET status = ?, officer_notes = ? WHERE id = ?");
        $update->bind_param("ssi", $status, $notes, $moderationId);
        $update->execute();
        $update->close();
    }
    header("Location: post_reports.php?status=" . urlencode($selectedStatus));
    exit;
}

$sql = "SELECT pr.*, r.title AS post_title, r.description AS post_description, u.name AS reporter_name FROM post_reports pr JOIN reports r ON r.id = pr.report_id LEFT JOIN users u ON u.id = pr.reporter_id";
if ($selectedStatus !== 'all') $sql .= " WHERE pr.status = ?";
$sql .= " ORDER BY pr.created_at DESC";
$stmt = $conn->prepare($sql);
if ($selectedStatus !== 'all') $stmt->bind_param("s", $selectedStatus);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderasi Postingan - CivicAI Officer</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@700;900&family=IBM+Plex+Mono:wght@400;500;600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
    /* ==========================================================
       CivicAI — Shared Design System
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

    :focus-visible { outline: 2.5px solid var(--signal); outline-offset: 3px; }

    /* ---------- Officer panel Navbar ---------- */

    .officer-nav { background: var(--ink); color: var(--paper); padding: 16px 8%; display: flex; justify-content: space-between; align-items: center; }
    .officer-nav .nav-left { display: flex; align-items: center; gap: 28px; }
    .officer-nav h2 { font-family: 'Big Shoulders Display', sans-serif; font-weight: 900; text-transform: uppercase; font-size: 19px; color: var(--signal); letter-spacing: -0.2px; }
    .officer-nav .nav-links { display: flex; gap: 6px; }
    .officer-nav .nav-links a { color: #B9C2BC; text-decoration: none; padding: 8px 12px; border-radius: var(--radius); font-size: 13px; font-weight: 600; transition: .15s ease; }
    .officer-nav .nav-links a:hover, .officer-nav .nav-links a.active { background: rgba(232, 99, 28, 0.16); color: var(--white); }
    .officer-nav .btn-logout { background: var(--danger); color: var(--white); border-color: var(--danger); padding: 7px 14px; min-height: unset; font-size: 12.5px; border: none; }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 40px;
        padding: 0 18px;
        border-radius: var(--radius);
        font-size: 13.5px;
        font-weight: 700;
        border: 1.5px solid var(--navy);
        transition: transform .15s ease, box-shadow .15s ease;
        white-space: nowrap;
        cursor: pointer;
        background: none;
        font-family: inherit;
    }
    .btn:hover { transform: translate(-2px, -2px); box-shadow: 3px 3px 0 var(--signal); }

    /* ---------- Generic card ---------- */

    .card {
        background: var(--white);
        border: 1.5px solid var(--ink);
        border-radius: var(--radius-lg);
        padding: 26px;
        box-shadow: 6px 6px 0 rgba(28, 35, 33, 0.08);
        margin-bottom: 25px;
    }

    /* ---------- Main Content & Components ---------- */

    .main-content { max-width: 1200px; margin: 30px auto; padding: 0 28px; }
    .header { margin-bottom: 25px; }
    .header h1 {
        font-family: 'Big Shoulders Display', sans-serif;
        font-weight: 900;
        text-transform: uppercase;
        font-size: clamp(26px, 3.4vw, 38px);
        letter-spacing: -0.5px;
        margin-bottom: 6px;
        color: var(--ink);
    }
    .header p { color: var(--ink-soft); font-size: 15.5px; line-height: 1.7; }
    
    .filters { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 16px; }
    .filters a {
        padding: 8px 14px;
        border: 1.5px solid var(--line);
        border-radius: var(--radius);
        color: var(--ink-soft);
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        background: var(--white);
        transition: all .15s ease;
    }
    .filters a:hover { border-color: var(--navy); color: var(--navy); }
    .filters a.active { color: var(--white); background: var(--navy); border-color: var(--navy); }

    .report { border-top: 1.5px solid var(--line); padding: 22px 0; }
    .report:first-child { border-top: 0; padding-top: 0; }
    .report h3 { 
        font-family: 'Big Shoulders Display', sans-serif;
        font-size: 20px;
        color: var(--ink);
        margin-bottom: 6px;
        letter-spacing: -0.2px;
    }
    .meta { color: var(--ink-soft); font-family: 'IBM Plex Mono', monospace; font-size: 12px; margin-bottom: 10px; }
    .reason { display: inline-block; background: #FBEAE8; color: var(--danger); padding: 4px 10px; border-radius: var(--radius); font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: .3px; border: 1px solid var(--danger); margin-bottom: 10px; }
    .report p { color: var(--ink); font-size: 14px; line-height: 1.6; margin: 8px 0; }
    
    .moderation { display: grid; grid-template-columns: 180px 1fr auto; gap: 10px; margin-top: 15px; }
    .moderation select, .moderation input {
        width: 100%;
        padding: 10px 14px;
        border: 1.5px solid var(--line);
        border-radius: var(--radius);
        background: var(--white);
        color: var(--ink);
        font-size: 13.5px;
        outline: none;
    }
    .moderation select:focus, .moderation input:focus { border-color: var(--navy); box-shadow: 0 0 0 3px rgba(27, 58, 92, 0.12); }
    .moderation button {
        background: var(--navy);
        color: var(--white);
        border: 1.5px solid var(--navy);
        border-radius: var(--radius);
        padding: 0 20px;
        font-weight: 700;
        font-size: 13.5px;
        cursor: pointer;
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .moderation button:hover { transform: translate(-2px, -2px); box-shadow: 3px 3px 0 var(--signal); }

    @media (max-width: 700px) {
        .officer-nav { padding: 14px 18px; flex-direction: column; align-items: flex-start; gap: 12px; }
        .officer-nav .nav-left { flex-direction: column; align-items: flex-start; gap: 8px; }
        .main-content { padding: 0 16px; }
        .moderation { grid-template-columns: 1fr; }
    }
    </style>
    <link rel="stylesheet" href="../assets/css/app-theme.css">
</head>
<body>

    <!-- Navbar Horizontal Officer -->
    <nav class="officer-nav">
        <div class="nav-left">
            <h2>CivicAI Officer</h2>
            <div class="nav-links">
                <a href="index.php">Priority Queue</a>
                <a href="heatmap.php">Heatmap & Sebaran</a>
                <a href="post_reports.php" class="active">Report Postingan</a>
            </div>
        </div>
        <div>
            <a href="../auth/logout.php" class="btn btn-logout">Logout</a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h1>Report Postingan</h1>
            <p>Antrean laporan warga terkait hoaks, spam, dan pelanggaran konten.</p>
        </div>

        <div class="card">
            <h3 style="font-family: 'Big Shoulders Display', sans-serif; font-size: 20px; text-transform: uppercase; color: var(--ink);">Filter Status Laporan</h3>
            <div class="filters">
                <?php foreach (['all' => 'Semua', 'pending' => 'Pending', 'reviewed' => 'Ditinjau', 'valid' => 'Valid', 'rejected' => 'Ditolak'] as $key => $label): ?>
                    <a class="<?= $selectedStatus === $key ? 'active' : '' ?>" href="post_reports.php?status=<?= urlencode($key) ?>"><?= $label ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($item = $result->fetch_assoc()): ?>
                    <article class="report">
                        <h3><?= htmlspecialchars($item['post_title']) ?></h3>
                        <div class="meta">Dilaporkan oleh <?= htmlspecialchars($item['reporter_name'] ?? 'User') ?> pada <?= htmlspecialchars($item['created_at']) ?></div>
                        <div class="reason">Alasan: <?= htmlspecialchars($item['reason']) ?></div>
                        <?php if (!empty($item['details'])): ?>
                            <p><strong>Detail Pelapor:</strong> <?= htmlspecialchars($item['details']) ?></p>
                        <?php endif; ?>
                        <p><strong>Isi Postingan Asli:</strong> <?= htmlspecialchars($item['post_description']) ?></p>
                        
                        <form class="moderation" method="POST">
                            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                            <select name="status">
                                <option value="pending" <?= $item['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="reviewed" <?= $item['status'] === 'reviewed' ? 'selected' : '' ?>>Ditinjau</option>
                                <option value="valid" <?= $item['status'] === 'valid' ? 'selected' : '' ?>>Valid</option>
                                <option value="rejected" <?= $item['status'] === 'rejected' ? 'selected' : '' ?>>Ditolak</option>
                            </select>
                            <input type="text" name="officer_notes" value="<?= htmlspecialchars($item['officer_notes'] ?? '') ?>" placeholder="Catatan officer...">
                            <button type="submit" name="moderate_report">Simpan</button>
                        </form>
                    </article>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color: var(--ink-soft); text-align: center; padding: 20px;">Belum ada report postingan pada filter ini.</p>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>