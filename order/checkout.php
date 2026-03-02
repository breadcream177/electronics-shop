<?php
session_start();
require_once __DIR__ . "/../DB/db.php";

if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }
$user_id = (int)$_SESSION['user_id'];

// 미결제 주문 자동정리(기존 유지)
$conn->begin_transaction();
try {
  $sql = "SELECT id FROM orders
          WHERE user_id=?
            AND status='결제대기'
            AND (pay_status IS NULL OR pay_status!='paid')
            AND created_at < (NOW() - INTERVAL 2 HOUR)";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $rs = $stmt->get_result();
  $old_ids = [];
  while ($r = $rs->fetch_assoc()) $old_ids[] = (int)$r['id'];
  $stmt->close();

  if (count($old_ids) > 0) {
    foreach ($old_ids as $oid) {
      $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id=?");
      $stmt->bind_param("i", $oid);
      $stmt->execute();
      $stmt->close();

      $stmt = $conn->prepare("DELETE FROM orders WHERE id=? AND user_id=?");
      $stmt->bind_param("ii", $oid, $user_id);
      $stmt->execute();
      $stmt->close();
    }
  }
  $conn->commit();
} catch (Throwable $e) {
  $conn->rollback();
}

$sql = "SELECT c.product_id, c.quantity, p.name, p.price, p.image
        FROM cart c
        JOIN products p ON p.id = c.product_id
        WHERE c.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result();
$stmt->close();

$items = [];
$display_total = 0;
while ($row = $cart_items->fetch_assoc()) {
  $row['quantity'] = (int)$row['quantity'];
  $row['price'] = (int)$row['price'];
  $items[] = $row;
  $display_total += $row['price'] * $row['quantity'];
}

if (count($items) === 0) {
  die("장바구니가 비었습니다. <a href='../cart/cart.php'>장바구니로</a>");
}

$pay_amount = 100;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $receiver_name = trim($_POST['receiver_name'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $address = trim($_POST['address'] ?? '');
  $request = trim($_POST['request'] ?? '');

  if ($receiver_name === '' || $phone === '' || $address === '') {
    die("배송 정보(받는사람/연락처/주소)는 필수입니다.");
  }

  $new_merchant_uid = "ORDER_" . $user_id . "_" . time() . "_" . bin2hex(random_bytes(3));

  $conn->begin_transaction();
  try {
    $sql = "SELECT id FROM orders
            WHERE user_id=? AND status='결제대기' AND (pay_status IS NULL OR pay_status!='paid')
              AND created_at >= (NOW() - INTERVAL 1 HOUR)
            ORDER BY id DESC
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $reuse = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($reuse) {
      $order_id = (int)$reuse['id'];

      $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id=?");
      $stmt->bind_param("i", $order_id);
      $stmt->execute();
      $stmt->close();

      $sql = "UPDATE orders
              SET total_price=?,
                  display_total=?,
                  pay_amount=?,
                  merchant_uid=?,
                  receiver_name=?,
                  phone=?,
                  address=?,
                  request=?,
                  status='결제대기',
                  pay_status='ready',
                  imp_uid=NULL,
                  paid_at=NULL
              WHERE id=? AND user_id=?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param(
        "iiisssssii",
        $display_total,
        $display_total,
        $pay_amount,
        $new_merchant_uid,
        $receiver_name,
        $phone,
        $address,
        $request,
        $order_id,
        $user_id
      );
      $stmt->execute();
      $stmt->close();
    } else {
      $sql = "INSERT INTO orders
                (user_id, total_price, status, receiver_name, phone, address, request, display_total, pay_amount, merchant_uid, pay_status, created_at)
              VALUES
                (?, ?, '결제대기', ?, ?, ?, ?, ?, ?, ?, 'ready', NOW())";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param(
        "iissssiis",
        $user_id,
        $display_total,
        $receiver_name,
        $phone,
        $address,
        $request,
        $display_total,
        $pay_amount,
        $new_merchant_uid
      );
      $stmt->execute();
      $order_id = (int)$stmt->insert_id;
      $stmt->close();
    }

    $sql = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    foreach ($items as $it) {
      $pid = (int)$it['product_id'];
      $qty = (int)$it['quantity'];
      $price = (int)$it['price'];
      $stmt->bind_param("iiii", $order_id, $pid, $qty, $price);
      $stmt->execute();
    }
    $stmt->close();

    $conn->commit();
    header("Location: ../Payment/payment.php?order_id=" . $order_id);
    exit;

  } catch (Throwable $e) {
    $conn->rollback();
    die("주문 생성/갱신 실패: " . htmlspecialchars($e->getMessage()));
  }
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8" />
  <title>주문서 작성</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{margin:0; font-family:Arial,sans-serif; background:#f6f7fb; color:#111;}
    .wrap{max-width:980px; margin:24px auto; padding:0 16px;}
    .card{background:#fff; border:1px solid #eee; border-radius:16px; padding:18px; box-shadow:0 6px 20px rgba(0,0,0,.04);}
    .top{display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;}
    h1{margin:0; font-size:24px;}
    .btn{display:inline-flex; align-items:center; justify-content:center; padding:9px 12px; border-radius:10px; border:1px solid #ddd; background:#fff; color:#111; text-decoration:none; cursor:pointer; font-weight:700;}
    .btn.primary{background:#111; color:#fff; border-color:#111;}
    .muted{color:#666; font-size:13px;}
    .grid{display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:14px;}
    .grid .full{grid-column:1 / -1;}
    label{display:block; font-size:13px; color:#333; margin-bottom:6px;}
    input{width:100%; padding:11px 12px; border:1px solid #ddd; border-radius:12px; font-size:14px; outline:none;}
    input:focus{border-color:#111;}
    table{width:100%; border-collapse:collapse; margin-top:14px;}
    th,td{padding:12px 10px; border-bottom:1px solid #eee; text-align:left; vertical-align:middle;}
    th{background:#fafafa; font-size:13px;}
    .pname{display:flex; align-items:center; gap:10px;}
    .thumb{width:50px; height:50px; border-radius:10px; border:1px solid #eee; background:#fafafa; overflow:hidden; display:flex; align-items:center; justify-content:center;}
    .thumb img{width:100%; height:100%; object-fit:cover;}
    .sum{margin-top:14px; display:flex; justify-content:space-between; align-items:flex-start; gap:10px; flex-wrap:wrap;}
    .sum .box{background:#fafafa; border:1px solid #eee; border-radius:14px; padding:12px 14px;}
    .sum .box strong{font-size:16px;}
    .actions{margin-top:14px; display:flex; justify-content:flex-end; gap:10px; flex-wrap:wrap;}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <div class="top">
        <div>
          <h1>주문서 작성</h1>
          <div class="muted">배송 정보를 입력하고 결제를 진행하세요.</div>
        </div>
        <a class="btn" href="../cart/cart.php">← 장바구니로</a>
      </div>

      <form method="post" style="margin-top:14px;">
        <h3 style="margin:10px 0;">배송 정보</h3>

        <div class="grid">
          <div>
            <label>받는사람</label>
            <input name="receiver_name" required value="<?= h($_POST['receiver_name'] ?? '') ?>">
          </div>
          <div>
            <label>연락처</label>
            <input name="phone" required value="<?= h($_POST['phone'] ?? '010-0000-0000') ?>">
          </div>
          <div class="full">
            <label>주소</label>
            <input name="address" required value="<?= h($_POST['address'] ?? '') ?>">
          </div>
          <div class="full">
            <label>요청사항</label>
            <input name="request" value="<?= h($_POST['request'] ?? '') ?>">
          </div>
        </div>

        <h3 style="margin:18px 0 10px;">주문 상품</h3>

        <table>
          <thead>
            <tr><th>상품</th><th style="width:140px;">가격</th><th style="width:80px;">수량</th><th style="width:160px;">소계</th></tr>
          </thead>
          <tbody>
            <?php foreach ($items as $it): ?>
              <tr>
                <td>
                  <div class="pname">
                    <div class="thumb">
                      <?php if (!empty($it['image'])): ?>
                        <img src="../images/<?= h($it['image']) ?>" alt="<?= h($it['name']) ?>">
                      <?php else: ?>
                        <span class="muted">No</span>
                      <?php endif; ?>
                    </div>
                    <div><?= h($it['name']) ?></div>
                  </div>
                </td>
                <td><?= number_format((int)$it['price']) ?>원</td>
                <td><?= (int)$it['quantity'] ?></td>
                <td><strong><?= number_format((int)$it['price'] * (int)$it['quantity']) ?>원</strong></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <div class="sum">
          <div class="box">
            <div class="muted">표시 총 합계</div>
            <strong><?= number_format((int)$display_total) ?>원</strong>
          </div>
          <div class="box">
            <div class="muted">실제 결제 금액(테스트)</div>
            <strong><?= number_format((int)$pay_amount) ?>원</strong>
          </div>
        </div>

        <div class="actions">
          <button class="btn primary" type="submit">결제하기</button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>