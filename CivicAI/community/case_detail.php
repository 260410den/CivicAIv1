<?php
require_once __DIR__ . "/../config/database.php";

$id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;

if ($id <= 0) {
    die("ID Case tidak valid.");
}

/*
|--------------------------------------------------------------------------
| Ambil data Case
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("SELECT * FROM cases WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Case tidak ditemukan.");
}

$case = $result->fetch_assoc();
$stmt->close();

/*
|--------------------------------------------------------------------------
| Ambil semua laporan yang terhubung ke Case ini
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("SELECT * FROM reports WHERE case_id = ? ORDER BY created_at DESC");
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
| Ambil komentar / diskusi publik untuk Case ini
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("SELECT c.*, u.name FROM comments c LEFT JOIN users u ON c.user_id = u.id WHERE c.case_id = ? ORDER BY c.created_at ASC");
$stmt->bind_param("i", $id);
$stmt->execute();
$comments_result = $stmt->get_result();

$comments = [];
while ($row = $comments_result->fetch_assoc()) {
    $comments[] = $row; 
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Case #<?= $case['id'] ?> - CivicAI</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background: #f5f7fa; color: #222; }
        header { background: white; border-bottom: 1px solid #ddd; padding: 18px 7%; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 24px; font-weight: bold; }
        .logo span { color: #2563eb; }
        header nav a { text-decoration: none; color: #555; margin-left: 20px; font-weight: bold; }
        
        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #2563eb; text-decoration: none; font-weight: bold; }
        
        .card { background: white; border: 1px solid #e2e8f0; border-radius: 10px; padding: 30px; margin-bottom: 25px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .card h1 { font-size: 24px; margin-bottom: 15px; }
        
        .badges { margin-bottom: 20px; }
        .badge { display: inline-block; padding: 5px 12px; border-radius: 15px; font-size: 12px; font-weight: bold; margin-right: 8px; }
        .status-open { background: #fef08a; color: #854d0e; }
        .status-in-progress { background: #bfdbfe; color: #1e40af; }
        .status-resolved { background: #bbf7d0; color: #166534; }
        .priority-high, .priority-critical { background: #fecaca; color: #991b1b; }
        .priority-medium { background: #fed7aa; color: #9a3412; }
        .priority-low { background: #e2e8f0; color: #334155; }
        
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; color: #555; font-size: 14px; }
        .description { font-size: 16px; line-height: 1.6; color: #334155; margin-bottom: 20px; }
        
        h3 { font-size: 18px; margin-bottom: 15px; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px; }
        
        .report-item { background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px; margin-bottom: 12px; }
        .report-item h4 { font-size: 16px; margin-bottom: 5px; color: #1e293b; }
        .report-meta { font-size: 13px; color: #64748b; margin-top: 8px; }

        .comment-box { background: #f8fafc; padding: 12px 15px; border-radius: 8px; margin-bottom: 10px; border: 1px solid #e2e8f0; }
        .comment-user { font-weight: bold; font-size: 13px; color: #2563eb; margin-bottom: 4px; }
        .comment-text { font-size: 14px; color: #334155; }
        
        form textarea { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; margin-bottom: 10px; resize: vertical; }
        form button { background: #2563eb; color: white; border: none; padding: 10px 20px; font-weight: bold; border-radius: 6px; cursor: pointer; }
        form button:hover { background: #1d4ed8; }
        .empty { color: #777; font-style: italic; font-size: 14px; }
    </style>
    <link rel="stylesheet" href="../assets/css/app-theme.css">
</head>
<body>

    <header>
        <div class="logo">Civic<span>AI</span></div>
        <nav>
            <a href="cases.php">← Kembali ke Daftar Case</a>
        </nav>
    </header>

    <div class="container">
        <a href="cases.php" class="back-link">← Daftar Case Publik</a>

        <div class="card">
            <h1><?= htmlspecialchars($case["title"]) ?></h1>
            
            <div class="badges">
                <span class="badge status-<?= str_replace('_', '-', htmlspecialchars($case["status"])) ?>">
                    Status: <?= strtoupper(htmlspecialchars($case["status"])) ?>
                </span>
                <span class="badge priority-<?= htmlspecialchars($case["priority"]) ?>">
                    Prioritas: <?= strtoupper(htmlspecialchars($case["priority"])) ?>
                </span>
            </div>

            <div class="info-grid">
                <div>📂 <strong>Kategori:</strong> <?= htmlspecialchars($case["category"]) ?></div>
                <div>📍 <strong>Lokasi:</strong> <?= htmlspecialchars($case["location"]) ?></div>
            </div>

            <p class="description"><?= nl2br(htmlspecialchars($case["description"])) ?></p>
        </div>

        <div class="card">
            <h3>📑 Laporan Warga yang Tergabung (<?= count($reports) ?>)</h3>
            <?php if (count($reports) > 0): ?>
                <?php foreach ($reports as $rep): ?>
                    <div class="report-item">
                        <h4><?= htmlspecialchars($rep["title"]) ?></h4>
                        <p style="font-size: 14px; color: #555;"><?= htmlspecialchars($rep["description"]) ?></p>
                        <div class="report-meta">
                            🕒 Dilaporkan pada: <?= htmlspecialchars($rep["created_at"]) ?> | Status Laporan: <?= htmlspecialchars($rep["status"]) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="empty">Belum ada laporan spesifik yang terhubung ke Case ini.</p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>💬 Diskusi & Komentar Warga</h3>
            
            <?php if (count($comments) > 0): ?>
                <?php foreach ($comments as $com): ?>
                    <div class="comment-box">
                        <div class="comment-user"><?= htmlspecialchars($com["name"] ?? "Warga CivicAI") ?> <span style="font-weight:normal; color:#888; font-size:11px;">(<?= htmlspecialchars($com["created_at"]) ?>)</span></div>
                        <div class="comment-text"><?= nl2br(htmlspecialchars($com["comment"])) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="empty" style="margin-bottom: 20px;">Belum ada diskusi pada Case ini. Jadilah yang pertama memberikan komentar!</p>
            <?php endif; ?>

            <form action="../api/comments.php" method="POST" style="margin-top: 20px;" onsubmit="setTimeout(() => window.location.reload(), 500);">
                <input type="hidden" name="case_id" value="<?= $case['id'] ?>">
                <input type="hidden" name="user_id" value="1">
                <textarea name="comment" rows="3" placeholder="Tulis komentar atau informasi tambahan terkait masalah ini..." required></textarea>
                <button type="submit">Kirim Komentar</button>
            </form>
        </div>
    </div>

</body>
</html>