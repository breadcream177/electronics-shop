<?php
session_start();
require_once __DIR__ . '/DB/db.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
  die("DB 연결 변수 \$conn 을 찾을 수 없습니다. DB/db.php에서 \$conn 생성 여부를 확인하세요.");
}

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function renderStars($rating, $max = 5) {
  $rating = max(0, min($max, (float)$rating));
  $full = (int)floor($rating);
  $half = ($rating - $full) >= 0.5 ? 1 : 0;
  $empty = $max - $full - $half;

  $html = '';
  for ($i=0; $i<$full; $i++) $html .= '<span class="star full">★</span>';
  if ($half) $html .= '<span class="star half">★</span>';
  for ($i=0; $i<$empty; $i++) $html .= '<span class="star empty">★</span>';
  return $html;
}

$product_id = (int)($_GET['product_id'] ?? ($_GET['id'] ?? 0));
if ($product_id <= 0) {
  die("잘못된 접근: product_id가 없습니다.");
}

/* 1) 상품 정보 */
$stmt = $conn->prepare("SELECT id, name, description, price, image, created_at FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) die("존재하지 않는 상품입니다.");

$productName  = $product['name'];
$productDesc  = $product['description'];
$productPrice = (int)$product['price'];
$productImage = $product['image'];

/* 2) 리뷰 요약 */
$stmt = $conn->prepare("
  SELECT COUNT(*) AS review_count, COALESCE(AVG(rating), 0) AS avg_rating
  FROM reviews
  WHERE product_id = ?
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();
$stmt->close();

$reviewCount = (int)($summary['review_count'] ?? 0);
$avgRating   = (float)($summary['avg_rating'] ?? 0);

/* 3) 리뷰 목록 (최신 10개) */
$stmt = $conn->prepare("
  SELECT
    r.id, r.user_id, r.rating, r.comment, r.created_at,
    COALESCE(NULLIF(u.display_name,''), u.username) AS writer_name
  FROM reviews r
  LEFT JOIN users u ON u.id = r.user_id
  WHERE r.product_id = ?
  ORDER BY r.created_at DESC
  LIMIT 10
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* 4) 로그인 정보 */
$loginUserId   = $_SESSION['user_id'] ?? null;
$loginUsername = $_SESSION['username'] ?? null;
$loginDisplayName = $_SESSION['display_name'] ?? null;
?>
<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= h($productName) ?> - 상품 상세</title>
  <link rel="stylesheet" href="/electronics_shop/css/style.css">
  <style>
    body{margin:0; background:#f6f7fb; color:#111;}
    .wrap{max-width:980px; margin:0 auto; padding:22px;}
    .card{background:#fff; border:1px solid #eee; border-radius:16px; padding:18px; box-shadow:0 6px 20px rgba(0,0,0,.04);}
    .product{display:grid; grid-template-columns:320px 1fr; gap:18px;}
    .imgbox{border-radius:14px; overflow:hidden; background:#fafafa; border:1px solid #eee; display:flex; align-items:center; justify-content:center; height:320px;}
    .imgbox img{width:100%; height:100%; object-fit:cover;}
    .p-title{margin:0 0 8px; font-size:24px;}
    .price{font-size:18px; font-weight:700; margin:8px 0 12px;}
    .desc{white-space:pre-wrap; color:#333; line-height:1.6; margin-top:10px;}
    .actions{margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;}
    .btn{display:inline-flex; align-items:center; justify-content:center; padding:10px 14px; border-radius:12px; border:1px solid #ddd; background:#fff; cursor:pointer; text-decoration:none; color:#111; font-weight:600;}
    .btn.primary{background:#111; color:#fff; border-color:#111;}
    .btn.danger{background:#fff; color:#c0392b; border-color:#f1c4bf;}
    .muted{color:#666; font-size:14px;}

    .rating-summary{display:flex; align-items:center; gap:12px; margin:10px 0 2px;}
    .rating-summary .stars{display:flex; align-items:center; gap:6px;}
    .star{font-size:18px; line-height:1;}
    .star.full{color:#f5b301;}
    .star.half{color:#f5b301; position:relative;}
    .star.half::after{content:"★"; color:#ddd; position:absolute; left:9px; top:0; width:9px; overflow:hidden;}
    .star.empty{color:#ddd;}
    .avg-num{font-weight:800;}

    .section{margin-top:18px;}
    .section h3{margin:0 0 10px; font-size:18px;}
    .review-empty{color:#777; padding:14px; border:1px dashed #ddd; border-radius:14px; background:#fff;}
    .review-list{list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:12px;}
    .review-item{border:1px solid #eee; border-radius:14px; padding:14px; background:#fff;}
    .review-top{display:flex; justify-content:space-between; align-items:flex-start; gap:10px; margin-bottom:8px;}
    .review-meta{display:flex; gap:10px; color:#777; font-size:13px; flex-wrap:wrap; align-items:center;}
    .review-actions{display:flex; gap:8px; align-items:center;}
    .review-content{white-space:pre-wrap; color:#222; font-size:14px; line-height:1.6;}

    .form{background:#fff; border:1px solid #eee; border-radius:16px; padding:14px;}
    .row{display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:10px;}
    select, textarea{width:100%; border:1px solid #ddd; border-radius:12px; padding:10px; font-size:14px;}
    textarea{min-height:90px; resize:vertical;}
    .form .row .left{flex:1; min-width:220px;}
    .form .row .right{display:flex; gap:10px; align-items:center;}
    .note{font-size:13px; color:#666; margin-top:8px;}
  </style>
</head>
<body>
  <div class="wrap">

    <div class="card product">
      <div class="imgbox">
        <?php if ($productImage): ?>
          <img src="<?= h('/electronics_shop/images/' . $productImage) ?>" alt="<?= h($productName) ?>">
        <?php else: ?>
          <div class="muted">이미지 없음</div>
        <?php endif; ?>
      </div>

      <div>
        <h1 class="p-title"><?= h($productName) ?></h1>

        <div class="rating-summary">
          <div class="stars">
            <?= renderStars($avgRating) ?>
            <span class="avg-num"><?= number_format($avgRating, 1) ?></span>
          </div>
          <div class="muted">리뷰 <?= $reviewCount ?>개</div>
        </div>

        <div class="price"><?= number_format($productPrice) ?>원</div>

        <?php if ($productDesc): ?>
          <div class="desc"><?= h($productDesc) ?></div>
        <?php endif; ?>

        <div class="actions">
          <a class="btn" href="/electronics_shop/index.php">목록으로</a>

          <!-- ✅ 어떤 장바구니 구현이든 통과하도록 id + product_id 둘 다 전달 -->
            <form method="post" action="/electronics_shop/cart/add_to_cart.php" style="display:inline;">
                <input type="hidden" name="product_id" value="<?= (int)$product_id ?>">
                <input type="hidden" name="quantity" value="1">
                <button type="submit" class="btn primary">장바구니 담기</button>
            </form>

          <a class="btn" href="/electronics_shop/wishlist/wishlist_add.php?product_id=<?= (int)$product_id ?>">
            ❤️ 찜하기
          </a>

          <a class="btn" href="/electronics_shop/qna/qna_list.php">
            Q&amp;A 게시판
          </a>
        </div>
      </div>
    </div>

    <div class="section">
      <h3>리뷰</h3>

      <?php if (empty($reviews)): ?>
        <div class="review-empty">아직 리뷰가 없습니다. 첫 리뷰를 남겨보세요!</div>
      <?php else: ?>
        <ul class="review-list">
          <?php foreach ($reviews as $r): ?>
            <li class="review-item">
              <div class="review-top">
                <div>
                  <div class="review-stars"><?= renderStars((float)$r['rating']) ?></div>
                  <div class="review-meta" style="margin-top:6px;">
                    <span><?= h($r['writer_name'] ?? ('유저#'.(int)$r['user_id'])) ?></span>
                    <span><?= h(date('Y-m-d H:i', strtotime($r['created_at']))) ?></span>
                  </div>
                </div>

                <?php if ($loginUserId && (int)$loginUserId === (int)$r['user_id']): ?>
                  <div class="review-actions">
                    <a class="btn" style="padding:6px 10px; border-radius:10px; text-decoration:none;"
                       href="/electronics_shop/review/review_edit.php?review_id=<?= (int)$r['id'] ?>&product_id=<?= (int)$product_id ?>">
                      수정
                    </a>

                    <form action="/electronics_shop/review/review_delete.php" method="post" style="margin:0;"
                          onsubmit="return confirm('이 리뷰를 삭제할까요?');">
                      <input type="hidden" name="review_id" value="<?= (int)$r['id'] ?>">
                      <input type="hidden" name="product_id" value="<?= (int)$product_id ?>">
                      <button type="submit" class="btn danger" style="padding:6px 10px; border-radius:10px;">삭제</button>
                    </form>
                  </div>
                <?php endif; ?>
              </div>

              <div class="review-content"><?= h($r['comment']) ?></div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>

    <div class="section">
      <h3>리뷰 작성</h3>

      <?php if (!$loginUserId): ?>
        <div class="review-empty">리뷰 작성은 로그인 후 가능합니다.</div>
      <?php else: ?>
        <!-- ✅ 항상 신규 작성(빈칸) -->
        <form class="form" action="/electronics_shop/review/review_add.php" method="post">
          <input type="hidden" name="product_id" value="<?= (int)$product_id ?>">

          <div class="row">
            <div class="left">
              <div class="muted">작성자: <?= h($loginDisplayName ?? $loginUsername ?? ('user#' . $loginUserId)) ?></div>
            </div>
            <div class="right">
              <label class="muted" for="rating">★</label>
              <select name="rating" id="rating" required>
                <?php for ($i=5; $i>=1; $i--): ?>
                  <option value="<?= $i ?>"><?= $i ?>점</option>
                <?php endfor; ?>
              </select>
            </div>
          </div>

          <textarea name="comment" placeholder="리뷰 내용을 입력하세요" required></textarea>

          <div class="row" style="margin-top:10px; justify-content:flex-end;">
            <button type="submit" class="btn primary">리뷰 등록</button>
          </div>

          <div class="note">
            * 한 사람이 같은 상품에 여러 리뷰를 작성할 수 있습니다. (수정은 각 리뷰의 ‘수정’ 버튼)
          </div>
        </form>
      <?php endif; ?>
    </div>

  </div>
</body>
</html>