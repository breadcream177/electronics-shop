<?php
session_start();
require_once __DIR__ . "/../DB/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: /electronics_shop/login.php");
    exit;
}
$user_id = (int)$_SESSION['user_id'];

$sql = "
SELECT 
    c.id AS cart_id,
    c.quantity,
    p.id AS product_id,
    p.name,
    p.price,
    p.image
FROM cart c
JOIN products p ON c.product_id = p.id
WHERE c.user_id = ?
ORDER BY c.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$total = 0;
$items = [];
while ($row = $result->fetch_assoc()) {
    $row['subtotal'] = (int)$row['price'] * (int)$row['quantity'];
    $total += $row['subtotal'];
    $items[] = $row;
}
$stmt->close();

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <title>장바구니</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{margin:0; font-family:Arial,sans-serif; background:#f6f7fb; color:#111;}
    .wrap{max-width:980px; margin:24px auto; padding:0 16px;}
    .card{background:#fff; border:1px solid #eee; border-radius:16px; padding:18px; box-shadow:0 6px 20px rgba(0,0,0,.04);}
    .top{display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;}
    h1{margin:0; font-size:24px;}
    .btn{display:inline-flex; align-items:center; justify-content:center; padding:9px 12px; border-radius:10px; border:1px solid #ddd; background:#fff; color:#111; text-decoration:none; cursor:pointer; font-weight:700;}
    .btn.primary{background:#111; color:#fff; border-color:#111;}
    .btn.danger{background:#fff; color:#c0392b; border-color:#f1c4bf;}
    .muted{color:#666; font-size:13px;}
    table{width:100%; border-collapse:collapse; margin-top:14px;}
    th,td{padding:12px 10px; border-bottom:1px solid #eee; text-align:left; vertical-align:middle;}
    th{background:#fafafa; font-size:13px; color:#333;}
    .pname{display:flex; align-items:center; gap:10px;}
    .thumb{width:56px; height:56px; border-radius:10px; border:1px solid #eee; background:#fafafa; overflow:hidden; display:flex; align-items:center; justify-content:center;}
    .thumb img{width:100%; height:100%; object-fit:cover;}
    .qtybox{display:flex; gap:8px; align-items:center; flex-wrap:wrap;}
    .qtybox input{width:70px; padding:8px 10px; border:1px solid #ddd; border-radius:10px;}
    .sum{margin-top:14px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;}
    .sum strong{font-size:18px;}
    .actions{margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;}
    form{margin:0;}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <div class="top">
        <div>
          <h1>장바구니</h1>
          <div class="muted">상품 수량을 변경하거나 주문을 진행할 수 있습니다.</div>
        </div>
        <a class="btn" href="/electronics_shop/index.php">← 쇼핑 계속하기</a>
      </div>

      <?php if (count($items) === 0): ?>
        <div style="margin-top:14px; padding:14px; border:1px dashed #ddd; border-radius:14px; color:#777;">
          장바구니가 비어있습니다.
        </div>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>상품</th>
              <th style="width:140px;">가격</th>
              <th style="width:220px;">수량</th>
              <th style="width:160px;">소계</th>
              <th style="width:120px;">삭제</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($items as $it): ?>
            <tr>
              <td>
                <div class="pname">
                  <div class="thumb">
                    <?php if (!empty($it['image'])): ?>
                      <img src="/electronics_shop/images/<?= h($it['image']) ?>" alt="<?= h($it['name']) ?>">
                    <?php else: ?>
                      <span class="muted">No</span>
                    <?php endif; ?>
                  </div>
                  <div><?= h($it['name']) ?></div>
                </div>
              </td>
              <td><?= number_format((int)$it['price']) ?>원</td>
              <td>
                <form method="POST" action="update_cart.php" class="qtybox">
                  <input type="hidden" name="cart_id" value="<?= (int)$it['cart_id'] ?>">
                  <input type="number" name="quantity" value="<?= (int)$it['quantity'] ?>" min="1" max="99">
                  <button class="btn" type="submit">변경</button>
                </form>
              </td>
              <td><strong><?= number_format((int)$it['subtotal']) ?>원</strong></td>
              <td>
                <form method="POST" action="remove_from_cart.php" onsubmit="return confirm('이 상품을 장바구니에서 삭제할까요?');">
                  <input type="hidden" name="cart_id" value="<?= (int)$it['cart_id'] ?>">
                  <button class="btn danger" type="submit">삭제</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>

        <div class="sum">
          <strong>총 합계: <?= number_format((int)$total) ?>원</strong>
          <div class="actions">
            <form method="GET" action="../order/checkout.php">
              <button class="btn primary" type="submit">주문하기</button>
            </form>
            <form method="POST" action="clear_cart.php" onsubmit="return confirm('장바구니를 전체 비우시겠습니까?');">
              <button class="btn" type="submit">전체 비우기</button>
            </form>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>