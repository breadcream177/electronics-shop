<?php
// login.php
session_start();

require_once __DIR__ . "/config.php";   // ✅ 추가
require_once __DIR__ . "/DB/db.php";

// 이미 로그인 상태면 메인으로
if (isset($_SESSION['user_id'])) {
    header("Location: " . url('/index.php'));
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = "아이디/비밀번호를 입력하세요.";
    } else {
        $stmt = $conn->prepare("SELECT id, username, display_name, password FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && isset($user['password']) && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['username'] = (string)$user['username'];
            $_SESSION['display_name'] = (string)($user['display_name'] ?: $user['username']);

            if ($user['username'] === 'admin') {
                header("Location: " . url('/admin/admin.php'));
                exit;
            }

            header("Location: " . url('/index.php'));
            exit;
        } else {
            $error = "로그인 실패 (아이디 또는 비밀번호가 올바르지 않습니다.)";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>로그인</title>
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
        .links { display:flex; gap:10px; justify-content:space-between; margin-top:14px; font-size:13px; flex-wrap:wrap; }
        .links a { color:#333; text-decoration:none; }
        .links a:hover { text-decoration:underline; }
        .kakao { margin-top: 10px; display:block; text-align:center; padding:11px 12px; border-radius:10px; background:#FEE500; color:#191919; font-weight:800; text-decoration:none; }
        .kakao:hover { opacity:0.92; }
        .google { margin-top: 10px; display:block; text-align:center; padding:11px 12px; border-radius:10px; background:#fff; color:#111; font-weight:800; text-decoration:none; border:1px solid #ddd; }
        .google:hover { opacity:0.92; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1 class="title">로그인</h1>
        <p class="sub">아이디/비밀번호로 로그인하거나 카카오/구글로 로그인하세요.</p>

        <?php if ($error !== ""): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php" autocomplete="off">
            <div class="row">
                <label for="username">아이디</label>
                <input id="username" type="text" name="username" required />
            </div>

            <div class="row">
                <label for="password">비밀번호</label>
                <input id="password" type="password" name="password" required />
            </div>

            <button class="btn" type="submit">로그인</button>
        </form>

        <a class="kakao" href="<?= url('/kakao/kakao_login.php') ?>">카카오로 로그인</a>
        <a class="google" href="<?= url('/google/google_login.php') ?>">Google로 로그인</a>

        <div class="links">
            <a href="<?= url('/index.php') ?>">메인으로</a>
            <a href="<?= url('/register.php') ?>">회원가입</a>
            <a href="<?= url('/auth/find_id.php') ?>">아이디 찾기</a>
            <a href="<?= url('/auth/forgot_password.php') ?>">비밀번호 재설정</a>
        </div>
    </div>
</div>
</body>
</html>