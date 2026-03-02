<?php
// 이 파일은 include 전용
$BASE = "/electronics_shop";
$current = basename($_SERVER['PHP_SELF']);
?>
<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo htmlspecialchars($page_title ?? "관리자페이지"); ?></title>
  <style>
    :root{
      --bg:#f6f7fb; --card:#fff; --line:#e7e8ee; --text:#111; --muted:#666;
      --brand:#0b57d0; --shadow:0 10px 24px rgba(0,0,0,.06); --radius:16px;
    }
    *{box-sizing:border-box}
    body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:var(--bg);color:var(--text)}
    a{color:inherit;text-decoration:none}
    .container{max-width:1180px;margin:0 auto;padding:0 16px}
    .top{background:#fff;border-bottom:1px solid var(--line)}
    .top-inner{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:14px 0;flex-wrap:wrap}
    .brand{font-weight:900;font-size:18px}
    .brand span{color:var(--brand)}
    .nav{display:flex;gap:8px;flex-wrap:wrap}
    .nav a{padding:8px 10px;border:1px solid var(--line);border-radius:12px;background:#fff}
    .nav a.active{border-color:var(--brand);color:var(--brand);font-weight:800}
    .nav a:hover{border-color:#cfd3e6}
    .page{padding:18px 0 28px}
    .card{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow);padding:14px}
    .title{margin:0 0 10px;font-size:20px}
    .muted{color:var(--muted)}
    .row{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
    .input, select{padding:10px 12px;border:1px solid var(--line);border-radius:12px;background:#fff}
    .btn{padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;cursor:pointer}
    .btn.primary{background:#111;color:#fff;border-color:#111}
    table{width:100%;border-collapse:separate;border-spacing:0}
    th,td{padding:10px 10px;border-bottom:1px solid var(--line);text-align:left;font-size:14px;vertical-align:top}
    th{background:#fafbff;font-size:13px;color:#444}
    tr:hover td{background:#fcfdff}
    .pill{display:inline-block;padding:4px 8px;border:1px solid var(--line);border-radius:999px;background:#fff;font-size:12px}
    .right{margin-left:auto}
    .footer{margin-top:18px;color:var(--muted);font-size:12px}
    .danger{border-color:#ffdad7}
    .danger.btn{background:#fff}
  </style>
</head>
<body>
  <div class="top">
    <div class="container top-inner">
      <div class="brand">Admin <span>Dashboard</span></div>
      <div class="nav">
        <a class="<?php echo ($current==='admin.php')?'active':''; ?>" href="<?php echo $BASE; ?>/admin/admin.php">주문</a>
        <a class="<?php echo ($current==='users.php')?'active':''; ?>" href="<?php echo $BASE; ?>/admin/users.php">유저</a>
        <a class="<?php echo ($current==='reviews.php')?'active':''; ?>" href="<?php echo $BASE; ?>/admin/reviews.php">리뷰</a>
        <a class="<?php echo ($current==='guest_inquiries.php')?'active':''; ?>" href="<?php echo $BASE; ?>/admin/guest_inquiries.php">비회원문의</a>
        <a href="<?php echo $BASE; ?>/index.php">메인</a>
        <a href="<?php echo $BASE; ?>/logout.php">로그아웃</a>
      </div>
    </div>
  </div>
  <div class="page">
    <div class="container">