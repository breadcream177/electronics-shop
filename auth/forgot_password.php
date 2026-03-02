<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../DB/db.php';

session_start();

$message = '';
$devToken = null;      // DEV_MODE에서만 사용
$devResetLink = null;  // 기존 링크도 유지

function base64url_encode($data) {
  return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function find_reset_row_by_token(mysqli $conn, string $token): ?array {
  $now = date('Y-m-d H:i:s');
  $stmt = $conn->prepare("SELECT id, user_id, token_hash FROM password_resets WHERE used_at IS NULL AND expires_at >= ?");
  $stmt->bind_param("s", $now);
  $stmt->execute();
  $res = $stmt->get_result();

  $found = null;
  while ($row = $res->fetch_assoc()) {
    if (password_verify($token, $row['token_hash'])) {
      $found = $row;
      break;
    }
  }
  $stmt->close();
  return $found;
}

// ---------------------------
// 1) 비번 변경 단계 (같은 페이지에서 처리)
// ---------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'do_reset') {

  $token = trim($_POST['token'] ?? '');
  $newPw  = (string)($_POST['new_password'] ?? '');
  $newPw2 = (string)($_POST['new_password2'] ?? '');

  if ($token === '') {
    $message = '유효하지 않은 요청입니다.(token 없음)';
  } elseif ($newPw === '' || $newPw2 === '') {
    $message = '새 비밀번호를 입력해주세요.';
  } elseif ($newPw !== $newPw2) {
    $message = '비밀번호가 일치하지 않습니다.';
  } elseif (mb_strlen($newPw) < 8) {
    $message = '비밀번호는 8자 이상으로 설정해주세요.';
  } else {

    $row = find_reset_row_by_token($conn, $token);
    if (!$row) {
      $message = '유효하지 않거나 만료된 토큰입니다.';
    } else {
      $userId = (int)$row['user_id'];
      $resetId = (int)$row['id'];

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
        if (defined('DEV_MODE') && DEV_MODE) {
          $message .= ' ' . $e->getMessage();
        }
      }
    }
  }

  // DEV에서 폼 다시 보여주려고 token 유지
  if (defined('DEV_MODE') && DEV_MODE) {
    $devToken = $token;
    $devResetLink = url('/auth/reset_password.php?token=' . $token);
  }
}

// ---------------------------
// 2) 재설정 요청 단계 (토큰 생성)
// ---------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'request_reset') {

  $username = trim($_POST['username'] ?? '');

  if ($username === '') {
    $message = '아이디를 입력해주세요.';
  } else {

    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    $stmt->close();

    // 계정 존재 여부는 숨김
    $message = '해당 아이디는 존재하지 않습니다';

    if ($user) {
      $userId = (int)$user['id'];

      $rawToken = base64url_encode(random_bytes(32));
      $tokenHash = password_hash($rawToken, PASSWORD_DEFAULT);
      $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));

      $ins = $conn->prepare("INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
      $ins->bind_param("iss", $userId, $tokenHash, $expiresAt);
      $ins->execute();
      $ins->close();

      // DEV에서만 토큰/링크 표시
      if (defined('DEV_MODE') && DEV_MODE) {
        $devToken = $rawToken;
        $devResetLink = url('/auth/reset_password.php?token=' . $rawToken);
        $message = '아래에서 바로 비밀번호를 재설정할 수 있습니다.';
      }
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
  .container{max-width:860px;margin:0 auto;padding:0 16px}
  .top{background:#fff;border-bottom:1px solid var(--line)}
  .top-inner{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:14px 0;flex-wrap:wrap}
  .brand{font-weight:900;font-size:18px}
  .brand span{color:var(--brand)}
  .nav{display:flex;gap:8px;flex-wrap:wrap}
  .nav a{padding:8px 10px;border:1px solid var(--line);border-radius:12px;background:#fff}
  .nav a:hover{border-color:#cfd3e6}

  .page{padding:22px 0 28px}
  .card{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow);padding:16px}
  h1{margin:0 0 6px;font-size:22px}
  .sub{margin:0 0 14px;color:var(--muted);line-height:1.5}
  .row{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
  .input{width:100%;padding:12px;border:1px solid var(--line);border-radius:12px;outline:none;background:#fff}
  .btn{padding:12px 14px;border-radius:12px;border:1px solid var(--line);background:#fff;cursor:pointer}
  .btn.primary{background:#111;color:#fff;border-color:#111}
  .btn:hover{border-color:#cfd3e6}
  .msg{padding:10px 12px;border-radius:12px;border:1px solid var(--line);margin:12px 0;background:#fafbff}
  .hint{font-size:12px;color:var(--muted)}
  .divider{height:1px;background:var(--line);margin:14px 0}
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
      <h1>비밀번호 재설정</h1>
      <p class="sub">아이디를 입력하면 비밀번호 재설정을 진행합니다. 아래에서 바로 재설정이 가능합니다</p>

      <?php if ($message): ?>
        <div class="msg"><?= h($message) ?></div>
      <?php endif; ?>

      <!-- 1) 재설정 요청 -->
      <form method="post" class="row" style="margin:0;">
        <input type="hidden" name="action" value="request_reset">
        <input class="input" style="max-width:520px;" type="text" name="username" required placeholder="아이디 입력">
        <button class="btn primary" type="submit">비밀번호 재설정</button>
      </form>

      <div class="row" style="margin-top:12px;justify-content:flex-start;">
        <a class="btn" href="<?= url('/login.php') ?>">로그인으로</a>
        <a class="btn" href="<?= url('/auth/find_id.php') ?>">아이디 찾기</a>
      </div>

      <?php if (defined('DEV_MODE') && DEV_MODE && $devToken): ?>
        <div class="divider"></div>

        <!-- 2) DEV_MODE: 같은 페이지에서 비번 변경 -->
        <h3 style="margin:0 0 8px;">새 비밀번호 설정</h3>
        <form method="post" class="row" style="margin:0;">
          <input type="hidden" name="action" value="do_reset">
          <input type="hidden" name="token" value="<?= h($devToken) ?>">

          <input class="input" style="max-width:260px;" type="password" name="new_password" required placeholder="새 비밀번호(8자+)">
          <input class="input" style="max-width:260px;" type="password" name="new_password2" required placeholder="비밀번호 확인">
          <button class="btn primary" type="submit">변경</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

</body>
</html>