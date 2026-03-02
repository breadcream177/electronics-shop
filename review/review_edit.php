<?php
// review/review_edit.php
session_start();
require_once __DIR__ . "/../DB/db.php";

if (!isset($_SESSION['user_id'])) {
  header("Location: /electronics_shop/login.php");
  exit;
}

$user_id    = (int)$_SESSION['user_id'];
$review_id  = (int)($_GET['review_id'] ?? 0);
$product_id = (int)($_GET['product_id'] ?? 0);

if ($review_id <= 0 || $product_id <= 0) {
  die("잘못된 접근");
}

// 내 리뷰만 수정 가능
$stmt = $conn->prepare("
  SELECT id, rating, comment
  FROM reviews
  WHERE id = ? AND user_id = ? AND product_id = ?
  LIMIT 1
");
$stmt->bind_param("iii", $review_id, $user_id, $product_id);
$stmt->execute();
$review = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$review) die("수정할 리뷰를 찾을 수 없습니다.");

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <title>리뷰 수정</title>
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial; background:#f6f7fb; margin:0;}
    .wrap{max-width:700px; margin:30px auto; background:#fff; border:1px solid #eee; border-radius:14px; padding:18px;}
    textarea, select{width:100%; padding:10px; border:1px solid #ddd; border-radius:10px; font-size:14px;}
    textarea{min-height:120px; resize:vertical;}
    .row{display:flex; justify-content:space-between; gap:10px; align-items:center; margin-bottom:10px;}
    .btn{padding:10px 14px; border-radius:10px; border:1px solid #111; background:#111; color:#fff; cursor:pointer;}
    .link{display:inline-block; padding:10px 14px; border-radius:10px; border:1px solid #ddd; text-decoration:none; color:#111;}
  </style>
</head>
<body>
  <div class="wrap">
    <h2>리뷰 수정</h2>

    <form method="post" action="/electronics_shop/review/review_add.php">
      <input type="hidden" name="product_id" value="<?= (int)$product_id ?>">
      <input type="hidden" name="review_id" value="<?= (int)$review['id'] ?>">

      <div class="row">
        <div>별점</div>
        <select name="rating" required>
          <?php for($i=5;$i>=1;$i--): ?>
            <option value="<?= $i ?>" <?= ($i==(int)$review['rating'])?'selected':''; ?>><?= $i ?>점</option>
          <?php endfor; ?>
        </select>
      </div>

      <textarea name="comment" required><?= h($review['comment']) ?></textarea>

      <div class="row" style="justify-content:flex-end; margin-top:12px;">
        <a class="link" href="/electronics_shop/product_detail.php?id=<?= (int)$product_id ?>">취소</a>
        <button class="btn" type="submit">수정 저장</button>
      </div>
    </form>
  </div>
</body>
</html>