<?php
session_start();
require_once __DIR__ . "/DB/db.php";

/*
  ✅ 기능
  - 검색(q): 상품명 LIKE
  - 가격 필터: min_price, max_price
  - 정렬(sort): new | price_asc | price_desc | rating_desc
  - 목록에 평균별점(avg_rating) + 리뷰수(review_count) 표시
*/

$BASE = "/electronics_shop";

// --- 로그인/권한 ---
$is_login = isset($_SESSION['user_id']);
$is_admin = (isset($_SESSION['username']) && $_SESSION['username'] === 'admin');
$viewer_name = $_SESSION['display_name'] ?? $_SESSION['username'] ?? '';

// --- 파라미터 받기 ---
$q = trim((string)($_GET['q'] ?? ''));
$min_price = (int)($_GET['min_price'] ?? 0);
$max_price = (int)($_GET['max_price'] ?? 0);
$sort = (string)($_GET['sort'] ?? 'new'); // new | price_asc | price_desc | rating_desc

// --- WHERE 조립(Prepared Statement) ---
$where = [];
$types = '';
$params = [];

if ($q !== '') {
    $where[] = "p.name LIKE ?";
    $types .= "s";
    $params[] = "%{$q}%";
}
if ($min_price > 0) {
    $where[] = "p.price >= ?";
    $types .= "i";
    $params[] = $min_price;
}
if ($max_price > 0) {
    $where[] = "p.price <= ?";
    $types .= "i";
    $params[] = $max_price;
}

// --- 정렬 ---
$orderBy = "ORDER BY p.created_at DESC";
if ($sort === 'price_asc') $orderBy = "ORDER BY p.price ASC";
if ($sort === 'price_desc') $orderBy = "ORDER BY p.price DESC";
if ($sort === 'rating_desc') $orderBy = "ORDER BY avg_rating DESC, review_count DESC, p.created_at DESC";

// --- 리뷰 집계 서브쿼리 ---
$sql = "
SELECT
  p.id, p.name, p.price, p.image,
  COALESCE(r.review_count, 0) AS review_count,
  COALESCE(r.avg_rating, 0) AS avg_rating
FROM products p
LEFT JOIN (
  SELECT product_id, COUNT(*) AS review_count, AVG(rating) AS avg_rating
  FROM reviews
  GROUP BY product_id
) r ON r.product_id = p.id
";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " " . $orderBy;

$stmt = $conn->prepare($sql);
if ($types !== '') {
    $bindParams = [];
    $bindParams[] = $types;
    foreach ($params as $k => $v) $bindParams[] = &$params[$k];
    call_user_func_array([$stmt, 'bind_param'], $bindParams);
}
$stmt->execute();
$result = $stmt->get_result();

// 별점 UI 함수(간단)
function renderStarsSimple($rating, $max = 5) {
    $rating = max(0, min($max, (float)$rating));
    $full = (int)round($rating);
    $html = '';
    for ($i=1; $i<=$max; $i++) {
        $html .= ($i <= $full) ? '<span class="star full">★</span>' : '<span class="star empty">★</span>';
    }
    return $html;
}

// 정렬 라벨
function sortLabel($sort){
    switch($sort){
        case 'price_asc': return '가격 낮은순';
        case 'price_desc': return '가격 높은순';
        case 'rating_desc': return '별점 높은순';
        default: return '최신순';
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>전자기기 쇼핑몰</title>

    <!-- ✅ 기존 style.css 유지(있으면) -->
    <link rel="stylesheet" href="style.css">

    <style>
        :root{
            --bg:#f6f7fb; --card:#fff; --line:#e7e8ee; --text:#111; --muted:#666;
            --brand:#0b57d0; --shadow:0 10px 24px rgba(0,0,0,.06);
            --radius:16px;
        }
        *{box-sizing:border-box}
        body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:var(--bg);color:var(--text)}
        a{color:inherit;text-decoration:none}
        .container{max-width:1180px;margin:0 auto;padding:0 16px}
        .muted{color:var(--muted)}
        .pill{display:inline-block;padding:6px 10px;border:1px solid var(--line);border-radius:999px;background:#fff;font-size:12px}

        /* Topbar */
        .topbar{background:#fff;border-bottom:1px solid var(--line)}
        .topbar-inner{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:10px 0;flex-wrap:wrap}
        .top-links{display:flex;align-items:center;gap:10px;flex-wrap:wrap;font-size:13px}
        .top-links a{padding:6px 8px;border-radius:10px}
        .top-links a:hover{background:#f0f3ff}
        .icons{display:flex;gap:10px;align-items:center}
        .icons a{font-size:18px;padding:6px 8px;border-radius:10px}
        .icons a:hover{background:#f0f3ff}

        /* Main header */
        .mainheader{background:#fff;border-bottom:1px solid var(--line)}
        .mainheader-inner{display:flex;align-items:center;gap:16px;padding:16px 0;flex-wrap:wrap}
        .logo{font-weight:900;font-size:22px;letter-spacing:-.5px}
        .logo span{color:var(--brand)}
        .search{flex:1;display:flex;gap:8px;min-width:280px}
        .search input{flex:1;padding:12px 14px;border:1px solid var(--line);border-radius:999px;outline:none;background:#fafbff}
        .search button{padding:12px 14px;border-radius:999px;border:1px solid var(--brand);background:var(--brand);color:#fff;font-weight:700;cursor:pointer}
        .header-actions{display:flex;gap:10px}
        .action{padding:10px 12px;border:1px solid var(--line);border-radius:12px;background:#fff}
        .action:hover{border-color:#cfd3e6}

        /* GNB */
        .gnb{border-top:1px solid var(--line)}
        .gnb-inner{display:flex;gap:10px;flex-wrap:wrap;padding:10px 0;font-size:14px}
        .gnb-inner a{padding:8px 10px;border-radius:10px}
        .gnb-inner a:hover{background:#f0f3ff}

        /* Page */
        .page{padding:18px 0 28px}
        .hero-grid{display:grid;grid-template-columns:260px 1fr 320px;gap:14px;margin-top:14px}
        .panel{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden}
        .panel.pad{padding:14px}
        .quick a{display:block;padding:10px;border-radius:12px;border:1px solid var(--line);margin-bottom:10px;background:#fff}
        .quick a:hover{border-color:#cfd3e6}

        .banner{min-height:260px;display:flex;align-items:flex-end;padding:18px;background:linear-gradient(135deg,#e9f0ff,#ffffff)}
        .banner h2{margin:0;font-size:28px}
        .banner p{margin:8px 0 0;color:#444;line-height:1.5}
        .banner .cta{margin-top:14px;display:inline-block;padding:10px 14px;border-radius:12px;background:#111;color:#fff}

        .promo{height:210px;border-radius:14px;border:1px solid var(--line);background:#111;color:#fff;display:flex;align-items:center;justify-content:center}

        .section-title{display:flex;justify-content:space-between;align-items:end;margin:22px 0 12px;gap:10px;flex-wrap:wrap}
        .section-title h3{margin:0;font-size:18px}
        .chips{display:flex;gap:8px;flex-wrap:wrap}
        .chip{padding:7px 10px;border:1px solid var(--line);border-radius:999px;background:#fff;font-size:13px}
        .chip.active{border-color:var(--brand);color:var(--brand);font-weight:700}

        /* Filter panel */
        .filter{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
        .filter input,.filter select{padding:10px 12px;border:1px solid var(--line);border-radius:12px;background:#fff}
        .filter button{padding:10px 12px;border:1px solid #111;background:#111;color:#fff;border-radius:12px;cursor:pointer}
        .filter a.reset{padding:10px 12px;border:1px solid var(--line);border-radius:12px;background:#fff}

        /* Product grid */
        .grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}
        .card{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow);padding:14px;display:flex;flex-direction:column;gap:10px}
        .thumb{height:160px;border-radius:14px;background:#f1f2f7;border:1px solid var(--line);display:flex;align-items:center;justify-content:center;overflow:hidden}
        .thumb img{width:100%;height:100%;object-fit:cover}
        .pname{font-weight:900}
        .meta{display:flex;justify-content:space-between;align-items:center;gap:8px;color:var(--muted);font-size:13px}
        .stars{display:flex;align-items:center;gap:6px}
        .star{font-size:14px;line-height:1}
        .star.full{color:#f5b301}
        .star.empty{color:#ddd}
        .price{font-weight:900;font-size:18px}
        .btnrow{display:flex;gap:8px;flex-wrap:wrap}
        .btn{padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;cursor:pointer}
        .btn.primary{background:#111;color:#fff;border-color:#111}
        .btn:hover{border-color:#cfd3e6}

        /* Footer */
        .footer{margin-top:22px;background:#fff;border-top:1px solid var(--line)}
        .footer-inner{padding:18px 0;display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap}

        @media (max-width: 1100px){
          .hero-grid{grid-template-columns:1fr}
          .grid{grid-template-columns:repeat(3,1fr)}
        }
        @media (max-width: 760px){
          .grid{grid-template-columns:repeat(2,1fr)}
          .search{min-width:unset;width:100%}
          .header-actions{width:100%}
        }
        @media (max-width: 420px){
          .grid{grid-template-columns:1fr}
        }
    </style>
</head>
<body>

<!-- ✅ Topbar -->
<div class="topbar">
  <div class="container topbar-inner">
    <div class="top-links">
      <?php if ($is_login): ?>
        <span class="muted">환영합니다 <b><?php echo htmlspecialchars($viewer_name); ?></b>님</span>

        <?php if ($is_admin): ?>
          <a class="pill" href="<?php echo $BASE; ?>/admin/admin.php">관리자</a>
        <?php else: ?>
          <a href="<?php echo $BASE; ?>/user/mypage.php">마이페이지</a>
        <?php endif; ?>

        <a href="<?php echo $BASE; ?>/logout.php">로그아웃</a>
      <?php else: ?>
        <a href="<?php echo $BASE; ?>/login.php">로그인</a>
        <a href="<?php echo $BASE; ?>/register.php">회원가입</a>
      <?php endif; ?>
    </div>

    <div class="icons">
      <a href="<?php echo $BASE; ?>/cart/cart.php" title="장바구니">🛒</a>
      <a href="<?php echo $BASE; ?>/wishlist/wishlist.php" title="찜">❤️</a>
      <a href="<?php echo $BASE; ?>/qna/qna_list.php" title="Q&A">💬</a>
      <a href="<?php echo $BASE; ?>/order/my_orders.php" title="주문내역">📦</a>
    </div>
  </div>
</div>

<!-- ✅ Main header -->
<div class="mainheader">
  <div class="container mainheader-inner">
    <a class="logo" href="<?php echo $BASE; ?>/index.php">electro<span>Shop</span></a>

    <!-- 검색은 기존 q 유지 -->
    <form class="search" method="get" action="index.php">
      <input type="text" name="q" placeholder="상품명 검색" value="<?php echo htmlspecialchars($q); ?>" />
      <button type="submit">검색</button>
    </form>

    <div class="header-actions">
      <a class="action" href="<?php echo $BASE; ?>/cart/cart.php">장바구니</a>
      <a class="action" href="<?php echo $BASE; ?>/order/my_orders.php">주문내역</a>
    </div>
  </div>

  <div class="gnb">
    <div class="container gnb-inner">
      <a href="<?php echo $BASE; ?>/index.php">전체</a>
      <a href="<?php echo $BASE; ?>/index.php?sort=new">최신</a>
      <a href="<?php echo $BASE; ?>/index.php?sort=price_asc">가격↓</a>
      <a href="<?php echo $BASE; ?>/index.php?sort=price_desc">가격↑</a>
      <a href="<?php echo $BASE; ?>/index.php?sort=rating_desc">별점</a>
    </div>
  </div>
</div>

<div class="page">
  <div class="container">

    <!-- ✅ Hero -->
    <div class="hero-grid">
      <div class="panel pad quick">
        <a href="<?php echo $BASE; ?>/index.php?sort=new">🆕 최신 상품</a>
        <a href="<?php echo $BASE; ?>/index.php?sort=price_asc">💸 가성비 추천</a>
        <a href="<?php echo $BASE; ?>/order/my_orders.php">📦 주문 조회</a>
        <a href="<?php echo $BASE; ?>/qna/qna_list.php">💬 Q&A</a>
        <a href="<?php echo $BASE; ?>/wishlist/wishlist.php">❤️ 찜 목록</a>
      </div>

      <div class="panel banner">
        <div>
          <h2>전자기기 쇼핑몰</h2>
          <p>반갑습니다</p>
          <a class="cta" href="#products">지금 쇼핑하기</a>
        </div>
      </div>

      <div class="panel pad">
        <div class="muted" style="font-size:13px;margin-bottom:8px;">현재 정렬</div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
          <span class="pill"><?php echo htmlspecialchars(sortLabel($sort)); ?></span>
          <?php if ($q !== ''): ?><span class="pill">검색: <?php echo htmlspecialchars($q); ?></span><?php endif; ?>
          <?php if ($min_price > 0): ?><span class="pill">최소: <?php echo number_format($min_price); ?>원</span><?php endif; ?>
          <?php if ($max_price > 0): ?><span class="pill">최대: <?php echo number_format($max_price); ?>원</span><?php endif; ?>
        </div>
        <div class="promo">PROMO</div>
      </div>
    </div>

    <!-- ✅ Products -->
    <div class="section-title" id="products">
      <div>
        <h3>상품 목록</h3>
        <div class="muted">필터/정렬로 원하는 상품을 찾아보세요</div>
      </div>

      <div class="chips">
        <a class="chip <?php echo ($sort==='new')?'active':''; ?>" href="<?php echo $BASE; ?>/index.php?sort=new">최신순</a>
        <a class="chip <?php echo ($sort==='price_asc')?'active':''; ?>" href="<?php echo $BASE; ?>/index.php?sort=price_asc">가격↓</a>
        <a class="chip <?php echo ($sort==='price_desc')?'active':''; ?>" href="<?php echo $BASE; ?>/index.php?sort=price_desc">가격↑</a>
        <a class="chip <?php echo ($sort==='rating_desc')?'active':''; ?>" href="<?php echo $BASE; ?>/index.php?sort=rating_desc">별점순</a>
      </div>
    </div>

    <!-- ✅ Filter panel (기존 파라미터 유지) -->
    <div class="panel pad" style="margin-bottom:14px;">
      <form method="GET" action="index.php" class="filter">
        <input type="text" name="q" placeholder="상품명 검색" value="<?php echo htmlspecialchars($q); ?>" />
        <input type="number" name="min_price" placeholder="최소가격" value="<?php echo (int)$min_price; ?>" />
        <input type="number" name="max_price" placeholder="최대가격" value="<?php echo (int)$max_price; ?>" />

        <select name="sort">
          <option value="new" <?php echo ($sort==='new')?'selected':''; ?>>최신순</option>
          <option value="price_asc" <?php echo ($sort==='price_asc')?'selected':''; ?>>가격 낮은순</option>
          <option value="price_desc" <?php echo ($sort==='price_desc')?'selected':''; ?>>가격 높은순</option>
          <option value="rating_desc" <?php echo ($sort==='rating_desc')?'selected':''; ?>>별점 높은순</option>
        </select>

        <button type="submit">적용</button>
        <a class="reset" href="index.php">초기화</a>
      </form>
    </div>

    <div class="grid">
      <?php while($row = $result->fetch_assoc()) { ?>
        <?php
          $pid = (int)$row['id'];
          $avg = (float)$row['avg_rating'];
          $cnt = (int)$row['review_count'];
          $img = (string)($row['image'] ?? '');
          $imgSrc = $BASE . "/images/" . rawurlencode($img);
        ?>
        <div class="card">
          <a class="thumb" href="product_detail.php?id=<?php echo $pid; ?>">
            <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
          </a>

          <div class="pname"><?php echo htmlspecialchars($row['name']); ?></div>

          <div class="meta">
            <div class="stars">
              <?php echo renderStarsSimple($avg); ?>
              <span class="muted"><?php echo number_format($avg, 1); ?></span>
            </div>
            <div class="muted">리뷰 <?php echo $cnt; ?>개</div>
          </div>

          <div class="price"><?php echo number_format((int)$row['price']); ?>원</div>

          <div class="btnrow">
            <a class="btn" href="product_detail.php?id=<?php echo $pid; ?>">상세보기</a>

            <form method="POST"
                  action="<?php echo $is_login ? $BASE.'/cart/add_to_cart.php' : $BASE.'/login.php'; ?>"
                  style="margin:0;">
              <input type="hidden" name="product_id" value="<?php echo $pid; ?>">
              <input type="hidden" name="quantity" value="1">
              <button type="submit" class="btn primary">장바구니</button>
            </form>

            <?php if($is_login) { ?>
              <form method="POST" action="<?php echo $BASE; ?>/wishlist/wishlist_add.php" style="margin:0;">
                <input type="hidden" name="product_id" value="<?php echo $pid; ?>">
                <button type="submit" class="btn">❤️ 찜</button>
              </form>
            <?php } ?>
          </div>
        </div>
      <?php } ?>
    </div>

    <?php if (isset($stmt)) $stmt->close(); ?>

  </div>
</div>

<div class="footer">
  <div class="container footer-inner">
    <div>
      <b>전자기기 쇼핑몰</b>
      <div class="muted">청?암</div>
    </div>
    <div class="muted">© <?php echo date('Y'); ?> electroShop</div>
  </div>
</div>

</body>
</html>