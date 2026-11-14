<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');

session_start();
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../api/ai_service.php";

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'petugas') {
    die("<div style='text-align:center; padding:50px; font-family:sans-serif;'><h2>Akses Ditolak</h2><p>Halaman ini hanya dapat diakses oleh Petugas.</p><a href='../auth/login.php'>Login Petugas</a></div>");
}

$report_id = intval($_GET['id'] ?? 0);

if ($report_id <= 0) {
    die("<div style='text-align:center; padding:50px; font-family:sans-serif;'><h2>ID Laporan tidak valid.</h2><a href='index.php'>Kembali ke Dashboard</a></div>");
}

$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

// Handle Form Submission (Update Status & Catatan Petugas)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_action'])) {
    $status = trim($_POST['status'] ?? '');
    $officer_notes = trim($_POST['officer_notes'] ?? '');

    if (!empty($status)) {
        $update_stmt = $conn->prepare("UPDATE reports SET status = ?, officer_notes = ? WHERE id = ?");
        if ($update_stmt) {
            $update_stmt->bind_param("ssi", $status, $officer_notes, $report_id);
            if ($update_stmt->execute()) {
                $update_stmt->close();

                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        "success" => true,
                        "message" => "Perubahan status dan catatan berhasil disimpan, dan langsung tampil di halaman warga.",
                        "status" => $status,
                        "officer_notes" => $officer_notes
                    ]);
                    exit;
                }

                // Fallback non-JS: kembali ke halaman ini sendiri (bukan ke index.php)
                // supaya pesan sukses benar-benar tampil ke petugas.
                header("Location: case_action.php?id=" . $report_id . "&success=1");
                exit;
            } else {
                $error_msg = "Gagal memperbarui data: " . $update_stmt->error;
            }
        } else {
            $error_msg = "Gagal mempersiapkan query: " . $conn->error;
        }
    } else {
        $error_msg = "Status laporan tidak boleh kosong.";
    }

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            "success" => false,
            "message" => $error_msg ?? "Gagal menyimpan perubahan."
        ]);
        exit;
    }
}

// Ambil data laporan berdasarkan ID
$stmt = $conn->prepare("SELECT * FROM reports WHERE id = ?");
if (!$stmt) {
    die("Query error: " . $conn->error);
}
$stmt->bind_param("i", $report_id);
$stmt->execute();
$report = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$report) {
    die("<div style='text-align:center; padding:50px; font-family:sans-serif;'><h2>Laporan tidak ditemukan.</h2><a href='index.php'>Kembali ke Dashboard</a></div>");
}

if (empty($report['ai_priority_score']) || empty($report['ai_problem'])) {
    $ai_analysis = AIService::analyzeReportText($report['title'], $report['description']);
    $ai_update = $conn->prepare("UPDATE reports SET priority = ?, ai_summary = ?, ai_category = ?, ai_priority = ?, ai_priority_score = ?, ai_priority_reasoning = ?, ai_problem = ?, ai_duration = ?, ai_risk = ?, ai_location_context = ? WHERE id = ?");
    if ($ai_update) {
        $ai_update->bind_param("ssssisssssi", $ai_analysis['recommended_priority'], $ai_analysis['summary'], $ai_analysis['problem'], $ai_analysis['recommended_priority'], $ai_analysis['priority_score'], $ai_analysis['ai_reasoning'], $ai_analysis['problem'], $ai_analysis['duration'], $ai_analysis['risk'], $ai_analysis['location_context'], $report_id);
        $ai_update->execute();
        $ai_update->close();
        $report['priority'] = $ai_analysis['recommended_priority'];
        $report['ai_summary'] = $ai_analysis['summary'];
        $report['ai_category'] = $ai_analysis['problem'];
        $report['ai_priority'] = $ai_analysis['recommended_priority'];
        $report['ai_priority_score'] = $ai_analysis['priority_score'];
        $report['ai_priority_reasoning'] = $ai_analysis['ai_reasoning'];
        $report['ai_problem'] = $ai_analysis['problem'];
        $report['ai_duration'] = $ai_analysis['duration'];
        $report['ai_risk'] = $ai_analysis['risk'];
        $report['ai_location_context'] = $ai_analysis['location_context'];
    }
}

$statusLabels = ['pending' => 'Pending', 'proses' => 'Diproses', 'selesai' => 'Selesai', 'ditolak' => 'Ditolak'];
$currentStatusLabel = $statusLabels[$report['status']] ?? ucfirst($report['status']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tindak Lanjut Kasus #<?= $report['id'] ?> - CivicAI Officer</title>
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

        /* ---------- Halaman ini: Tindak Lanjut Kasus ---------- */
        .container { max-width: 800px; margin: 40px auto; padding: 0 20px; }
        .back-link { display: inline-flex; align-items: center; margin-bottom: 20px; color: var(--navy); text-decoration: none; font-weight: 600; font-size: 14px; }
        .action-layout { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 20px; align-items: start; }
        .card { margin-bottom: 25px; }
        .card h2 { font-family: 'Big Shoulders Display', sans-serif; font-weight: 900; text-transform: uppercase; font-size: 20px; color: var(--ink); margin-bottom: 15px; border-bottom: 2px solid var(--paper); padding-bottom: 10px; }
        .report-title { font-size: 20px; font-weight: bold; color: var(--ink); margin: 10px 0 8px; }
        .report-desc { font-size: 15px; color: var(--ink-soft); margin-bottom: 15px; background: var(--paper); padding: 12px; border-radius: 8px; border: 1px solid var(--line); }
        .img-preview { display: block; width: auto; max-width: 100%; max-height: 300px; object-fit: contain; border-radius: 8px; margin: 0 auto 15px; }
        .img-preview.landscape { width: 100%; max-width: 100%; max-height: none; margin-left: 0; margin-right: 0; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; color: var(--ink); }
        .form-group select, .form-group textarea { width: 100%; padding: 10px 12px; border: 1.5px solid var(--line); border-radius: 8px; font-size: 14px; outline: none; font-family: inherit; }
        .form-group select:focus, .form-group textarea:focus { border-color: var(--navy); box-shadow: 0 0 0 3px rgba(27,58,92,0.1); }
        .form-group textarea { height: 100px; resize: vertical; }
        #form-alert[hidden] { display: none; }
        @media (max-width: 700px) {
            .container { width: 100%; margin: 24px auto; padding: 0 12px; }
            .card { padding: 16px; margin-bottom: 20px; }
            .action-layout { grid-template-columns: 1fr; gap: 0; }
            .ai-grid { grid-template-columns: 1fr; }
        }
    </style>
    <link rel="stylesheet" href="../assets/css/app-theme.css">
</head>
<body>

    <nav class="officer-nav">
        <div class="nav-left">
            <h2>CivicAI Officer</h2>
        </div>
        <div>
            <a href="index.php" class="btn btn-secondary" style="color:#B9C2BC;border-color:#3a4642;">Dashboard</a>
            <a href="../auth/logout.php" class="btn btn-logout">Logout</a>
        </div>
    </nav>

    <div class="container">
        <a href="index.php" class="back-link">← Kembali ke Dashboard Petugas</a>

        <div id="form-alert" hidden></div>

        <?php if (!empty($error_msg)): ?>
            <div class="alert-danger">⚠️ <?= htmlspecialchars($error_msg) ?></div>
        <?php endif; ?>

        <div class="action-layout">
            <!-- Informasi Laporan -->
            <div class="card">
            <h2>Detail Laporan Warga #<?= $report['id'] ?></h2>

            <?php
                $p = strtolower($report['priority'] ?? 'medium');
                $badgeClass = 'badge-medium';
                if ($p === 'critical') $badgeClass = 'badge-critical';
                elseif ($p === 'high') $badgeClass = 'badge-high';
                elseif ($p === 'low') $badgeClass = 'badge-low';
            ?>
            <span class="badge <?= $badgeClass ?>">Prioritas: <?= strtoupper($report['priority']) ?></span>
            <span class="badge badge-category"><?= htmlspecialchars($report['category']) ?></span>
            <span class="badge badge-success" id="status-badge-live">Status: <?= htmlspecialchars($currentStatusLabel) ?></span>

            <div class="report-title"><?= htmlspecialchars($report['title']) ?></div>
            <div class="report-desc"><?= htmlspecialchars($report['description']) ?></div>

            <?php if (!empty($report['ai_priority_score']) || !empty($report['ai_problem'])): ?>
                <div class="ai-box">
                    <strong>Analisis AI yang dapat dijelaskan</strong>
                    <div class="ai-grid">
                        <span><b>Skor prioritas:</b> <?= intval($report['ai_priority_score'] ?? 0) ?>/100</span>
                        <span><b>Masalah:</b> <?= htmlspecialchars($report['ai_problem'] ?? '-') ?></span>
                        <span><b>Durasi:</b> <?= htmlspecialchars($report['ai_duration'] ?? '-') ?></span>
                        <span><b>Risiko:</b> <?= htmlspecialchars($report['ai_risk'] ?? '-') ?></span>
                        <span><b>Konteks lokasi:</b> <?= htmlspecialchars($report['ai_location_context'] ?? '-') ?></span>
                    </div>
                    <p><b>Alasan skor:</b> <?= htmlspecialchars($report['ai_priority_reasoning'] ?? '-') ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($report['photo']) && file_exists(__DIR__ . '/../uploads/' . $report['photo'])): ?>
                <img src="../uploads/<?= htmlspecialchars($report['photo']) ?>" alt="Bukti Laporan" class="img-preview" onload="this.classList.toggle('landscape', this.naturalWidth > this.naturalHeight)">
            <?php endif; ?>

            <p style="font-size: 13px; color: var(--ink-soft);">📍 <strong>Lokasi:</strong> <?= htmlspecialchars($report['location']) ?></p>
            <p style="font-size: 13px; color: var(--ink-soft); margin-top: 4px;">🕒 <strong>Waktu Lapor:</strong> <?= htmlspecialchars($report['created_at']) ?></p>

            <div class="preview-panel" style="margin-top: 18px;">
                <div class="preview-label mono">Pratinjau tampilan untuk warga</div>
                <p style="font-size: 13px; color: var(--ink-soft); margin-bottom: 10px;">Begini kira-kira yang akan dilihat warga di feed laporan setelah Anda menyimpan:</p>
                <div class="preview-officer-box" id="notes-preview-live">
                    <?= !empty($report['officer_notes']) ? '<strong>Respon Petugas:</strong> ' . htmlspecialchars($report['officer_notes']) : '(belum ada catatan petugas)' ?>
                </div>
            </div>
            </div>

            <!-- Form Tindak Lanjut Petugas -->
            <div class="card">
            <h2>Tindak Lanjut & Respon Petugas</h2>
            <form action="" method="POST" id="update-form">
                <div class="form-group">
                    <label for="status">Status Laporan</label>
                    <select name="status" id="status" required>
                        <option value="pending" <?= ($report['status'] === 'pending') ? 'selected' : '' ?>>Pending (Menunggu)</option>
                        <option value="proses" <?= ($report['status'] === 'proses' || $report['status'] === 'in_progress') ? 'selected' : '' ?>>Diproses</option>
                        <option value="selesai" <?= ($report['status'] === 'selesai' || $report['status'] === 'resolved') ? 'selected' : '' ?>>Selesai</option>
                        <option value="ditolak" <?= ($report['status'] === 'ditolak' || $report['status'] === 'rejected') ? 'selected' : '' ?>>Ditolak</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="officer_notes">Catatan / Respon Petugas</label>
                    <textarea name="officer_notes" id="officer_notes" placeholder="Tuliskan respon resmi atau tindakan yang telah diambil..." required><?= htmlspecialchars($report['officer_notes'] ?? '') ?></textarea>
                </div>

                <button type="submit" name="update_action" class="btn btn-primary" id="save-btn">Simpan Pembaruan</button>
            </form>
            </div>
        </div>
    </div>

    <script>
        var form = document.getElementById('update-form');
        var saveBtn = document.getElementById('save-btn');
        var alertBox = document.getElementById('form-alert');
        var statusBadge = document.getElementById('status-badge-live');
        var notesPreview = document.getElementById('notes-preview-live');

        var statusLabels = { pending: 'Pending', proses: 'Diproses', selesai: 'Selesai', ditolak: 'Ditolak' };

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            saveBtn.disabled = true;
            var originalLabel = saveBtn.textContent;
            saveBtn.textContent = 'Menyimpan...';

            try {
                var formData = new FormData(form);
                var res = await fetch(window.location.pathname + window.location.search, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                var data = await res.json();

                if (data.success) {
                    alertBox.className = 'alert-success';
                    alertBox.textContent = '✅ ' + data.message;
                    alertBox.hidden = false;

                    statusBadge.textContent = 'Status: ' + (statusLabels[data.status] || data.status);
                    notesPreview.innerHTML = data.officer_notes
                        ? '<strong>Respon Petugas:</strong> ' + data.officer_notes.replace(/</g, '&lt;')
                        : '(belum ada catatan petugas)';
                } else {
                    alertBox.className = 'alert-danger';
                    alertBox.textContent = '⚠️ ' + data.message;
                    alertBox.hidden = false;
                }
            } catch (err) {
                alertBox.className = 'alert-danger';
                alertBox.textContent = '⚠️ Gagal terhubung ke server. Periksa koneksi Anda dan coba lagi.';
                alertBox.hidden = false;
            } finally {
                saveBtn.disabled = false;
                saveBtn.textContent = originalLabel;
                alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        });
    </script>

</body>
</html>
