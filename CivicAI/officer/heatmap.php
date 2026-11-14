<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'petugas') {
    header("Location: ../auth/login.php?error=Unauthorized");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$allowedCategories = ['all', 'Infrastruktur', 'Kebersihan', 'Drainase', 'Banjir', 'Fasilitas Umum', 'Lingkungan', 'Lainnya'];
$selectedCategory = trim($_GET['category'] ?? 'all');
if (!in_array($selectedCategory, $allowedCategories, true)) {
    $selectedCategory = 'all';
}

$categoryWhere = '';
$categoryParams = [];
if ($selectedCategory !== 'all') {
    $categoryWhere = ' WHERE category = ?';
    $categoryParams[] = $selectedCategory;
}
$activeStatusCondition = "LOWER(status) NOT IN ('selesai', 'resolved', 'closed', 'ditolak', 'rejected')";

$provinceCoordinates = [
    'dki jakarta' => [-6.2088, 106.8456],
    'jawa barat' => [-6.9175, 107.6191],
    'jawa tengah' => [-7.1500, 110.1403],
    'jawa timur' => [-7.5361, 112.2384],
    'di yogyakarta' => [-7.7956, 110.3695],
    'banten' => [-6.4058, 106.0640],
    'bali' => [-8.4095, 115.1889]
];

function estimateCoordinates($location, $provinceCoordinates) {
    $normalizedLocation = strtolower($location);
    foreach ($provinceCoordinates as $province => $coordinates) {
        if (strpos($normalizedLocation, $province) !== false) {
            return $coordinates;
        }
    }
    return [-2.5489, 118.0149];
}

$pointsSql = "SELECT id, title, category, location, latitude, longitude, priority, status, ai_priority_score FROM reports WHERE " . $activeStatusCondition;
if ($selectedCategory !== 'all') {
    $pointsSql .= " AND category = ?";
}
$pointsSql .= " ORDER BY created_at DESC";
if ($selectedCategory === 'all') {
    $pointsSql = "SELECT id, title, category, location, latitude, longitude, priority, status, ai_priority_score FROM reports WHERE " . $activeStatusCondition . " ORDER BY created_at DESC";
}
$pointsStmt = $conn->prepare($pointsSql);
if ($categoryParams) {
    $pointsStmt->bind_param('s', $categoryParams[0]);
}
$pointsStmt->execute();
$pointsResult = $pointsStmt->get_result();
$mapPoints = [];
while ($row = $pointsResult->fetch_assoc()) {
    $hasGps = $row['latitude'] !== null && $row['longitude'] !== null;
    $coordinates = $hasGps
        ? [(float) $row['latitude'], (float) $row['longitude']]
        : estimateCoordinates($row['location'], $provinceCoordinates);
    $mapPoints[] = [
        'id' => (int) $row['id'],
        'title' => $row['title'],
        'category' => $row['category'],
        'location' => $row['location'],
        'latitude' => $coordinates[0],
        'longitude' => $coordinates[1],
        'priority' => $row['priority'],
        'status' => $row['status'],
        'score' => $row['ai_priority_score'] !== null ? (int) $row['ai_priority_score'] : null,
        'is_estimate' => !$hasGps
    ];
}
$pointsStmt->close();

$statsSql = "SELECT location, category, COUNT(*) AS total_cases, SUM(CASE WHEN priority IN ('critical', 'high') THEN 1 ELSE 0 END) AS urgent_cases, SUM(CASE WHEN latitude IS NOT NULL AND longitude IS NOT NULL THEN 1 ELSE 0 END) AS mapped_cases FROM reports" . $categoryWhere . " GROUP BY location, category ORDER BY total_cases DESC";
$statsStmt = $conn->prepare($statsSql);
if ($categoryParams) {
    $statsStmt->bind_param('s', $categoryParams[0]);
}
$statsStmt->execute();
$result = $statsStmt->get_result();
$locations = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $locations[] = $row;
    }
}
$statsStmt->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Heatmap & Sebaran Wilayah - CivicAI Officer</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@700;900&family=IBM+Plex+Mono:wght@400;500;600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
    /* ==========================================================
       CivicAI — Shared Design System
       Dipakai di semua halaman: landing, auth, community, officer.
       Konsep: "tiket laporan warga" — kop resmi + kartu bertumpuk.
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
    img { max-width: 100%; display: block; }

    .display {
        font-family: 'Big Shoulders Display', sans-serif;
        font-weight: 900;
        letter-spacing: -0.5px;
        text-transform: uppercase;
    }
    .mono { font-family: 'IBM Plex Mono', monospace; }

    :focus-visible { outline: 2.5px solid var(--signal); outline-offset: 3px; }

    /* ---------- Officer panel Navbar ---------- */

    .officer-nav { background: var(--ink); color: var(--paper); padding: 16px 8%; display: flex; justify-content: space-between; align-items: center; }
    .officer-nav .nav-left { display: flex; align-items: center; gap: 28px; }
    .officer-nav h2 { font-family: 'Big Shoulders Display', sans-serif; font-weight: 900; text-transform: uppercase; font-size: 19px; color: var(--signal); letter-spacing: -0.2px; }
    .officer-nav .nav-links { display: flex; gap: 6px; }
    .officer-nav .nav-links a { color: #B9C2BC; text-decoration: none; padding: 8px 12px; border-radius: var(--radius); font-size: 13px; font-weight: 600; transition: .15s ease; }
    .officer-nav .nav-links a:hover, .officer-nav .nav-links a.active { background: rgba(232, 99, 28, 0.16); color: var(--white); }
    .officer-nav .btn-logout { background: var(--danger); color: var(--white); border-color: var(--danger); padding: 7px 14px; min-height: unset; font-size: 12.5px; }

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
    .btn-secondary { color: var(--navy); background: transparent; }

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
    
    #report-map { height: 430px; border-radius: var(--radius); overflow: hidden; border: 1.5px solid var(--line); }
    .map-empty { display: flex; height: 100%; align-items: center; justify-content: center; padding: 20px; color: var(--ink-soft); background: var(--paper); text-align: center; }
    .map-toolbar { display: flex; justify-content: space-between; align-items: center; gap: 15px; margin-bottom: 16px; flex-wrap: wrap; }
    .category-filter { min-width: 220px; padding: 10px 14px; border: 1.5px solid var(--line); border-radius: var(--radius); background: var(--white); color: var(--ink); font-size: 14px; outline: none; }
    .category-filter:focus { border-color: var(--navy); box-shadow: 0 0 0 3px rgba(27, 58, 92, 0.12); }
    
    .map-legend { display: flex; flex-wrap: wrap; gap: 8px 14px; margin-top: 14px; color: var(--ink-soft); font-size: 12.5px; }
    .map-legend em { color: var(--ink); font-style: normal; }
    .legend-dot { display: inline-block; width: 10px; height: 10px; margin-right: 4px; border-radius: 50%; }

    /* ---------- Tables ---------- */

    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { padding: 14px 16px; text-align: left; border-bottom: 1.5px solid var(--line); font-size: 14px; }
    th { background: var(--paper); color: var(--navy); font-family: 'IBM Plex Mono', monospace; font-size: 12px; letter-spacing: .6px; text-transform: uppercase; font-weight: 600; }
    tr:hover { background: rgba(237, 234, 224, 0.4); }
    
    .badge-danger { display: inline-block; background: #FBEAE8; color: var(--danger); padding: 4px 10px; border-radius: var(--radius); font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: .3px; border: 1px solid var(--danger); }

    @media (max-width: 700px) {
        .officer-nav { padding: 14px 18px; flex-direction: column; align-items: flex-start; gap: 12px; }
        .officer-nav .nav-left { flex-direction: column; align-items: flex-start; gap: 8px; }
        .main-content { padding: 0 16px; }
        .map-toolbar { flex-direction: column; align-items: stretch; }
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
                <a href="heatmap.php" class="active">Heatmap & Sebaran</a>
                <a href="post_reports.php">Report Postingan</a>
            </div>
        </div>
        <div>
            <a href="../auth/logout.php" class="btn btn-logout" style="border:none;">Logout</a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h1>Heatmap & Analisis Sebaran Wilayah</h1>
            <p>Modul Pemetaan Konsentrasi Masalah Publik</p>
        </div>

        <div class="card">
            <div class="map-toolbar">
                <h3 style="font-family: 'Big Shoulders Display', sans-serif; font-size: 22px; color: var(--ink); text-transform: uppercase;">🗺️ Peta Hotspot Laporan</h3>
                <form method="GET">
                    <select name="category" class="category-filter" onchange="this.form.submit()">
                        <option value="all">Semua kategori</option>
                        <?php foreach (array_slice($allowedCategories, 1) as $category): ?>
                            <option value="<?= htmlspecialchars($category) ?>" <?= $selectedCategory === $category ? 'selected' : '' ?>><?= htmlspecialchars($category) ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <?php if (count($mapPoints) > 0): ?>
                <div id="report-map"></div>
            <?php else: ?>
                <div id="report-map"><div class="map-empty">Belum ada laporan yang dapat ditampilkan.</div></div>
            <?php endif; ?>
            <div class="map-legend">
                <span><i class="legend-dot" style="background:#dc2626"></i>Critical</span>
                <span><i class="legend-dot" style="background:#ea580c"></i>High</span>
                <span><i class="legend-dot" style="background:#eab308"></i>Medium</span>
                <span><i class="legend-dot" style="background:#2563eb"></i>Low</span>
                <span><b><?= count($mapPoints) ?></b> laporan terpetakan</span>
                <span>Titik bertanda estimasi memakai wilayah dari alamat manual</span>
            </div>
        </div>

        <div class="card">
            <h3 style="margin-bottom: 15px; font-family: 'Big Shoulders Display', sans-serif; font-size: 22px; color: var(--ink); text-transform: uppercase;">📊 Statistik Kepadatan Kasus per Lokasi</h3>
            <table>
                <thead>
                    <tr>
                        <th>Lokasi / Wilayah</th>
                        <th>Kategori Dominan</th>
                        <th>Total Case Terdaftar</th>
                        <th>Kasus Urgensi Tinggi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($locations) > 0): ?>
                        <?php foreach ($locations as $loc): ?>
                            <tr>
                                <td><strong>📍 <?= htmlspecialchars($loc["location"]) ?></strong></td>
                                <td><?= htmlspecialchars($loc["category"]) ?></td>
                                <td><?= htmlspecialchars($loc["total_cases"]) ?> Kasus</td>
                                <td>
                                    <?php if ($loc["urgent_cases"] > 0): ?>
                                        <span class="badge-danger"><?= htmlspecialchars($loc["urgent_cases"]) ?> Urgen</span>
                                    <?php else: ?>
                                        <span style="color: var(--sage); font-weight: 600;">Aman</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--ink-soft); padding: 20px;">Belum ada data wilayah untuk dianalisis.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (count($mapPoints) > 0): ?>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            const reportPoints = <?= json_encode($mapPoints, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const map = L.map('report-map', { worldCopyJump: true }).setView([0, 0], 2);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(map);

            const bounds = [];
            const colors = { critical: '#dc2626', high: '#ea580c', medium: '#eab308', low: '#2563eb' };
            reportPoints.forEach(function (point) {
                const coordinate = [point.latitude, point.longitude];
                bounds.push(coordinate);
                const color = colors[point.priority] || colors.medium;
                const radius = Math.max(8, Math.min(24, 8 + ((point.score || 50) / 100) * 16));
                const markerOptions = {
                    radius: radius,
                    color: color,
                    fillColor: color,
                    fillOpacity: 0.55,
                    weight: 2
                };
                if (point.is_estimate) {
                    markerOptions.dashArray = '5, 5';
                    markerOptions.fillOpacity = 0.35;
                }
                L.circleMarker(coordinate, markerOptions).bindPopup('<strong>#' + point.id + ' ' + escapeHtml(point.title) + '</strong><br>' + escapeHtml(point.category) + '<br>Prioritas: ' + escapeHtml(point.priority) + (point.score !== null ? ' (' + point.score + '/100)' : '') + '<br>' + escapeHtml(point.location) + (point.is_estimate ? '<br><em>Lokasi perkiraan wilayah</em>' : '<br><em>Koordinat GPS</em>')).addTo(map);
            });
            map.fitBounds(bounds, { padding: [30, 30] });

            function escapeHtml(value) {
                return String(value).replace(/[&<>"']/g, function (character) {
                    return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[character];
                });
            }
        </script>
    <?php endif; ?>

</body>
</html>