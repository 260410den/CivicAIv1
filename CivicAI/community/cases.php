<?php
require_once __DIR__ . "/../config/database.php";

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
$cases = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $cases[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Case Publik - CivicAI</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background: #f5f7fa; color: #222; }
        
        header { background: white; border-bottom: 1px solid #ddd; padding: 18px 7%; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 24px; font-weight: bold; }
        .logo span { color: #2563eb; }
        header nav a { text-decoration: none; color: #555; margin-left: 20px; font-weight: bold; }
        header nav a:hover { color: #2563eb; }

        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; }
        h1 { font-size: 26px; margin-bottom: 25px; }

        .case-card { background: white; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .case-card h3 { font-size: 20px; margin-bottom: 10px; }
        .info { color: #64748b; font-size: 14px; margin-bottom: 6px; }
        
        .badges { margin: 15px 0; }
        .badge { display: inline-block; padding: 5px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; margin-right: 8px; }
        .status-open { background: #fef08a; color: #854d0e; }
        .status-in-progress { background: #bfdbfe; color: #1e40af; }
        .status-resolved { background: #bbf7d0; color: #166534; }
        .priority-high, .priority-critical { background: #fecaca; color: #991b1b; }
        .priority-medium { background: #fed7aa; color: #9a3412; }
        .priority-low { background: #e2e8f0; color: #334155; }

        .detail-btn { display: flex; align-items: center; justify-content: center; width: 100%; min-height: 40px; background: #2563eb; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: bold; margin-top: 10px; text-align: center; }
        .detail-btn:hover { background: #1d4ed8; }
        .empty { text-align: center; color: #777; padding: 40px; background: white; border-radius: 10px; }

        @media (max-width: 600px) {
            header { padding: 14px 16px; align-items: center; gap: 12px; }
            .logo { white-space: nowrap; }
            header nav { display: flex; flex-wrap: wrap; justify-content: flex-end; align-items: center; gap: 6px 12px; }
            header nav a { margin-left: 0; font-size: 12px; }
            .container { width: 100%; margin: 24px auto; padding: 0 12px; }
            h1 { font-size: 21px; line-height: 1.35; }
            .case-card { padding: 16px; }
            .detail-btn { min-height: 42px; }
        }
    </style>
    <link rel="stylesheet" href="../assets/css/app-theme.css">
</head>
<body>

    <header>
        <div class="logo">Civic<span>AI</span></div>
        <nav>
            <a href="index.php">Laporan Warga</a>
            <a href="cases.php" style="color:#2563eb;">Daftar Case</a>
            <a href="../officer/index.php" target="_blank">Officer Portal →</a>
        </nav>
    </header>

    <div class="container">
        <h1>Daftar Masalah Publik (Case Terpusat)</h1>

        <?php if (count($cases) > 0): ?>
            <?php foreach ($cases as $case): ?>
                <div class="case-card">
                    <h3><?= htmlspecialchars($case["title"]) ?></h3>
                    <div class="info">📂 Kategori: <?= htmlspecialchars($case["category"]) ?></div>
                    <div class="info">📍 Lokasi: <?= htmlspecialchars($case["location"]) ?></div>
                    <div class="info">📑 Menggabungkan: <strong><?= htmlspecialchars($case["report_count"]) ?> Laporan Warga</strong></div>
                    
                    <div class="badges">
                        <span class="badge status-<?= str_replace('_', '-', htmlspecialchars($case["status"])) ?>">
                            Status: <?= strtoupper(htmlspecialchars($case["status"])) ?>
                        </span>
                        <span class="badge priority-<?= htmlspecialchars($case["priority"]) ?>">
                            Prioritas: <?= strtoupper(htmlspecialchars($case["priority"])) ?>
                        </span>
                    </div>

                    <a href="case_detail.php?id=<?= $case["id"] ?>" class="detail-btn">Lihat Detail & Diskusi Warga →</a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty">Belum ada Case yang tercatat saat ini.</div>
        <?php endif; ?>
    </div>

</body>
</html>