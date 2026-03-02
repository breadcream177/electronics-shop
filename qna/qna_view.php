<?php
session_start();
require_once __DIR__ . "/../DB/db.php";

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die("잘못된 접근");

$stmt = $conn->prepare("
  SELECT q.*, u.username
  FROM qna q
  LEFT JOIN users u ON u.id = q.user_id
  WHERE q.id = ?
  LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$post) die("게시글이 없습니다.");

$loginUserId = (int)($_SESSION['user_id'] ?? 0);
$isOwner = ($loginUserId > 0 && $loginUserId === (int)$post['user_id']);

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <title>Q&A 상세</title>
  <link rel="stylesheet" href="/electronics_shop/css/style.css">
  <style>
    body{background:#f6f7fb;}
    .wrap{max-width:980px; margin:24px auto; background:#fff; border:1px solid #eee; border-radius:16px; padding:18px;}
    .meta{display:flex; gap:10px; flex-wrap:wrap; color:#666; font-size:13px; margin:8px 0 14px;}
    .content{white-space:pre-wrap; line-height:1.6; border-top:1px solid #eee; padding-top:14px;}
    .btn{display:inline-block; padding:9px 12px; border-radius:10px; border:1px solid #ddd; text-decoration:none; color:#111; background:#fff;}
    .btn.danger{border-color:#ffbcbc; color:#b00000; background:#fff5f5; cursor:pointer;}
  </style>
</head>
<body>
  <div class="wrap">
    <h2 style="margin:0;"><?= h($post['title']) ?></h2>
    <div class="meta">
      <div>작성자: <?= h($post['username'] ?? ('유저#'.(int)$post['user_id'])) ?></div>
      <div>작성일: <?= h($post['created_at']) ?></div>
    </div>

    <div class="content"><?= h($post['content']) ?></div>

    <div style="display:flex; gap:8px; justify-content:flex-end; margin-top:14px; flex-wrap:wrap;">
      <a class="btn" href="/electronics_shop/qna/qna_list.php">목록</a>

      <?php if ($isOwner): ?>
        <form method="post" action="/electronics_shop/qna/qna_delete.php" onsubmit="return confirm('삭제하시겠습니까?');" style="display:inline;">
          <input type="hidden" name="id" value="<?= (int)$post['id'] ?>">
          <button class="btn danger" type="submit">삭제</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>