<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../DB/db.php';

session_start();

$message = '';
$resultUsername = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '유효한 이메일을 입력해주세요.';
    } else {

        $stmt = $conn->prepare("SELECT username FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        if (!DEV_MODE) {
            $message = '입력하신 이메일로 안내를 발송했습니다.';
        } else {
            if ($row) {
                $resultUsername = $row['username'];
            } else {
                $message = '해당 이메일로 가입된 계정을 찾을 수 없습니다.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>아이디 찾기</title>
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
  .row{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
  .input{flex:1;min-width:220px;padding:12px 12px;border:1px solid var(--line);border-radius:12px;background:#fff;outline:none}
  .btn{padding:12px 14px;border-radius:12px;border:1px solid var(--line);background:#fff;cursor:pointer}
  .btn.primary{background:#111;color:#fff;border-color:#111}
  .btn:hover{border-color:#cfd3e6}
  .msg{padding:10px 12px;border-radius:12px;border:1px solid var(--line);margin:12px 0}
  .msg.info{border-color:#cfe0ff;background:#f3f7ff;color:#163b8c}
  .msg.err{border-color:#ffd6d6;background:#fff4f4;color:#b30000}
  .muted{color:var(--muted)}
</style>
</head>
<body>

<div class="top">
  <div class="container top-inner">
    <div class="brand">electro<span>Shop</span></div>
    <div class="nav">
      <a href="<?= url('/index.php') ?>">메인</a>
      <a href="<?= url('/login.php') ?>">로그인</a>
    </div>
  </div>
</div>

<div class="page">
  <div class="container">
    <div class="card">
      <h1>아이디 찾기</h1>
      <p class="sub">가입한 이메일을 입력하면 아이디를 확인할 수 있습니다.</p>

      <form method="post" class="row" style="margin:0;">
        <input class="input" type="email" name="email" required placeholder="가입 이메일 입력" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        <button class="btn primary" type="submit">찾기</button>
      </form>

      <!-- ✅ 요청한 안내 문구: 입력칸과 로그인으로 사이 -->
      <div class="msg info">
        가입 이메일 분실시
        <a href="<?= url('/qna/guest_inquiry.php') ?>" style="text-decoration:underline;">Q&A 페이지</a>에
        <b>비회원 댓글</b>로 관리자에게 문의하세요.
      </div>

      <?php if ($message): ?>
        <div class="msg <?= (strpos($message,'유효')!==false || strpos($message,'찾을 수')!==false) ? 'err' : 'info' ?>">
          <?= htmlspecialchars($message) ?>
        </div>
      <?php endif; ?>

      <?php if (DEV_MODE && $resultUsername): ?>
        <div class="msg info">
          <b>보유 아이디 :</b> <?= htmlspecialchars($resultUsername) ?>
        </div>
      <?php endif; ?>

      <div class="row" style="margin-top:12px;">
        <a class="btn" href="<?= url('/login.php') ?>">로그인으로</a>
        <a class="btn" href="<?= url('/auth/forgot_password.php') ?>">비밀번호 재설정</a>
      </div>
    </div>
  </div>
</div>

</body>
</html>