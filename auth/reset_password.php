<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../DB/db.php';

session_start();

$token = $_GET['token'] ?? '';
$message = '';
$valid = false;
$userId = null;
$resetId = null;

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

if ($token) {
  $now = date('Y-m-d H:i:s');

  $stmt = $conn->prepare("SELECT id, user_id, token_hash FROM password_resets WHERE used_at IS NULL AND expires_at >= ?");
  $stmt->bind_param("s", $now);
  $stmt->execute();
  $res = $stmt->get_result();

  while ($row = $res->fetch_assoc()) {
    if (password_verify($token, $row['token_hash'])) {
      $valid = true;
      $userId = (int)$row['user_id'];
      $resetId = (int)$row['id'];
      break;
    }
  }
  $stmt->close();
}

if ($valid && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $newPw = $_POST['new_password'] ?? '';
  $newPw2 = $_POST['new_password2'] ?? '';

  if ($newPw === '' || $newPw2 === '') {
    $message = '새 비밀번호를 입력해주세요.';
  } elseif ($newPw !== $newPw2) {
    $message = '비밀번호가 일치하지 않습니다.';
  } elseif (mb_strlen($newPw) < 8) {
    $message = '비밀번호는 8자 이상으로 설정해주세요.';
  } else {
    $hash = password_hash($newPw, PASSWORD_DEFAULT);

    $conn->begin_transaction();
    try {
      $up = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
      $up->bind_param("si", $hash, $userId);
      $up->execute();
      $up->close();

      $usedAt = date('Y-m-d H:i:s');
      $u2 = $conn->prepare("UPDATE password_resets SET used_at = ? WHERE id = ?");
      $u2->bind_param("si", $usedAt, $resetId);
      $u2->execute();
      $u2->close();

      $conn->commit();
      header("Location: " . url('/login.php') . "?reset=success");
      exit;
    } catch (Throwable $e) {
      $conn->rollback();
      $message = '비밀번호 변경 실패(DB 오류).';
      if (defined('DEV_MODE') && DEV_MODE) $message .= ' ' . $e->getMessage();
    }
  }
}
?>
<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>비밀번호 재설정</title>
<style>
  :root{
    --bg:#f6f7fb; --card:#fff; --line:#e7e8ee; --text:#111; --muted:#666;
    --brand:#0b57d0; --shadow:0 10px 24px rgba(0,0,0,.06); --radius:16px;
  }
  *{box-sizing:border-box}
  body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:var(--bg);color:var(--text)}
  a{color:inherit;text-decoration:none}
  .container{max-width:760px;margin:0 auto;padding:0 16px}
  .page{padding:26px 0}
  .card{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow);padding:16px}
  h1{margin:0 0 10px;font-size:22px}
  .input{width:100%;padding:12px;border:1px solid var(--line);border-radius:12px;outline:none;background:#fff}
  .row{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
  .btn{padding:12px 14px;border-radius:12px;border:1px solid var(--line);background:#fff;cursor:pointer}
  .btn.primary{background:#111;color:#fff;border-color:#111}
  .msg{padding:10px 12px;border-radius:12px;border:1px solid var(--line);margin:12px 0;background:#fafbff}
  .muted{color:var(--muted);font-size:13px}
</style>
</head>
<body>
<div class="page">
  <div class="container">
    <div class="card">
      <h1>비밀번호 재설정</h1>

      <?php if ($message): ?>
        <div class="msg"><?= h($message) ?></div>
      <?php endif; ?>

      <?php if ($valid): ?>
        <form method="post" class="row" style="margin:0;">
          <input class="input" style="max-width:300px;" type="password" name="new_password" required placeholder="새 비밀번호(8자+)">
          <input class="input" style="max-width:300px;" type="password" name="new_password2" required placeholder="비밀번호 확인">
          <button class="btn primary" type="submit">변경</button>
        </form>
        <p class="muted" style="margin:10px 0 0;">재설정 완료 후 로그인 페이지로 이동합니다.</p>
      <?php else: ?>
        <div class="msg">유효하지 않거나 만료된 토큰입니다.</div>
      <?php endif; ?>

      <div class="row" style="margin-top:12px;">
        <a class="btn" href="<?= url('/login.php') ?>">로그인으로</a>
        <a class="btn" href="<?= url('/auth/forgot_password.php') ?>">재설정 다시</a>
      </div>
    </div>
  </div>
</div>
</body>
</html>