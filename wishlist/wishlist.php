<?php
session_start();
require_once __DIR__ . "/../DB/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: /electronics_shop/login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

/**
 * ✅ (옵션) "담고 찜에서 제거" 처리
 * - add_to_cart.php로 보내기 전에 여기서 처리하면 안 됨 (장바구니 로직이 거기 있음)
 * - 대신: 장바구니 담기 성공 후에 remove까지 하려면 add_to_cart.php에서 처리하는 방식도 가능
 * - 지금은 wishlist.php에서 "move=1" 요청을 받으면 remove만 먼저 하는 게 아니라,
 *   "담고 찜에서 제거" 버튼은 add_to_cart.php가 실행된 뒤 remove 되도록 하는 게 자연스럽다.
 *
 * 따라서, 여기서는 move 기능을 add_to_cart.php가 처리하도록 맡기는 쪽이 더 확실함.
 * (아래 2번에서 add_to_cart.php도 함께 수정해줄 거야)
 */

$sql = "SELECT p.id, p.name, p.price, p.image
        FROM wishlist w
        JOIN products p ON w.product_id = p.id
        WHERE w.user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<title>찜 목록</title>
<style>
  body{font-family:Arial,sans-serif; background:#f6f7fb; margin:0;}
  .wrap{max-width:980px; margin:24px auto; background:#fff; border:1px solid #eee; border-radius:16px; padding:18px;}
  .top{display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;}
  .btn{display:inline-flex; align-items:center; justify-content:center; padding:9px 12px; border-radius:10px; border:1px solid #ddd; background:#fff; color:#111; text-decoration:none; cursor:pointer; font-weight:600;}
  .btn.primary{background:#111; color:#fff; border-color:#111;}
  .btn.danger{background:#fff; color:#c0392b; border-color:#f1c4bf;}
  .list{display:grid; grid-template-columns:repeat(auto-fill, minmax(240px, 1fr)); gap:14px; margin-top:14px;}
  .item{border:1px solid #eee; border-radius:14px; padding:14px; background:#fff;}
  .thumb{width:100%; height:160px; border-radius:12px; border:1px solid #eee; background:#fafafa; overflow:hidden; display:flex; align-items:center; justify-content:center;}
  .thumb img{width:100%; height:100%; object-fit:cover;}
  .name{margin:10px 0 6px; font-size:16px;}
  .price{margin:0 0 10px; color:#111; font-weight:700;}
  .actions{display:flex; gap:8px; flex-wrap:wrap;}
  form{margin:0;}
</style>
</head>
<body>
  <div class="wrap">
    <div class="top">
      <h1 style="margin:0;">찜 목록</h1>
      <a class="btn" href="/electronics_shop/index.php">← 쇼핑 계속하기</a>
    </div>
    <hr style="border:0;border-top:1px solid #eee;margin:14px 0;">

    <?php if ($result->num_rows === 0): ?>
      <p>찜한 상품이 없습니다.</p>
    <?php else: ?>
      <div class="list">
        <?php while($row = $result->fetch_assoc()): ?>
          <div class="item">
            <div class="thumb">
              <?php if (!empty($row['image'])): ?>
                <img src="/electronics_shop/images/<?= h($row['image']) ?>" alt="<?= h($row['name']) ?>">
              <?php else: ?>
                <span style="color:#777;">이미지 없음</span>
              <?php endif; ?>
            </div>

            <h3 class="name"><?= h($row['name']) ?></h3>
            <p class="price"><?= number_format((int)$row['price']) ?>원</p>

            <div class="actions">
              <!-- ✅ 장바구니 담기 -->
              <form method="POST" action="/electronics_shop/cart/add_to_cart.php">
                <input type="hidden" name="product_id" value="<?= (int)$row['id'] ?>">
                <input type="hidden" name="quantity" value="1">
                <button class="btn primary" type="submit">🛒 장바구니 담기</button>
              </form>

              <!-- ✅ 담고 찜에서 제거(옵션) -->
              <form method="POST" action="/electronics_shop/cart/add_to_cart.php">
                <input type="hidden" name="product_id" value="<?= (int)$row['id'] ?>">
                <input type="hidden" name="quantity" value="1">
                <input type="hidden" name="move_from_wishlist" value="1">
                <button class="btn" type="submit">🛒 담고 찜해제</button>
              </form>

              <!-- ✅ 찜 삭제 -->
              <form method="POST" action="/electronics_shop/wishlist/wishlist_remove.php"
                    onsubmit="return confirm('찜 목록에서 삭제할까요?');">
                <input type="hidden" name="product_id" value="<?= (int)$row['id'] ?>">
                <button class="btn danger" type="submit">❌ 삭제</button>
              </form>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>