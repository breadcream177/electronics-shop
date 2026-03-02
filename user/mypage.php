<?php
session_start();
require_once __DIR__ . "/../DB/db.php";

$BASE = "/electronics_shop";

if (!isset($_SESSION['user_id'])) {
  header("Location: {$BASE}/login.php");
  exit;
}

$user_id = (int)$_SESSION['user_id'];
$error = "";
$ok = "";

// ✅ 표시이름 변경 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $new_display = trim($_POST['display_name'] ?? '');

  if ($new_display === '') {
    $error = "표시이름은 비울 수 없습니다.";
  } elseif (mb_strlen($new_display) > 50) {
    $error = "표시이름은 50자 이하여야 합니다.";
  } else {
    $stmt = $conn->prepare("UPDATE users SET display_name=? WHERE id=?");
    $stmt->bind_param("si", $new_display, $user_id);
    if ($stmt->execute()) {
      $_SESSION['display_name'] = $new_display; // ✅ 세션도 동기화
      $ok = "표시이름이 변경되었습니다.";
    } else {
      $error = "변경 실패(DB 오류).";
    }
    $stmt->close();
  }
}

$stmt = $conn->prepare("SELECT id, username, display_name, email, created_at FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
  http_response_code(404);
  exit("유저 정보를 찾을 수 없습니다.");
}

$email = $user['email'] ?? '';
$display_name = ($user['display_name'] ?? '') !== '' ? $user['display_name'] : ($user['username'] ?? '');
$username = $user['username'] ?? '';
$created_at = $user['created_at'] ?? '';
?>
<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>마이페이지</title>
  <style>
    :root{
      --bg:#f6f7fb; --card:#fff; --line:#e7e8ee; --text:#111; --muted:#666;
      --brand:#0b57d0; --shadow:0 10px 24px rgba(0,0,0,.06); --radius:16px;
    }
    *{box-sizing:border-box}
    body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:var(--bg);color:var(--text)}
    a{color:inherit;text-decoration:none}
    .container{max-width:980px;margin:0 auto;padding:0 16px}
    .top{background:#fff;border-bottom:1px solid var(--line)}
    .top-inner{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:14px 0;flex-wrap:wrap}
    .brand{font-weight:900;font-size:18px}
    .brand span{color:var(--brand)}
    .nav{display:flex;gap:8px;flex-wrap:wrap}
    .nav a{padding:8px 10px;border:1px solid var(--line);border-radius:12px;background:#fff}
    .nav a:hover{border-color:#cfd3e6}

    .page{padding:18px 0 28px}
    .grid{display:grid;grid-template-columns:1fr 320px;gap:14px}
    .card{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow);padding:16px}
    .title{margin:0 0 10px;font-size:22px;letter-spacing:-.3px}
    .sub{margin:0;color:var(--muted);line-height:1.5}
    .section{margin-top:14px}
    .section h3{margin:0 0 8px;font-size:16px}
    .kv{display:grid;grid-template-columns:140px 1fr;gap:8px;border:1px solid var(--line);border-radius:14px;overflow:hidden}
    .kv div{padding:10px 12px}
    .kv .k{background:#fafbff;color:#444}
    .kv .v{background:#fff;font-weight:600}
    .pill{display:inline-block;padding:4px 8px;border:1px solid var(--line);border-radius:999px;background:#fff;font-size:12px;color:#444}

    .row{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
    .input{flex:1;min-width:180px;padding:12px 12px;border:1px solid var(--line);border-radius:12px;background:#fff;outline:none}
    .btn{padding:12px 14px;border-radius:12px;border:1px solid var(--line);background:#fff;cursor:pointer}
    .btn.primary{background:#111;color:#fff;border-color:#111}
    .btn:hover{border-color:#cfd3e6}

    .msg{padding:10px 12px;border-radius:12px;border:1px solid var(--line);margin-bottom:10px}
    .msg.ok{border-color:#ccebd6;background:#f3fbf6;color:#0b6b2e}
    .msg.err{border-color:#ffd6d6;background:#fff4f4;color:#b30000}

    .quick a{display:flex;align-items:center;justify-content:space-between;gap:10px;
      padding:12px 12px;border:1px solid var(--line);border-radius:14px;background:#fff;margin-bottom:10px}
    .quick a:hover{border-color:#cfd3e6}
    .right{color:var(--muted);font-size:13px}

    @media (max-width: 920px){
      .grid{grid-template-columns:1fr}
    }
  </style>
</head>
<body>

<div class="top">
  <div class="container top-inner">
    <div class="brand">electro<span>Shop</span> · 마이페이지</div>
    <div class="nav">
      <a href="<?php echo $BASE; ?>/index.php">메인</a>
      <a href="<?php echo $BASE; ?>/cart/cart.php">장바구니</a>
      <a href="<?php echo $BASE; ?>/wishlist/wishlist.php">찜</a>
      <a href="<?php echo $BASE; ?>/order/my_orders.php">주문내역</a>
      <a href="<?php echo $BASE; ?>/logout.php">로그아웃</a>
    </div>
  </div>
</div>

<div class="page">
  <div class="container">
    <div class="grid">
      <!-- 좌측: 내 정보/수정 -->
      <div class="card">
        <h1 class="title">안녕하세요, <?php echo htmlspecialchars($display_name); ?>님 👋</h1>
        <p class="sub">계정 정보 확인 및 표시이름을 수정할 수 있습니다.</p>

        <?php if ($ok): ?>
          <div class="msg ok"><?php echo htmlspecialchars($ok); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
          <div class="msg err"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="section">
          <h3>내 정보</h3>
          <div class="kv">
            <div class="k">아이디(로그인)</div>
            <div class="v"><?php echo htmlspecialchars($username); ?></div>

            <div class="k">표시이름(노출)</div>
            <div class="v"><?php echo htmlspecialchars($display_name); ?> <span class="pill">edit</span></div>

            <div class="k">이메일</div>
            <div class="v"><?php echo $email !== '' ? htmlspecialchars($email) : '(미등록)'; ?></div>

            <div class="k">가입일</div>
            <div class="v"><?php echo htmlspecialchars($created_at); ?></div>
          </div>
        </div>

        <div class="section">
          <h3>표시이름 변경</h3>
          <form method="post" action="mypage.php" class="row" style="margin:0;">
            <input class="input" type="text" name="display_name" value="<?php echo htmlspecialchars($display_name); ?>" />
            <button class="btn primary" type="submit">저장</button>
          </form>
          <p class="sub" style="margin-top:8px;font-size:13px;">표시이름은 상품 리뷰, Q&A 등에서 노출됩니다.</p>
        </div>
      </div>

      <!-- 우측: 바로가기 -->
      <div class="card">
        <h2 class="title" style="font-size:18px;margin-bottom:6px;">바로가기</h2>
        <p class="sub" style="font-size:13px;">자주 쓰는 메뉴로 빠르게 이동하세요.</p>

        <div class="section quick">
          <a href="<?php echo $BASE; ?>/order/my_orders.php">
            <span>📦 주문내역</span>
            <span class="right">보기 →</span>
          </a>
          <a href="<?php echo $BASE; ?>/wishlist/wishlist.php">
            <span>❤️ 찜 목록</span>
            <span class="right">보기 →</span>
          </a>
          <a href="<?php echo $BASE; ?>/cart/cart.php">
            <span>🛒 장바구니</span>
            <span class="right">보기 →</span>
          </a>
          <a href="<?php echo $BASE; ?>/qna/qna_list.php">
            <span>💬 Q&A</span>
            <span class="right">보기 →</span>
          </a>
        </div>

        <div class="section">
          <div class="sub" style="font-size:12px;">
            제출/시연용 프로젝트입니다. (PHP · MariaDB · PortOne 카카오페이)
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

</body>
</html>