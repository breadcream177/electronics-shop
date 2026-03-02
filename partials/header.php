<?php
// header.php는 include로만 사용
$is_logged_in = isset($_SESSION['user_id']);
$username = $_SESSION['username'] ?? '';
$is_admin = (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1);
?>
<header class="topbar">
  <div class="container topbar-inner">
    <div class="top-links">
      <?php if ($is_logged_in): ?>
        <span class="muted">환영합니다 <b><?php echo htmlspecialchars($username); ?></b>님</span>
        <a href="/electronics_shop/mypage/mypage.php">마이페이지</a>
        <a href="/electronics_shop/auth/logout.php">로그아웃</a>
      <?php else: ?>
        <a href="/electronics_shop/auth/login.php">로그인</a>
        <a href="/electronics_shop/auth/register.php">회원가입</a>
      <?php endif; ?>
      <?php if ($is_admin): ?>
        <span class="dot">•</span>
        <a class="pill" href="/electronics_shop/admin/admin.php">관리자</a>
      <?php endif; ?>
    </div>
    <div class="top-icons">
      <a href="/electronics_shop/cart/cart.php" title="장바구니">🛒</a>
      <a href="/electronics_shop/wishlist/wishlist.php" title="찜">❤️</a>
      <a href="/electronics_shop/order/my_orders.php" title="주문내역">📦</a>
      <a href="/electronics_shop/qna/qna_list.php" title="Q&A">💬</a>
    </div>
  </div>
</header>

<header class="mainheader">
  <div class="container mainheader-inner">
    <a class="logo" href="/electronics_shop/index.php">electro<span>Shop</span></a>

    <!-- ✅ 여기 search form은 네 기존 index.php GET 파라미터 구조에 맞춰 name만 맞추면 됨 -->
    <form class="search" method="get" action="/electronics_shop/index.php">
      <input type="text" name="q" placeholder="상품명 검색" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
      <button type="submit">검색</button>
    </form>

    <div class="header-actions">
      <a class="action" href="/electronics_shop/cart/cart.php">장바구니</a>
      <a class="action" href="/electronics_shop/order/my_orders.php">주문내역</a>
    </div>
  </div>

  <nav class="gnb">
    <div class="container gnb-inner">
      <!-- 카테고리/필터는 네 DB 구조에 맞춰 연결 -->
      <a href="/electronics_shop/index.php">전체</a>
      <a href="/electronics_shop/index.php?category=phone">스마트폰</a>
      <a href="/electronics_shop/index.php?category=tablet">태블릿</a>
      <a href="/electronics_shop/index.php?category=audio">오디오</a>
      <a href="/electronics_shop/index.php?category=pc">PC/노트북</a>
      <a href="/electronics_shop/index.php?sort=latest">최신순</a>
      <a href="/electronics_shop/index.php?sort=price_asc">가격낮은순</a>
      <a href="/electronics_shop/index.php?sort=price_desc">가격높은순</a>
    </div>
  </nav>
</header>