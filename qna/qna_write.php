<?php
session_start();
require_once __DIR__ . "/../DB/db.php";

if (!isset($_SESSION['user_id'])) {
  header("Location: /electronics_shop/login.php");
  exit;
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$error = '';
$title = '';
$content = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim((string)($_POST['title'] ?? ''));
  $content = trim((string)($_POST['content'] ?? ''));

  if ($title === '' || $content === '') {
    $error = "제목과 내용을 입력해주세요.";
  } else {
    $user_id = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("INSERT INTO qna (user_id, title, content, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $user_id, $title, $content);
    $stmt->execute();
    $newId = $stmt->insert_id;
    $stmt->close();

    header("Location: /electronics_shop/qna/qna_view.php?id=" . (int)$newId);
    exit;
  }
}
?>
<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <title>Q&A 글쓰기</title>
  <link rel="stylesheet" href="/electronics_shop/css/style.css">  
  <style>
    body{background:#f6f7fb;}
    .wrap{max-width:820px; margin:24px auto; background:#fff; border:1px solid #eee; border-radius:16px; padding:18px;}
    input,textarea{width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:10px; font-size:14px;}
    textarea{min-height:200px; resize:vertical;}
    .row{margin:10px 0;}
    .btn{display:inline-block; padding:9px 12px; border-radius:10px; border:1px solid #ddd; text-decoration:none; color:#111; background:#fff;}
    .btn.primary{background:#111; color:#fff; border-color:#111; cursor:pointer;}
    .err{background:#fff3f3; border:1px solid #ffd3d3; padding:10px 12px; border-radius:10px; color:#a40000;}
  </style>
</head>
<body>
  <div class="wrap">
    <h2 style="margin-top:0;">Q&A 글쓰기</h2>

    <?php if ($error): ?>
      <div class="err"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="row">
        <input type="text" name="title" placeholder="제목" value="<?= h($title) ?>" required>
      </div>
      <div class="row">
        <textarea name="content" placeholder="내용" required><?= h($content) ?></textarea>
      </div>

      <div style="display:flex; gap:8px; justify-content:flex-end; margin-top:12px;">
        <a class="btn" href="/electronics_shop/qna/qna_list.php">목록</a>
        <button class="btn primary" type="submit">등록</button>
      </div>
    </form>
  </div>
</body>
</html>