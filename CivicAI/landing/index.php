<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$dashboardUrl = '../community/index.php';
if (($_SESSION['user_role'] ?? '') === 'petugas') {
    $dashboardUrl = '../officer/index.php';
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f4f2ec">
    <meta name="description" content="CivicAI membantu warga menyampaikan masalah lingkungan dan mengikuti tindak lanjutnya dengan lebih terbuka.">
    <title>CivicAI — Dari suara warga menjadi aksi nyata</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/site.css">
    <script src="../assets/js/site.js" defer></script>
</head>
<body>
    <header class="site-header">
        <a class="brand" href="index.php" aria-label="CivicAI, beranda">
            <span class="brand-mark" aria-hidden="true"><span></span><span></span><span></span></span>
            <span>Civic<span class="brand-ai">AI</span></span>
        </a>
        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="primary-nav" aria-label="Buka menu"><span></span><span></span></button>
        <nav class="primary-nav" id="primary-nav" aria-label="Navigasi utama">
            <a href="#cara-kerja">Cara kerja</a>
            <a href="#untuk-warga">Untuk warga</a>
            <a href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>">Jelajahi laporan</a>
            <?php if ($isLoggedIn): ?>
                <a class="nav-cta" href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>">Dashboard <span aria-hidden="true">↗</span></a>
            <?php else: ?>
                <a class="nav-login" href="../auth/login.php">Masuk</a>
                <a class="nav-cta" href="../auth/register.php">Mulai melapor <span aria-hidden="true">↗</span></a>
            <?php endif; ?>
        </nav>
    </header>

    <main>
        <section class="hero">
            <div class="hero-copy reveal">
                <p class="eyebrow"><span class="eyebrow-dot"></span> Platform partisipasi warga</p>
                <h1>Lingkungan yang lebih baik <em>dimulai dari suara Anda.</em></h1>
                <p class="hero-description">Sampaikan masalah di sekitar Anda, bantu petugas memahami prioritas, lalu ikuti perkembangannya dalam satu ruang yang terbuka.</p>
                <div class="hero-actions">
                    <a class="button button-dark" href="<?= $isLoggedIn ? '../community/create.php' : '../auth/register.php' ?>">Sampaikan laporan <span aria-hidden="true">↗</span></a>
                    <a class="text-link" href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>">Lihat ruang laporan <span aria-hidden="true">→</span></a>
                </div>
                <div class="hero-footnote"><span class="footnote-line"></span><span>Warga bersuara. Kota bergerak.</span></div>
            </div>

            <div class="hero-visual reveal reveal-delay" aria-label="Ilustrasi alur laporan CivicAI">
                <div class="visual-orbit orbit-one"></div><div class="visual-orbit orbit-two"></div>
                <svg class="city-map" viewBox="0 0 620 520" role="img" aria-labelledby="map-title">
                    <title id="map-title">Ilustrasi peta kawasan dan titik laporan</title>
                    <path class="map-block" d="M70 55h140v100H70zM245 45h120v145H245zM405 70h145v95H405zM80 190h110v145H80zM220 220h170v100H220zM425 195h120v140H425zM60 370h145v100H60zM230 350h125v110H230zM385 365h165v105H385z" />
                    <path class="map-road" d="M30 175h560M210 25v460M390 25v460M45 345h530M395 30l-190 455" />
                    <path class="map-river" d="M5 285c100-55 140 70 245 10s155-70 365 8" />
                    <circle class="map-point point-one" cx="152" cy="245" r="8"/><circle class="map-ring" cx="152" cy="245" r="18"/>
                    <circle class="map-point point-two" cx="322" cy="142" r="8"/><circle class="map-ring ring-two" cx="322" cy="142" r="18"/>
                    <circle class="map-point point-three" cx="470" cy="393" r="8"/><circle class="map-ring ring-three" cx="470" cy="393" r="18"/>
                </svg>
                <div class="map-label label-top"><span class="label-dot"></span> Laporan diterima <span class="label-number">01</span></div>
                <div class="map-label label-bottom"><span class="label-dot label-dot-green"></span> Dalam tindak lanjut <span class="label-number">02</span></div>
                <div class="map-caption"><span class="caption-mark">CA</span><span><strong>Ruang bersama</strong><small>Masalah lokal, perhatian kolektif</small></span></div>
                <span class="visual-coordinate">06°12′ S&nbsp; 106°49′ E</span>
            </div>
        </section>

        <section class="principle-strip" aria-label="Prinsip CivicAI">
            <div><span class="strip-number">01</span><span>Dengar lebih dekat</span></div>
            <div><span class="strip-number">02</span><span>Pahami konteks</span></div>
            <div><span class="strip-number">03</span><span>Tindak lanjuti bersama</span></div>
        </section>

        <section class="section process-section" id="cara-kerja">
            <div class="section-intro reveal">
                <p class="eyebrow">Sederhana dan terbuka</p>
                <h2>Dari cerita singkat<br>ke langkah yang jelas.</h2>
                <p>CivicAI merapikan proses pelaporan agar warga dan petugas punya pemahaman yang sama tentang masalah dan penanganannya.</p>
            </div>
            <div class="process-list">
                <article class="process-item reveal">
                    <span class="process-index">01</span><div><h3>Ceritakan kondisi di sekitar</h3><p>Tuliskan lokasi dan situasi yang Anda temui. Tambahkan foto sebagai konteks bila tersedia.</p></div><span class="process-arrow" aria-hidden="true">↗</span>
                </article>
                <article class="process-item reveal">
                    <span class="process-index">02</span><div><h3>AI membantu menyusun prioritas</h3><p>Analisis memberi petunjuk awal tentang kategori dan urgensi. Petugas tetap menentukan tindak lanjut.</p></div><span class="process-arrow" aria-hidden="true">↗</span>
                </article>
                <article class="process-item reveal">
                    <span class="process-index">03</span><div><h3>Pantau perkembangan bersama</h3><p>Status dan tanggapan dapat diikuti warga, supaya informasi tetap tersambung sepanjang proses.</p></div><span class="process-arrow" aria-hidden="true">↗</span>
                </article>
            </div>
        </section>

        <section class="community-section" id="untuk-warga">
            <div class="community-art reveal" aria-hidden="true">
                <div class="community-card card-back"><span class="mini-label">CATATAN LINGKUNGAN</span><span class="card-scribble"></span><span class="card-scribble short"></span><span class="card-pin">+</span></div>
                <div class="community-card card-front"><span class="mini-label">SUARA WARGA</span><strong>“Jalan depan sekolah<br>perlu penerangan.”</strong><span class="card-meta"><span></span> Laporan warga</span></div>
                <div class="community-seal"><span>CA</span><small>BERSAMA<br>WARGA</small></div>
            </div>
            <div class="community-copy reveal">
                <p class="eyebrow">Ruang partisipasi</p>
                <h2>Suara kecil bisa membuka perubahan yang berarti.</h2>
                <p>Ikuti diskusi laporan, dukung masalah yang juga dirasakan, dan lihat respons petugas tanpa kehilangan konteks.</p>
                <a class="button button-light" href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>">Jelajahi laporan <span aria-hidden="true">↗</span></a>
            </div>
        </section>

        <section class="closing-section reveal">
            <div><p class="eyebrow">Mulai dari lingkungan Anda</p><h2>Ada yang perlu dibenahi?<br>Mari sampaikan bersama.</h2></div>
            <a class="button button-dark" href="<?= $isLoggedIn ? '../community/create.php' : '../auth/register.php' ?>">Buat laporan pertama <span aria-hidden="true">↗</span></a>
        </section>
    </main>

    <footer class="site-footer"><a class="brand footer-brand" href="index.php"><span class="brand-mark" aria-hidden="true"><span></span><span></span><span></span></span><span>Civic<span class="brand-ai">AI</span></span></a><p>Teknologi untuk percakapan warga dan kota yang lebih baik.</p><span class="footer-copyright">© <?= date('Y') ?> CivicAI</span></footer>
</body>
</html>
