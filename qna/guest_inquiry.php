<?php
session_start();
require_once __DIR__ . "/../DB/db.php";

$BASE = "/electronics_shop";

$ok = "";
$error = "";

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $content = trim($_POST['content'] ?? '');
  $honeypot = trim($_POST['company'] ?? ''); // 스팸봇 방지용(숨김필드)

  if ($honeypot !== '') {
    $error = "잘못된 요청입니다.";
  } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "유효한 이메일을 입력해주세요.";
  } elseif ($content === '' || mb_strlen($content) < 5) {
    $error = "문의 내용을 5자 이상 입력해주세요.";
  } elseif (mb_strlen($content) > 2000) {
    $error = "문의 내용은 2000자 이하로 입력해주세요.";
  } else {
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;

    $stmt = $conn->prepare("INSERT INTO qna_guest_inquiries (guest_email, content, ip) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $content, $ip);
    if ($stmt->execute()) {
      $ok = "문의가 접수되었습니다. 관리자 확인 후 필요한 경우 이메일로 안내드립니다.";
      $_POST = []; // 폼 초기화
    } else {
      $error = "저장 실패(DB 오류).";
    }
    $stmt->close();
  }
}
?>
<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>비회원 문의</title>
<style>
  :root{
    --bg:#f6f7fb; --card:#fff; --line:#e7e8ee; --text:#111; --muted:#666;
    --brand:#0b57d0; --shadow:0 10px 24px rgba(0,0,0,.06); --radius:16px;
  }
  *{box-sizing:border-box}
  body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:var(--bg);color:var(--text)}
  a{color:inherit;text-decoration:none}
  .container{max-width:720px;margin:0 auto;padding:0 16px}
  .top{background:#fff;border-bottom:1px solid var(--line)}
  .top-inner{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:14px 0;flex-wrap:wrap}
  .brand{font-weight:900;font-size:18px}
  .brand span{color:var(--brand)}
  .nav{display:flex;gap:8px;flex-wrap:wrap}
  .nav a{padding:8px 10px;border:1px solid var(--line);border-radius:12px;background:#fff}
  .nav a:hover{border-color:#cfd3e6}

  .page{padding:18px 0 28px}
  .card{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow);padding:16px}
  h1{margin:0 0 8px;font-size:22px;letter-spacing:-.3px}
  .sub{margin:0 0 14px;color:var(--muted);line-height:1.5}
  .input, .ta{width:100%;padding:12px;border:1px solid var(--line);border-radius:12px;outline:none;background:#fff}
  .ta{min-height:160px;resize:vertical}
  .row{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
  .btn{padding:12px 14px;border-radius:12px;border:1px solid var(--line);background:#fff;cursor:pointer}
  .btn.primary{background:#111;color:#fff;border-color:#111}
  .btn:hover{border-color:#cfd3e6}
  .msg{padding:10px 12px;border-radius:12px;border:1px solid var(--line);margin:12px 0}
  .msg.ok{border-color:#ccebd6;background:#f3fbf6;color:#0b6b2e}
  .msg.err{border-color:#ffd6d6;background:#fff4f4;color:#b30000}
  .hint{font-size:12px;color:var(--muted);margin-top:8px}
  .hidden{display:none}
</style>
</head>
<body>

<div class="top">
  <div class="container top-inner">
    <div class="brand">electro<span>Shop</span></div>
    <div class="nav">
      <a href="<?= $BASE ?>/index.php">메인</a>
      <a href="<?= $BASE ?>/qna/qna_list.php">Q&A</a>
      <a href="<?= $BASE ?>/login.php">로그인</a>
    </div>
  </div>
</div>

<div class="page">
  <div class="container">
    <div class="card">
      <h1>비회원 문의</h1>
      <p class="sub">
        가입 이메일을 분실했거나 로그인 문제 등으로 문의가 필요한 경우 사용하세요.<br>
        ※ 문의 내용은 <b>관리자만</b> 확인할 수 있습니다.
      </p>

      <?php if ($ok): ?><div class="msg ok"><?= h($ok) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="msg err"><?= h($error) ?></div><?php endif; ?>

      <form method="post" style="margin:0;">
        <!-- 스팸봇 방지용 숨김 필드 -->
        <input class="hidden" type="text" name="company" value="">

        <div style="margin-bottom:10px;">
          <label class="hint">연락 받을 이메일</label>
          <input class="input" type="email" name="email" required placeholder="example@email.com"
                 value="<?= h($_POST['email'] ?? '') ?>">
        </div>

        <div style="margin-bottom:10px;">
          <label class="hint">문의 내용</label>
          <textarea class="ta" name="content" required placeholder="문의 내용을 입력하세요."><?= h($_POST['content'] ?? '') ?></textarea>
          <div class="hint">5자 이상 / 2000자 이하</div>
        </div>

        <div class="row" style="justify-content:flex-end;">
          <a class="btn" href="<?= $BASE ?>/qna/qna_list.php">취소</a>
          <button class="btn primary" type="submit">문의 접수</button>
        </div>
      </form>
    </div>
  </div>
</div>

</body>
</html>