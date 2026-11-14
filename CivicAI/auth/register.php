<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once __DIR__ . "/../config/database.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";
    $role = 'warga';

    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Semua kolom wajib diisi!";
    } elseif ($password !== $confirm_password) {
        $error = "Konfirmasi password tidak sama dengan password. Coba periksa lagi ketikan Anda.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";
    } elseif (strlen($password) < 8) {
        $error = "Password minimal 8 karakter.";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Email sudah terdaftar, silakan gunakan email lain atau login.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);

            if ($stmt->execute()) {
                $success = "Registrasi berhasil! Silakan <a href='login.php'>Login di sini</a>.";
            } else {
                $error = "Terjadi kesalahan pada database: " . $stmt->error;
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Warga - CivicAI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@700;900&family=IBM+Plex+Mono:wght@400;500;600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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

    /* ---------- Header / nav (dipakai di semua halaman publik) ---------- */

    .site-header {
        position: sticky;
        top: 0;
        z-index: 20;
        display: flex;
        align-items: center;
        justify-content: space-between;
        max-width: 1200px;
        margin: 0 auto;
        padding: 18px 28px;
        backdrop-filter: blur(8px);
        background: rgba(237, 234, 224, 0.86);
        border-bottom: 1px solid var(--line);
    }
    .brand {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-family: 'Big Shoulders Display', sans-serif;
        font-weight: 900;
        font-size: 24px;
        letter-spacing: -0.5px;
        text-transform: uppercase;
    }
    .brand-chip {
        font-family: 'IBM Plex Mono', monospace;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 1px;
        color: var(--white);
        background: var(--signal);
        padding: 3px 7px;
        border-radius: 3px;
    }
    .header-actions { display: flex; align-items: center; gap: 10px; }
    .link-ghost {
        font-size: 13.5px;
        font-weight: 600;
        color: var(--navy);
        padding: 9px 6px;
        border-bottom: 1.5px solid transparent;
        transition: border-color .15s ease;
    }
    .link-ghost:hover { border-color: var(--navy); }

    /* ---------- Buttons ---------- */

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
    .btn:disabled { opacity: .55; cursor: not-allowed; transform: none; box-shadow: none; }
    .btn-primary { color: var(--white); background: var(--navy); }
    .btn-secondary { color: var(--navy); background: transparent; }
    .btn-danger { color: var(--white); background: var(--danger); border-color: var(--danger); }
    .btn-block { width: 100%; }

    /* ---------- Generic card ---------- */

    .card {
        background: var(--white);
        border: 1.5px solid var(--ink);
        border-radius: var(--radius-lg);
        padding: 26px;
    }

    /* ---------- Alerts ---------- */

    .alert-success, .alert-danger {
        padding: 13px 16px;
        border-radius: var(--radius);
        margin-bottom: 18px;
        font-size: 13.5px;
        font-weight: 600;
        border: 1.5px solid;
    }
    .alert-success { background: #EAF3EB; color: var(--sage); border-color: var(--sage); }
    .alert-danger { background: #FBEAE8; color: var(--danger); border-color: var(--danger); }

    /* ---------- Auth pages (login/register) ---------- */

    .auth-shell {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }
    .auth-main {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
    }
    .auth-card {
        width: 100%;
        max-width: 420px;
        background: var(--white);
        border: 1.5px solid var(--ink);
        border-radius: var(--radius-lg);
        padding: 38px 34px;
        box-shadow: 7px 7px 0 var(--paper-deep);
    }
    .auth-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        font-family: 'IBM Plex Mono', monospace;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 1.5px;
        color: var(--signal);
        text-transform: uppercase;
        margin-bottom: 10px;
    }
    .auth-eyebrow::before { content: ''; width: 18px; height: 2px; background: var(--signal); }
    .auth-card h1 {
        font-family: 'Big Shoulders Display', sans-serif;
        font-weight: 900;
        text-transform: uppercase;
        font-size: 28px;
        letter-spacing: -0.3px;
        margin-bottom: 26px;
    }

    .form-group { margin-bottom: 18px; }
    .form-group label {
        display: block;
        font-family: 'IBM Plex Mono', monospace;
        font-weight: 600;
        font-size: 11.5px;
        letter-spacing: .6px;
        text-transform: uppercase;
        margin-bottom: 7px;
        color: var(--ink-soft);
    }
    .form-group input {
        width: 100%;
        padding: 12px 14px;
        border: 1.5px solid var(--line);
        border-radius: var(--radius);
        font-size: 14.5px;
        font-family: 'Inter', Arial, sans-serif;
        color: var(--ink);
        background: var(--white);
        outline: none;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .form-group input:focus {
        border-color: var(--navy);
        box-shadow: 0 0 0 3px rgba(27, 58, 92, 0.12);
    }

    .password-field { position: relative; }
    .password-field input { padding-right: 78px; }
    .toggle-pw {
        position: absolute;
        right: 6px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        font-family: 'IBM Plex Mono', monospace;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: .4px;
        text-transform: uppercase;
        color: var(--navy);
        cursor: pointer;
        padding: 6px 8px;
        border-radius: var(--radius);
    }
    .toggle-pw:hover { background: var(--paper); }

    .match-note {
        margin-top: 7px;
        font-size: 12.5px;
        font-weight: 600;
        min-height: 16px;
    }
    .match-note.match-ok { color: var(--sage); }
    .match-note.match-bad { color: var(--danger); }

    .auth-footer-link {
        text-align: center;
        margin-top: 22px;
        font-size: 13px;
        color: var(--ink-soft);
    }
    .auth-footer-link a { color: var(--navy); font-weight: 700; }
    .auth-footer-link a:hover { text-decoration: underline; }
    .auth-footer-link + .auth-footer-link { margin-top: 10px; }

    /* ---------- Landing: hero stack / ticket rail ---------- */

    .hero {
        max-width: 1200px; margin: 0 auto; padding: 64px 28px 30px;
        display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(300px, 0.9fr);
        gap: 56px; align-items: center;
    }
    .eyebrow {
        display: inline-flex; align-items: center; gap: 9px;
        font-family: 'IBM Plex Mono', monospace; font-size: 11.5px; font-weight: 600;
        letter-spacing: 1.5px; color: var(--signal); text-transform: uppercase; margin-bottom: 18px;
    }
    .eyebrow::before { content: ''; width: 22px; height: 2px; background: var(--signal); }
    .hero-stack { display: flex; flex-direction: column; gap: 2px; margin-bottom: 26px; }
    .hero-line { display: flex; align-items: baseline; gap: 14px; border-bottom: 1px solid var(--line); padding: 6px 0; }
    .hero-line:last-child { border-bottom: none; }
    .hero-line .step-tag { font-family: 'IBM Plex Mono', monospace; font-size: 12px; font-weight: 600; color: var(--ink-soft); min-width: 24px; }
    .hero-line .display { font-size: clamp(32px, 5.6vw, 54px); line-height: 1; }
    .hero-line:nth-child(2) .display { color: var(--signal); }
    .hero-line:nth-child(3) .display { color: var(--navy); }
    .hero-copy { max-width: 500px; color: var(--ink-soft); font-size: 16.5px; line-height: 1.7; margin-bottom: 30px; }
    .hero-actions { display: flex; flex-wrap: wrap; gap: 12px; }
    .hero-actions .btn { min-height: 48px; padding: 0 22px; font-size: 14px; }

    .ticket-stack { position: relative; min-height: 420px; }
    .ticket {
        position: relative; background: var(--white); border: 1.5px solid var(--ink); border-radius: 4px;
        padding: 16px 18px 18px; margin-bottom: 16px; box-shadow: 6px 6px 0 rgba(28, 35, 33, 0.08);
        animation: ticketIn .5s ease both;
    }
    .ticket:nth-child(1) { transform: rotate(-1.6deg); animation-delay: .05s; }
    .ticket:nth-child(2) { transform: rotate(1deg); margin-left: 26px; animation-delay: .18s; }
    .ticket:nth-child(3) { transform: rotate(-0.8deg); animation-delay: .31s; }
    .ticket:nth-child(4) { transform: rotate(1.4deg); margin-left: 14px; animation-delay: .44s; }
    .ticket::before {
        content: ''; position: absolute; left: -7px; top: 50%; width: 14px; height: 14px;
        background: var(--paper); border: 1.5px solid var(--ink); border-radius: 50%; transform: translateY(-50%);
    }
    .ticket-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
    .ticket-id { font-family: 'IBM Plex Mono', monospace; font-size: 11px; color: var(--ink-soft); }
    .ticket-stamp {
        font-family: 'IBM Plex Mono', monospace; font-size: 10.5px; font-weight: 600; letter-spacing: .6px;
        text-transform: uppercase; padding: 3px 8px; border-radius: 20px; border: 1.5px solid currentColor;
    }
    .stamp-submitted { color: var(--ink-soft); }
    .stamp-ai { color: var(--signal); }
    .stamp-assigned { color: var(--navy); }
    .stamp-done { color: var(--sage); }
    .ticket-title { font-size: 14.5px; font-weight: 700; margin-bottom: 4px; }
    .ticket-meta { font-size: 12.5px; color: var(--ink-soft); }

    @keyframes ticketIn { from { opacity: 0; transform: translateY(14px) rotate(0deg); } }
    @media (prefers-reduced-motion: reduce) { .ticket { animation: none; } html { scroll-behavior: auto; } }

    /* ---------- Section shell ---------- */

    .section { max-width: 1200px; margin: 0 auto; padding: 60px 28px; }
    .section-head { max-width: 640px; margin-bottom: 36px; }
    .section-head .eyebrow { margin-bottom: 10px; }
    .section-head h2 {
        font-family: 'Big Shoulders Display', sans-serif; font-weight: 900; text-transform: uppercase;
        font-size: clamp(26px, 3.4vw, 38px); letter-spacing: -0.5px; margin-bottom: 12px;
    }
    .section-head p { color: var(--ink-soft); font-size: 15.5px; line-height: 1.7; }

    .steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0; border-top: 1.5px solid var(--ink); border-bottom: 1.5px solid var(--ink); }
    .step { padding: 26px 24px; border-left: 1.5px solid var(--ink); }
    .step:first-child { border-left: none; }
    .step .step-num { font-family: 'IBM Plex Mono', monospace; font-size: 12.5px; font-weight: 600; color: var(--signal); margin-bottom: 14px; display: block; }
    .step h3 { font-family: 'Big Shoulders Display', sans-serif; font-weight: 900; text-transform: uppercase; font-size: 21px; letter-spacing: -0.3px; margin-bottom: 10px; }
    .step p { font-size: 14px; color: var(--ink-soft); line-height: 1.7; }

    .chip-row { display: flex; flex-wrap: wrap; gap: 10px; }
    .chip { font-family: 'IBM Plex Mono', monospace; font-size: 12.5px; font-weight: 500; padding: 9px 14px; border: 1.5px solid var(--ink); border-radius: 20px; background: var(--white); }

    .faq-list { display: flex; flex-direction: column; gap: 0; border-top: 1.5px solid var(--line); }
    .faq-item { padding: 20px 0; border-bottom: 1.5px solid var(--line); }
    .faq-item h3 { font-size: 15.5px; font-weight: 700; margin-bottom: 8px; }
    .faq-item p { font-size: 14px; color: var(--ink-soft); line-height: 1.7; }

    .info-card {
        background: var(--white); border: 1.5px solid var(--ink); border-radius: var(--radius-lg);
        padding: 32px; display: flex; flex-direction: column; gap: 16px; max-width: 640px;
    }
    .info-tag { font-family: 'IBM Plex Mono', monospace; font-size: 11px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; color: var(--ink-soft); }
    .info-card h3 { font-family: 'Big Shoulders Display', sans-serif; font-weight: 900; text-transform: uppercase; font-size: 26px; }
    .info-card p { color: var(--ink-soft); font-size: 14.5px; line-height: 1.7; }
    .info-card ul { list-style: none; display: flex; flex-direction: column; gap: 10px; margin: 4px 0; }
    .info-card li { font-size: 14px; color: var(--ink); padding-left: 20px; position: relative; }
    .info-card li::before { content: '—'; position: absolute; left: 0; color: var(--signal); font-weight: 700; }
    .info-card .btn { align-self: flex-start; }

    .cta-band { background: var(--navy); color: var(--paper); }
    .cta-inner { max-width: 1200px; margin: 0 auto; padding: 56px 28px; display: flex; align-items: center; justify-content: space-between; gap: 24px; flex-wrap: wrap; }
    .cta-inner h2 { font-family: 'Big Shoulders Display', sans-serif; font-weight: 900; text-transform: uppercase; font-size: clamp(24px, 3.2vw, 34px); max-width: 480px; }
    .cta-band .btn-primary { background: var(--signal); border-color: var(--signal); color: var(--white); }
    .cta-band .btn-primary:hover { box-shadow: 3px 3px 0 var(--paper); }

    footer.site-footer { max-width: 1200px; margin: 0 auto; padding: 26px 28px 40px; display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; font-size: 12.5px; color: var(--ink-soft); }

    /* ---------- Officer panel ---------- */

    .officer-nav { background: var(--ink); color: var(--paper); padding: 16px 8%; display: flex; justify-content: space-between; align-items: center; }
    .officer-nav .nav-left { display: flex; align-items: center; gap: 28px; }
    .officer-nav h2 { font-family: 'Big Shoulders Display', sans-serif; font-weight: 900; text-transform: uppercase; font-size: 19px; color: var(--signal); letter-spacing: -0.2px; }
    .officer-nav .nav-links { display: flex; gap: 6px; }
    .officer-nav .nav-links a { color: #B9C2BC; text-decoration: none; padding: 8px 12px; border-radius: var(--radius); font-size: 13px; font-weight: 600; transition: .15s ease; }
    .officer-nav .nav-links a:hover, .officer-nav .nav-links a.active { background: rgba(232, 99, 28, 0.16); color: var(--white); }
    .officer-nav .btn-logout { background: var(--danger); color: var(--white); border-color: var(--danger); padding: 7px 14px; min-height: unset; font-size: 12.5px; }

    .badge { display: inline-block; padding: 4px 10px; font-size: 11px; font-weight: 700; border-radius: var(--radius); text-transform: uppercase; letter-spacing: .3px; }
    .badge-critical { background: #FBEAE8; color: var(--danger); }
    .badge-high { background: #FCEADD; color: var(--signal); }
    .badge-medium { background: #FBF3D9; color: #8A6A11; }
    .badge-low { background: #EDEAE0; color: var(--ink-soft); }
    .badge-category { background: #E4ECF3; color: var(--navy); }
    .badge-success { background: #E7F1E8; color: var(--sage); }

    .ai-box { padding: 16px; border: 1.5px solid var(--signal); border-radius: var(--radius-lg); background: #FFF6EF; color: var(--ink); font-size: 13px; margin-bottom: 16px; }
    .ai-box strong { display: block; margin-bottom: 10px; color: var(--signal); font-family: 'IBM Plex Mono', monospace; font-size: 11px; letter-spacing: .6px; text-transform: uppercase; }
    .ai-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px 14px; margin-bottom: 10px; }
    .ai-box p { line-height: 1.6; }

    .preview-panel { border: 1.5px dashed var(--line); border-radius: var(--radius-lg); padding: 18px; background: var(--paper); }
    .preview-panel .preview-label { font-family: 'IBM Plex Mono', monospace; font-size: 10.5px; letter-spacing: 1px; text-transform: uppercase; color: var(--ink-soft); margin-bottom: 10px; }
    .preview-officer-box { background: #EAF3EB; border-left: 4px solid var(--sage); padding: 10px 14px; border-radius: 6px; font-size: 13.5px; color: var(--sage); }

    @media (max-width: 860px) {
        .hero { grid-template-columns: 1fr; padding-top: 40px; gap: 40px; }
        .steps { grid-template-columns: 1fr; }
        .step { border-left: none; border-top: 1.5px solid var(--ink); }
        .step:first-child { border-top: none; }
    }
    @media (max-width: 560px) {
        .site-header { padding: 14px 18px; }
        .header-actions .link-ghost { display: none; }
        .hero, .section { padding-left: 18px; padding-right: 18px; }
        .cta-inner { flex-direction: column; align-items: flex-start; }
        .cta-inner .btn { width: 100%; }
    }
    </style>
    <link rel="stylesheet" href="../assets/css/app-theme.css">
</head>
<body>
<div class="auth-shell">
    <header class="site-header">
        <a class="brand" href="../landing/index.php">Civic<span class="brand-chip">AI</span></a>
        <div class="header-actions">
            <a class="link-ghost" href="../landing/index.php">← Beranda</a>
        </div>
    </header>

    <main class="auth-main">
        <div class="auth-card">
            <span class="auth-eyebrow">Daftar akun warga</span>
            <h1>Buat akun baru</h1>

            <?php if (!empty($error)): ?>
                <div class="alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert-success"><?= $success ?></div>
            <?php endif; ?>

            <form action="" method="POST" id="register-form" novalidate>
                <div class="form-group">
                    <label for="name">Nama Lengkap</label>
                    <input type="text" id="name" name="name" placeholder="Nama Anda" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="nama@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-field">
                        <input type="password" id="password" name="password" placeholder="Minimal 8 karakter" required minlength="8">
                        <button type="button" class="toggle-pw" data-target="password">Lihat</button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Konfirmasi Password</label>
                    <div class="password-field">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Ketik ulang password" required>
                        <button type="button" class="toggle-pw" data-target="confirm_password">Lihat</button>
                    </div>
                    <div class="match-note" id="match-note" aria-live="polite"></div>
                </div>

                <button type="submit" class="btn btn-primary btn-block" id="submit-btn">Daftar Sekarang</button>
            </form>

            <div class="auth-footer-link">
                Sudah punya akun? <a href="login.php">Login di sini</a>
            </div>
        </div>
    </main>
</div>

<script>
    // Toggle lihat/sembunyi password
    document.querySelectorAll('.toggle-pw').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.dataset.target);
            var showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            btn.textContent = showing ? 'Lihat' : 'Sembunyi';
        });
    });

    // Cross-check konfirmasi password secara real-time, mencegah typo lolos submit
    var passwordInput = document.getElementById('password');
    var confirmInput = document.getElementById('confirm_password');
    var matchNote = document.getElementById('match-note');
    var submitBtn = document.getElementById('submit-btn');
    var form = document.getElementById('register-form');

    function checkPasswordMatch() {
        if (!confirmInput.value) {
            matchNote.textContent = '';
            matchNote.className = 'match-note';
            return true;
        }
        if (passwordInput.value === confirmInput.value) {
            matchNote.textContent = 'Password cocok.';
            matchNote.className = 'match-note match-ok';
            return true;
        }
        matchNote.textContent = 'Password belum sama — periksa lagi ketikan Anda sebelum mendaftar.';
        matchNote.className = 'match-note match-bad';
        return false;
    }

    passwordInput.addEventListener('input', checkPasswordMatch);
    confirmInput.addEventListener('input', checkPasswordMatch);

    form.addEventListener('submit', function (e) {
        if (passwordInput.value.length > 0 && !checkPasswordMatch()) {
            e.preventDefault();
            confirmInput.focus();
        }
    });
</script>
</body>
</html>
