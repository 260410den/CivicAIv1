<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
require_once __DIR__ . "/../config/database.php";

$report_id = intval($_GET["id"] ?? 0);
$ip_address = $_SERVER['REMOTE_ADDR'];
$is_logged_in = isset($_SESSION['user_name']) || isset($_SESSION['username']);

// Handle Like / Dislike
if (isset($_GET["action"])) {
    $action = $_GET["action"];
    if (!in_array($action, ['like', 'dislike'], true)) {
        http_response_code(422);
        exit("Aksi dukungan tidak valid.");
    }
    $is_ajax = (!empty($_GET["ajax"]) || strtolower($_SERVER["HTTP_X_REQUESTED_WITH"] ?? "") === "xmlhttprequest");

    if (!$is_logged_in) {
        if ($is_ajax) {
            http_response_code(401);
            echo json_encode(["success" => false, "message" => "Silakan login untuk ikut berinteraksi"]);
        } else {
            header("Location: ../auth/login.php");
        }
        exit;
    }

    $check_vote = $conn->prepare("SELECT id, vote_type FROM report_votes WHERE report_id = ? AND ip_address = ?");
    $check_vote->bind_param("is", $report_id, $ip_address);
    $check_vote->execute();
    $vote_result = $check_vote->get_result();

    if ($vote_result->num_rows > 0) {
        $existing = $vote_result->fetch_assoc();
        if ($existing['vote_type'] !== $action) {
            $voteUpdate = $conn->prepare("UPDATE report_votes SET vote_type = ? WHERE id = ?");
            $voteUpdate->bind_param("si", $action, $existing['id']);
            $voteUpdate->execute();
            $voteUpdate->close();
            if ($action === 'like') {
                $conn->query("UPDATE reports SET likes = likes + 1, dislikes = dislikes - 1 WHERE id = $report_id");
            } else {
                $conn->query("UPDATE reports SET likes = likes - 1, dislikes = dislikes + 1 WHERE id = $report_id");
            }
        }
    } else {
        $insert_vote = $conn->prepare("INSERT INTO report_votes (report_id, ip_address, vote_type) VALUES (?, ?, ?)");
        $insert_vote->bind_param("iss", $report_id, $ip_address, $action);
        $insert_vote->execute();
        $insert_vote->close();

        if ($action === 'like') {
            $conn->query("UPDATE reports SET likes = likes + 1 WHERE id = $report_id");
        } else {
            $conn->query("UPDATE reports SET dislikes = dislikes + 1 WHERE id = $report_id");
        }
    }
    $check_vote->close();

    $report_stmt = $conn->prepare("SELECT likes, dislikes FROM reports WHERE id = ?");
    $report_stmt->bind_param("i", $report_id);
    $report_stmt->execute();
    $report_data = $report_stmt->get_result()->fetch_assoc();
    $report_stmt->close();

    if ($is_ajax) {
        echo json_encode([
            "success" => true,
            "action" => $action,
            "likes" => (int) ($report_data["likes"] ?? 0),
            "dislikes" => (int) ($report_data["dislikes"] ?? 0),
            "user_vote" => $action
        ]);
        exit;
    }

    header("Location: detail.php?id=" . $report_id);
    exit;
}

// Handle Kirim Komentar / Balasan
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit_comment"])) {
    $is_ajax = (!empty($_POST["ajax"]) || strtolower($_SERVER["HTTP_X_REQUESTED_WITH"] ?? "") === "xmlhttprequest");

    if (!$is_logged_in) {
        if ($is_ajax) {
            http_response_code(401);
            echo json_encode(["success" => false, "message" => "Silakan login untuk menulis komentar"]);
        } else {
            header("Location: ../auth/login.php");
        }
        exit;
    }
    $commenter_name = $_SESSION['user_name'] ?? $_SESSION['username'] ?? "Warga";
    $comment = trim($_POST["comment"] ?? "");
    $parent_id = !empty($_POST["parent_id"]) ? intval($_POST["parent_id"]) : NULL;

    if (!empty($comment)) {
        $stmt = $conn->prepare("INSERT INTO report_comments (report_id, commenter_name, comment, parent_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issi", $report_id, $commenter_name, $comment, $parent_id);
        $stmt->execute();
        $inserted_id = $stmt->insert_id;
        $stmt->close();

        $commenter_role = $_SESSION['user_role'] ?? null;
        $is_official = ($commenter_role === 'petugas' || stripos($commenter_name, 'petugas') !== false);

        if ($is_ajax) {
            echo json_encode([
                "success" => true,
                "type" => $parent_id ? "reply" : "comment",
                "comment_id" => $inserted_id,
                "parent_id" => $parent_id,
                "commenter_name" => $commenter_name,
                "comment" => $comment,
                "created_at" => date("Y-m-d H:i:s"),
                "is_official" => $is_official
            ]);
            exit;
        }

        header("Location: detail.php?id=" . $report_id);
        exit;
    }

    if ($is_ajax) {
        echo json_encode(["success" => false, "message" => "Komentar tidak boleh kosong"]);
        exit;
    }
}

// Ambil Data Laporan
$stmt = $conn->prepare("SELECT * FROM reports WHERE id = ?");
$stmt->bind_param("i", $report_id);
$stmt->execute();
$report = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$report) {
    die("<div style='text-align:center; margin-top:50px; font-family:Inter,sans-serif;'><h2>Laporan tidak ditemukan.</h2><a href='index.php'>Kembali ke Feed</a></div>");
}
$can_report_post = $is_logged_in && isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] !== (int) ($report['user_id'] ?? 0);

// Fungsi rekursif untuk merender komentar bertingkat
function renderComments($conn, $report_id, $parent_id = null) {
    $is_logged_in = isset($_SESSION['user_name']) || isset($_SESSION['username']);

    if ($parent_id === null) {
        $sql = "SELECT c.*, u.role as user_role 
                FROM report_comments c 
                LEFT JOIN users u ON c.commenter_name = u.name 
                WHERE c.report_id = $report_id AND (c.parent_id IS NULL OR c.parent_id = 0) 
                ORDER BY c.created_at ASC";
    } else {
        $sql = "SELECT c.*, u.role as user_role 
                FROM report_comments c 
                LEFT JOIN users u ON c.commenter_name = u.name 
                WHERE c.report_id = $report_id AND c.parent_id = $parent_id 
                ORDER BY c.created_at ASC";
    }

    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        echo ($parent_id === null) ? '<div class="comment-list">' : '<div class="reply-list" id="reply-list-' . $parent_id . '">';
        
        while ($comm = $result->fetch_assoc()) {
            $is_official = ($comm['user_role'] === 'petugas' || stripos($comm['commenter_name'], 'petugas') !== false);
            $comment_id = $comm['id'];

            $check_sub = $conn->query("SELECT COUNT(*) as total FROM report_comments WHERE parent_id = $comment_id");
            $sub_count = $check_sub ? $check_sub->fetch_assoc()['total'] : 0;
            ?>
            <div class="comment-item <?= $is_official ? 'is-official' : '' ?>">
                <div class="comment-author">
                    <?= htmlspecialchars($comm['commenter_name']) ?>
                    <?php if ($is_official): ?>
                        <span class="badge badge-official" style="margin-bottom:0; font-size:9.5px; padding:2px 6px;">PETUGAS RESMI</span>
                    <?php endif; ?>
                </div>
                <div class="comment-text"><?= htmlspecialchars($comm['comment']) ?></div>
                <div class="comment-date"><?= $comm['created_at'] ?></div>
                
                <?php if ($is_logged_in): ?>
                    <button type="button" class="btn-reply-toggle" onclick="toggleReplyForm(<?= $comment_id ?>)">Balas</button>

                    <div id="reply-form-<?= $comment_id ?>" class="reply-form">
                        <form action="detail.php?id=<?= $report_id ?>" method="POST" data-report-id="<?= $report_id ?>" onsubmit="return submitCommentAjax(event, this);">
                            <input type="hidden" name="parent_id" value="<?= $comment_id ?>">
                            <input type="hidden" name="submit_comment" value="1">
                            <textarea name="comment" placeholder="Tulis balasan..." required></textarea>
                            <button type="submit" class="btn btn-primary" style="font-size: 11.5px; min-height: 32px; padding: 4px 12px;">Kirim Balasan</button>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if ($sub_count > 0): ?>
                    <button type="button" class="btn-toggle-replies" id="btn-toggle-<?= $comment_id ?>" onclick="toggleReplies(<?= $comment_id ?>)" data-count="<?= $sub_count ?>">
                        ▼ Lihat balasan (<?= $sub_count ?>)
                    </button>
                <?php endif; ?>

                <?php renderComments($conn, $report_id, $comment_id); ?>
            </div>
            <?php
        }
        echo '</div>';
    }
}

$my_vote_query = $conn->prepare("SELECT vote_type FROM report_votes WHERE report_id = ? AND ip_address = ?");
$my_vote_query->bind_param("is", $report_id, $ip_address);
$my_vote_query->execute();
$my_vote_res = $my_vote_query->get_result()->fetch_assoc();
$user_voted = $my_vote_res['vote_type'] ?? '';
$my_vote_query->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail & Diskusi Laporan - CivicAI</title>
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
        header nav a {
            text-decoration: none;
            color: var(--ink-soft);
            margin-left: 20px;
            font-weight: 600;
            font-size: 13.5px;
            transition: color .15s ease;
        }
        header nav a:hover { color: var(--navy); }

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

        /* ---------- Layout & Cards ---------- */
        .container { max-width: 750px; margin: 36px auto; padding: 0 20px; }
        .back-link {
            display: inline-flex;
            align-items: center;
            margin-bottom: 20px;
            color: var(--navy);
            font-weight: 600;
            font-size: 13.5px;
            gap: 6px;
        }
        .back-link:hover { text-decoration: underline; }

        .card {
            background: var(--white);
            border: 1.5px solid var(--ink);
            border-radius: var(--radius-lg);
            padding: 28px;
            margin-bottom: 28px;
            box-shadow: 6px 6px 0 rgba(28, 35, 33, 0.06);
        }

        /* ---------- Badges ---------- */
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
        .badge-official { background: #E2ECF5; color: var(--navy); font-size: 10px; padding: 2px 6px; border-radius: var(--radius); font-weight: 700; }

        /* ---------- Content Typography ---------- */
        .title {
            font-family: 'Big Shoulders Display', sans-serif;
            font-weight: 900;
            text-transform: uppercase;
            font-size: 26px;
            letter-spacing: -0.3px;
            color: var(--ink);
            margin-bottom: 10px;
        }
        .desc { font-size: 14.5px; color: var(--ink-soft); margin-bottom: 18px; line-height: 1.6; }
        
        .img-preview { display: block; width: auto; max-width: 100%; max-height: 350px; object-fit: contain; border-radius: var(--radius); border: 1.5px solid var(--line); margin: 0 auto 16px; }
        .img-preview.landscape { width: 100%; max-width: 100%; max-height: none; margin-left: 0; margin-right: 0; }
        .video-preview { display: block; width: 100%; max-height: 350px; object-fit: contain; border-radius: var(--radius); margin: 0 auto 16px; background: var(--ink); }

        .officer-box {
            background: #EDF5EE;
            border-left: 4px solid var(--sage);
            padding: 12px 16px;
            border-radius: var(--radius);
            margin-bottom: 16px;
            font-size: 13.5px;
            color: #274D2C;
        }
        .meta-info {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 12px;
            color: var(--ink-soft);
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1.5px solid var(--line);
        }

        /* ---------- Vote Buttons ---------- */
        .vote-section { display: flex; gap: 10px; align-items: center; }
        .btn-vote {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: var(--radius);
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            border: 1.5px solid var(--line);
            background: var(--white);
            color: var(--ink);
            transition: .15s ease;
            cursor: pointer;
            font-family: inherit;
        }
        .btn-vote:hover { border-color: var(--navy); background: var(--paper); }
        .btn-vote.active-like { background: #EDF5EE; border-color: var(--sage); color: #274D2C; font-weight: 700; }
        .btn-vote.active-dislike { background: #FBEAE8; border-color: var(--danger); color: var(--danger); font-weight: 700; }

        /* ---------- Comments & Form Section ---------- */
        h3 {
            font-family: 'Big Shoulders Display', sans-serif;
            font-weight: 900;
            text-transform: uppercase;
            font-size: 20px;
            letter-spacing: -0.2px;
            color: var(--ink);
            margin-bottom: 16px;
        }
        .comment-form textarea {
            width: 100%;
            padding: 12px;
            border: 1.5px solid var(--line);
            border-radius: var(--radius);
            font-size: 13.5px;
            margin-bottom: 10px;
            height: 90px;
            resize: vertical;
            outline: none;
            font-family: inherit;
            background: var(--white);
            color: var(--ink);
        }
        .comment-form textarea:focus { border-color: var(--navy); box-shadow: 0 0 0 2px rgba(27,58,92,0.1); }
        .user-tag {
            background: var(--paper);
            padding: 10px 14px;
            border-radius: var(--radius);
            font-size: 13px;
            font-weight: 600;
            color: var(--ink-soft);
            margin-bottom: 12px;
            display: block;
            border: 1.5px solid var(--line);
        }
        .guest-action { display: flex; flex-direction: column; align-items: center; gap: 12px; margin-top: 14px; text-align: center; }
        .login-prompt { width: 100%; margin: 0; padding: 12px 14px; border: 1.5px solid #A2C2E8; border-radius: var(--radius); color: var(--navy); background: #E2ECF5; font-size: 13px; font-weight: 500; }
        
        .report-post-box { margin-top: 16px; padding-top: 16px; border-top: 1.5px solid var(--line); }
        .report-post-form { display: grid; grid-template-columns: 1fr 1fr auto; gap: 8px; align-items: center; }
        .report-post-form select, .report-post-form input { min-width: 0; padding: 9px 10px; border: 1.5px solid var(--line); border-radius: var(--radius); font-size: 12.5px; font-family: inherit; outline: none; }
        .report-post-form select:focus, .report-post-form input:focus { border-color: var(--navy); }
        
        .btn-report {
            border: 1.5px solid var(--danger);
            border-radius: var(--radius);
            padding: 9px 14px;
            color: var(--danger);
            background: #FBEAE8;
            cursor: pointer;
            font-size: 12.5px;
            font-weight: 700;
            transition: .15s ease;
        }
        .btn-report:hover { background: var(--danger); color: var(--white); }

        .comment-list { margin-top: 20px; display: flex; flex-direction: column; gap: 12px; }
        .comment-item { background: var(--white); border: 1.5px solid var(--line); padding: 16px; border-radius: var(--radius-lg); }
        .comment-item.is-official { background: #F8F7F4; border-left: 4px solid var(--navy); }
        .comment-author { font-weight: 700; font-size: 13.5px; color: var(--ink); margin-bottom: 6px; display: flex; align-items: center; flex-wrap: wrap; gap: 6px; }
        .comment-text { font-size: 14px; line-height: 1.5; color: var(--ink-soft); margin-bottom: 8px; white-space: pre-wrap; overflow-wrap: anywhere; }
        .comment-date { font-family: 'IBM Plex Mono', monospace; font-size: 10.5px; color: var(--ink-soft); margin-top: 4px; }
        
        .btn-reply-toggle { background: var(--paper); border: 1.5px solid var(--line); padding: 4px 10px; border-radius: 20px; color: var(--ink-soft); font-size: 11.5px; font-weight: 600; cursor: pointer; transition: .15s ease; display: inline-block; margin-top: 6px; }
        .btn-reply-toggle:hover { background: var(--line); color: var(--ink); }
        
        .reply-list { display: none; margin-top: 12px; padding: 4px 0 0 16px; border-left: 2px solid var(--navy); }
        .reply-list .comment-item { background: var(--paper); padding: 12px; border-color: var(--line); border-radius: var(--radius); }
        
        .btn-toggle-replies { background: none; border: none; color: var(--navy); font-size: 12px; font-weight: 700; cursor: pointer; margin-top: 8px; display: block; padding: 0; font-family: inherit; }
        .btn-toggle-replies:hover { text-decoration: underline; }

        .reply-form { margin-top: 10px; padding: 12px; display: none; background: var(--paper); border: 1.5px solid var(--line); border-radius: var(--radius); }
        .reply-form textarea { width: 100%; padding: 8px 10px; border: 1.5px solid var(--line); border-radius: var(--radius); font-size: 12.5px; height: 60px; resize: vertical; outline: none; background: var(--white); }

        @media (max-width: 600px) {
            header { padding: 14px 16px; flex-direction: column; align-items: flex-start; gap: 10px; }
            header nav { display: flex; flex-wrap: wrap; gap: 8px 12px; }
            header nav a { margin-left: 0; font-size: 12px; }
            .container { margin: 20px auto; padding: 0 12px; }
            .card { padding: 16px; margin-bottom: 20px; }
            .title { font-size: 21px; }
            .report-post-form { grid-template-columns: 1fr; }
        }
    </style>
    <script>
        function escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function submitVoteAjax(button) {
            var action = button.dataset.action;
            var reportId = button.dataset.reportId;
            var url = 'detail.php?id=' + encodeURIComponent(reportId) + '&action=' + encodeURIComponent(action) + '&ajax=1';

            fetch(url, {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (!data.success) {
                    alert(data.message || 'Gagal memproses vote');
                    return;
                }

                var likeBtn = document.querySelector('.btn-vote[data-action="like"]');
                var dislikeBtn = document.querySelector('.btn-vote[data-action="dislike"]');

                likeBtn.innerHTML = '👍 Setuju / Dukung (' + data.likes + ')';
                dislikeBtn.innerHTML = '👎 Kurang Relevan (' + data.dislikes + ')';

                likeBtn.classList.toggle('active-like', data.user_vote === 'like');
                dislikeBtn.classList.toggle('active-dislike', data.user_vote === 'dislike');
                likeBtn.classList.remove('active-dislike');
                dislikeBtn.classList.remove('active-like');
            })
            .catch(function () {
                window.location.href = 'detail.php?id=' + reportId + '&action=' + action;
            });
        }

        function submitCommentAjax(event, form) {
            event.preventDefault();

            var formData = new FormData(form);
            formData.append('ajax', '1');
            formData.append('submit_comment', '1');

            fetch(form.action || window.location.href, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (!data.success) {
                    alert(data.message || 'Gagal menyimpan komentar');
                    return;
                }

                if (data.type === 'reply') {
                    var list = document.getElementById('reply-list-' + data.parent_id);
                    var targetContainer = list || document.createElement('div');
                    if (!list) {
                        targetContainer.id = 'reply-list-' + data.parent_id;
                        targetContainer.className = 'reply-list';
                        targetContainer.style.display = 'block';
                        var parentCard = document.getElementById('reply-form-' + data.parent_id);
                        if (parentCard && parentCard.closest('.comment-item')) {
                            parentCard.closest('.comment-item').appendChild(targetContainer);
                        }
                    } else {
                        list.style.display = 'block';
                    }

                    targetContainer.appendChild(buildCommentCard({
                        comment_id: data.comment_id,
                        commenter_name: data.commenter_name,
                        comment: data.comment,
                        created_at: data.created_at,
                        is_official: data.is_official,
                        report_id: form.dataset.reportId || new URLSearchParams(window.location.search).get('id')
                    }));

                    var btnToggle = document.getElementById('btn-toggle-' + data.parent_id);
                    if (btnToggle) {
                        btnToggle.innerText = '▲ Sembunyikan balasan';
                    }

                    var formBox = document.getElementById('reply-form-' + data.parent_id);
                    if (formBox) formBox.style.display = 'none';
                    form.reset();
                    return;
                }

                var list = document.querySelector('.comment-list');
                if (!list) {
                    list = document.createElement('div');
                    list.className = 'comment-list';
                    document.getElementById('comments-container').appendChild(list);
                }
                list.appendChild(buildCommentCard({
                    comment_id: data.comment_id,
                    commenter_name: data.commenter_name,
                    comment: data.comment,
                    created_at: data.created_at,
                    is_official: data.is_official,
                    report_id: form.dataset.reportId || new URLSearchParams(window.location.search).get('id')
                }));
                form.reset();
            })
            .catch(function () {
                alert('Komentar gagal dikirim. Periksa koneksi atau coba lagi.');
            });

            return false;
        }

        function reportPostAjax(event, form) {
            event.preventDefault();
            var button = form.querySelector('button[type="submit"]');
            button.disabled = true;
            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                alert(data.message || 'Laporan diproses');
                if (data.success) form.reset();
            })
            .catch(function () { alert('Laporan gagal dikirim. Coba lagi.'); })
            .finally(function () { button.disabled = false; });
            return false;
        }

        function buildCommentCard(data) {
            var wrapper = document.createElement('div');
            wrapper.className = 'comment-item' + (data.is_official ? ' is-official' : '');
            wrapper.innerHTML = [
                '<div class="comment-author">' + escapeHtml(data.commenter_name) + (data.is_official ? ' <span class="badge badge-official" style="margin-bottom:0; font-size:9.5px; padding:2px 6px;">PETUGAS RESMI</span>' : '') + '</div>',
                '<div class="comment-text">' + escapeHtml(data.comment) + '</div>',
                '<div class="comment-date">' + escapeHtml(data.created_at) + '</div>',
                '<button type="button" class="btn-reply-toggle" onclick="toggleReplyForm(' + data.comment_id + ')">Balas</button>',
                '<div id="reply-form-' + data.comment_id + '" class="reply-form">',
                    '<form onsubmit="return submitCommentAjax(event, this);" method="POST" action="detail.php?id=' + encodeURIComponent(data.report_id) + '">',
                        '<input type="hidden" name="parent_id" value="' + data.comment_id + '">',
                        '<input type="hidden" name="submit_comment" value="1">',
                        '<textarea name="comment" placeholder="Tulis balasan..." required></textarea>',
                        '<button type="submit" class="btn btn-primary" style="font-size: 11.5px; min-height: 32px; padding: 4px 12px; margin-top: 6px;">Kirim Balasan</button>',
                    '</form>',
                '</div>'
            ].join('');
            return wrapper;
        }

        function toggleReplyForm(commentId) {
            var form = document.getElementById('reply-form-' + commentId);
            if (!form) return;
            form.style.display = (form.style.display === 'none' || form.style.display === '') ? 'block' : 'none';
        }

        function toggleReplies(commentId) {
            var list = document.getElementById('reply-list-' + commentId);
            var btn = document.getElementById('btn-toggle-' + commentId);
            if (!list || !btn) return;
            var count = btn.getAttribute('data-count');
            if (list.style.display === 'none' || list.style.display === '') {
                list.style.display = 'block';
                btn.innerText = '▲ Sembunyikan balasan';
            } else {
                list.style.display = 'none';
                btn.innerText = '▼ Lihat balasan (' + count + ')';
            }
        }
    </script>
    <link rel="stylesheet" href="../assets/css/app-theme.css">
</head>
<body>

    <header>
        <div class="logo">Civic<span>AI</span></div>
        <nav>
            <a href="index.php">Daftar Laporan</a>
            <?php if ($is_logged_in): ?>
                <a href="../auth/logout.php" style="color: var(--danger);">Logout</a>
            <?php endif; ?>
        </nav>
    </header>

    <div class="container">
        <a href="index.php" class="back-link">← Kembali ke Feed Laporan</a>

        <div class="card">
            <?php 
                $p = strtolower($report['priority'] ?? 'medium');
                $badgeClass = 'badge-medium';
                if ($p === 'critical') $badgeClass = 'badge-critical';
                elseif ($p === 'high') $badgeClass = 'badge-high';
                elseif ($p === 'low') $badgeClass = 'badge-low';
            ?>
            <div>
                <span class="badge <?= $badgeClass ?>">Prioritas: <?= strtoupper($report['priority']) ?></span>
                <span class="badge badge-category"><?= htmlspecialchars($report['category']) ?></span>
            </div>

            <div class="title"><?= htmlspecialchars($report['title']) ?></div>
            <div class="desc"><?= htmlspecialchars($report['description']) ?></div>

            <?php if (!empty($report['photo']) && file_exists(__DIR__ . '/../uploads/' . $report['photo'])): ?>
                <img src="../uploads/<?= htmlspecialchars($report['photo']) ?>" alt="Bukti" class="img-preview" onload="this.classList.toggle('landscape', this.naturalWidth > this.naturalHeight)">
            <?php endif; ?>

            <?php if (!empty($report['video']) && file_exists(__DIR__ . '/../uploads/' . $report['video'])): ?>
                <video class="video-preview" controls preload="metadata">
                    <source src="../uploads/<?= htmlspecialchars($report['video']) ?>">
                    Browser Anda tidak mendukung pemutar video.
                </video>
            <?php endif; ?>

            <?php if (!empty($report['officer_notes'])): ?>
                <div class="officer-box">
                    <strong>Respon Petugas:</strong> <?= htmlspecialchars($report['officer_notes']) ?>
                </div>
            <?php endif; ?>

            <div class="meta-info">
                <span>📍 <?= htmlspecialchars($report['location']) ?></span>
                <span>🕒 <?= htmlspecialchars($report['created_at']) ?></span>
                <span>Status: <strong style="text-transform:uppercase; color:var(--navy);"><?= htmlspecialchars($report['status']) ?></strong></span>
            </div>

            <?php if ($is_logged_in): ?>
                <div class="vote-section">
                    <button type="button" data-action="like" data-report-id="<?= $report['id'] ?>" class="btn-vote <?= ($user_voted === 'like') ? 'active-like' : '' ?>" onclick="submitVoteAjax(this); return false;">
                        👍 Setuju / Dukung (<?= $report['likes'] ?? 0 ?>)
                    </button>
                    <button type="button" data-action="dislike" data-report-id="<?= $report['id'] ?>" class="btn-vote <?= ($user_voted === 'dislike') ? 'active-dislike' : '' ?>" onclick="submitVoteAjax(this); return false;">
                        👎 Kurang Relevan (<?= $report['dislikes'] ?? 0 ?>)
                    </button>
                </div>
            <?php else: ?>
                <div class="guest-action">
                    <a class="btn btn-primary" href="../auth/login.php" style="width: 100%;">Login untuk ikut berinteraksi</a>
                </div>
            <?php endif; ?>

            <?php if ($can_report_post): ?>
                <div class="report-post-box">
                    <form class="report-post-form" action="../api/report_post.php" method="POST" onsubmit="return reportPostAjax(event, this);">
                        <input type="hidden" name="report_id" value="<?= $report_id ?>">
                        <select name="reason" required>
                            <option value="">Laporkan postingan</option>
                            <option value="Hoaks atau informasi palsu">Hoaks atau informasi palsu</option>
                            <option value="Konten tidak pantas">Konten tidak pantas</option>
                            <option value="Spam atau iklan">Spam atau iklan</option>
                            <option value="Pelanggaran lainnya">Pelanggaran lainnya</option>
                        </select>
                        <input type="text" name="details" placeholder="Keterangan tambahan (opsional)">
                        <button type="submit" class="btn-report">Kirim report</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>Diskusi & Tanggapan Publik</h3>
            
            <?php if ($is_logged_in): ?>
                <form action="detail.php?id=<?= $report_id ?>" method="POST" class="comment-form" data-report-id="<?= $report_id ?>" onsubmit="return submitCommentAjax(event, this);">
                    <?php $logged_name = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Warga'; ?>
                    <span class="user-tag">💬 Berkomentar sebagai: <strong><?= htmlspecialchars($logged_name) ?></strong>
                    <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'petugas'): ?>
                        <span class="badge badge-official" style="margin-bottom:0; font-size:9.5px; padding:2px 6px;">PETUGAS RESMI</span>
                    <?php endif; ?></span>

                    <textarea name="comment" placeholder="Tulis tanggapan atau informasi tambahan..." required></textarea>
                    <input type="hidden" name="submit_comment" value="1">
                    <button type="submit" class="btn btn-primary" style="font-size: 12.5px;">Kirim Komentar</button>
                </form>
            <?php else: ?>
                <div class="guest-action" style="margin-top:0;">
                    <p class="login-prompt">Login terlebih dahulu untuk menulis komentar atau membalas komentar.</p>
                </div>
            <?php endif; ?>

            <div id="comments-container">
                <?php renderComments($conn, $report_id); ?>
            </div>
        </div>
    </div>

</body>
</html>
