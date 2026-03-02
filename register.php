<?php
session_start();

require_once __DIR__ . "/config.php";
require_once __DIR__ . "/DB/db.php";

// 이미 로그인 상태면 메인으로
if (isset($_SESSION['user_id'])) {
    header("Location: " . url('/index.php'));
    exit;
}

$error = "";
$joined = false;

// ✅ 입력값 유지용(에러 시에도 폼 유지)
$val_username = '';
$val_display_name = '';
$val_email = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username     = trim($_POST['username'] ?? '');
    $display_name = trim($_POST['display_name'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $password     = (string)($_POST['password'] ?? '');
    $password2    = (string)($_POST['password2'] ?? '');

    $val_username = $username;
    $val_display_name = $display_name;
    $val_email = $email;

    if ($username === '' || $email === '' || $password === '' || $password2 === '') {
        $error = "모든 항목을 입력하세요.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "이메일 형식이 올바르지 않습니다.";
    } elseif (mb_strlen($username) < 3 || mb_strlen($username) > 20) {
        $error = "아이디는 3~20자여야 합니다.";
    } elseif (mb_strlen($display_name) > 50) {
        $error = "표시이름은 50자 이하여야 합니다.";
    } elseif (strlen($password) < 4) {
        $error = "비밀번호는 최소 4자 이상이어야 합니다.";
    } elseif ($password !== $password2) {
        $error = "비밀번호 확인이 일치하지 않습니다.";
    } else {
        if ($display_name === '') $display_name = $username;

        // 중복 체크(username 또는 email)
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($exists) {
            $error = "이미 사용 중인 아이디 또는 이메일입니다.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (username, display_name, password, email) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $display_name, $hash, $email);

            if ($stmt->execute()) {
                $new_id = (int)$stmt->insert_id;
                $stmt->close();

                // ✅ 가입 직후 로그인 세션 세팅은 유지(기존 로직 그대로)
                $_SESSION['user_id'] = $new_id;
                $_SESSION['username'] = $username;
                $_SESSION['display_name'] = $display_name;

                // ✅ 바로 이동하지 않고 모달 띄우기
                $joined = true;

                // 폼 값은 비워도 되고 유지해도 되는데, UX상 비우는 게 자연스러움
                $val_username = '';
                $val_display_name = '';
                $val_email = '';
            } else {
                $stmt->close();
                $error = "회원가입 실패(DB 오류).";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>회원가입</title>
  <style>
    body { margin:0; font-family: Arial, sans-serif; background:#f5f6f8; }
    .wrap { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; }
    .card { width:100%; max-width:420px; background:#fff; border-radius:14px; box-shadow:0 10px 30px rgba(0,0,0,0.08); padding:26px; }
    .title { font-size:22px; font-weight:700; margin:0 0 6px; }
    .sub { margin:0 0 18px; color:#666; font-size:13px; }
    .row { margin-bottom:12px; }
    label { display:block; font-size:13px; margin-bottom:6px; color:#333; }
    input { width:100%; padding:12px; border:1px solid #ddd; border-radius:10px; font-size:14px; outline:none; }
    input:focus { border-color:#111; }
    .btn { width:100%; padding:12px; border:0; border-radius:10px; background:#111; color:#fff; font-weight:700; cursor:pointer; margin-top:6px; }
    .btn:hover { opacity:0.92; }
    .error { background:#ffecec; border:1px solid #ffbcbc; color:#b30000; padding:10px 12px; border-radius:10px; font-size:13px; margin-bottom:12px; }
    .links { display:flex; justify-content:space-between; margin-top:14px; font-size:13px; }
    .links a { color:#333; text-decoration:none; }
    .links a:hover { text-decoration:underline; }

    /* ✅ 가입완료 모달 */
    .modal-overlay {
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0,0,0,0.4);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 9999;
    }
    .modal-box {
      background: #fff;
      padding: 26px;
      border-radius: 14px;
      width: 320px;
      text-align: center;
      box-shadow: 0 15px 40px rgba(0,0,0,0.15);
    }
    .modal-box h3 {
      margin: 0 0 10px;
      font-size: 18px;
    }
    .modal-box p {
      margin: 0 0 18px;
      font-size: 14px;
      color: #555;
      line-height: 1.4;
    }
    .modal-box button {
      width: 100%;
      padding: 11px 12px;
      border: 0;
      border-radius: 10px;
      background: #111;
      color: #fff;
      font-weight: 700;
      cursor: pointer;
    }
    .modal-box button:hover { opacity: 0.92; }
  </style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <h1 class="title">회원가입</h1>
    <p class="sub">아이디/표시이름/이메일/비밀번호로 계정을 생성합니다.</p>

    <?php if ($error !== ""): ?>
      <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="POST" action="register.php" autocomplete="off">
      <div class="row">
        <label for="username">아이디(로그인용)</label>
        <input id="username" type="text" name="username" required
               value="<?= htmlspecialchars($val_username, ENT_QUOTES, 'UTF-8') ?>"
               autocomplete="username" />
      </div>

      <div class="row">
        <label for="display_name">표시이름(타인에게 보이는 이름)</label>
        <input id="display_name" type="text" name="display_name" placeholder="예: 홍길동"
               value="<?= htmlspecialchars($val_display_name, ENT_QUOTES, 'UTF-8') ?>"
               autocomplete="nickname" />
      </div>

      <div class="row">
        <label for="email">이메일</label>
        <input id="email" type="email" name="email" required
               value="<?= htmlspecialchars($val_email, ENT_QUOTES, 'UTF-8') ?>"
               autocomplete="email" />
      </div>

      <div class="row">
        <label for="password">비밀번호</label>
        <input id="password" type="password" name="password" required autocomplete="new-password" />
      </div>

      <div class="row">
        <label for="password2">비밀번호 확인</label>
        <input id="password2" type="password" name="password2" required autocomplete="new-password" />
      </div>

      <button class="btn" type="submit">회원가입</button>
    </form>

    <div class="links">
      <a href="<?= url('/login.php') ?>">로그인으로</a>
      <a href="<?= url('/index.php') ?>">메인으로</a>
    </div>
  </div>

  <?php if ($joined): ?>
    <div class="modal-overlay">
      <div class="modal-box">
        <h3>가입 완료</h3>
        <p>회원가입이 완료되었습니다.<br>홈페이지로 이동합니다.</p>
        <button type="button" onclick="goHome()">확인</button>
      </div>
    </div>
    <script>
      function goHome() {
        window.location.href = "<?= url('/index.php') ?>";
      }
    </script>
  <?php endif; ?>

</div>
</body>
</html>