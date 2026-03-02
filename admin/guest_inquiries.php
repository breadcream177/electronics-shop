<?php
session_start();
require_once __DIR__ . "/../DB/db.php";

$BASE = "/electronics_shop";

// ✅ 로그인 + 관리자만
if (!isset($_SESSION['user_id'])) {
  header("Location: {$BASE}/login.php");
  exit;
}
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
  die("관리자만 접근 가능");
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$res = $conn->query("SELECT id, guest_email, content, ip, created_at FROM qna_guest_inquiries ORDER BY id DESC");
?>
<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>관리자 - 비회원 문의</title>
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:#f6f7fb;margin:0}
    .wrap{max-width:1100px;margin:20px auto;background:#fff;border:1px solid #e7e8ee;border-radius:16px;padding:16px}
    a{text-decoration:none;color:#111}
    table{width:100%;border-collapse:collapse}
    th,td{border-bottom:1px solid #eee;padding:10px;text-align:left;vertical-align:top}
    th{background:#fafbff}
    .muted{color:#666;font-size:12px}
    .content{white-space:pre-wrap;line-height:1.5}
    .top{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:10px}
    .btn{display:inline-block;padding:8px 10px;border:1px solid #ddd;border-radius:10px;background:#fff}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="top">
      <h2 style="margin:0;">비회원 문의함</h2>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a class="btn" href="<?= $BASE ?>/admin/admin.php">주문 목록</a>
        <a class="btn" href="<?= $BASE ?>/index.php">메인</a>
      </div>
    </div>

    <p class="muted">※ 이 페이지는 관리자만 접근 가능합니다.</p>

    <table>
      <tr>
        <th style="width:70px;">ID</th>
        <th style="width:220px;">이메일</th>
        <th>내용</th>
        <th style="width:170px;">작성일</th>
      </tr>

      <?php while($r = $res->fetch_assoc()): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td>
            <?= h($r['guest_email']) ?><br>
            <span class="muted">IP: <?= h($r['ip'] ?? '-') ?></span>
          </td>
          <td class="content"><?= h($r['content']) ?></td>
          <td><?= h($r['created_at']) ?></td>
        </tr>
      <?php endwhile; ?>
    </table>
  </div>
</body>
</html>