<?php
session_start();
require_once __DIR__ . "/../DB/db.php";

if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($order_id <= 0) die("잘못된 주문번호");

$stmt = $conn->prepare("SELECT id, user_id, display_total, pay_amount, merchant_uid FROM orders WHERE id=? AND user_id=?");
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) die("주문을 찾을 수 없습니다.");

$merchant_uid = $order['merchant_uid'];
$display_total = (int)$order['display_total'];
$pay_amount = (int)$order['pay_amount'];

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8" />
  <title>결제 진행</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{margin:0; font-family:Arial,sans-serif; background:#f6f7fb; color:#111;}
    .wrap{max-width:720px; margin:24px auto; padding:0 16px;}
    .card{background:#fff; border:1px solid #eee; border-radius:16px; padding:18px; box-shadow:0 6px 20px rgba(0,0,0,.04);}
    .top{display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;}
    h1{margin:0; font-size:24px;}
    .muted{color:#666; font-size:13px;}
    .row{margin-top:12px; display:grid; gap:10px;}
    .box{background:#fafafa; border:1px solid #eee; border-radius:14px; padding:12px 14px;}
    .box strong{font-size:16px;}
    .btns{margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;}
    .btn{display:inline-flex; align-items:center; justify-content:center; padding:10px 14px; border-radius:12px; border:1px solid #ddd; background:#fff; cursor:pointer; text-decoration:none; color:#111; font-weight:800;}
    .btn.primary{background:#111; color:#fff; border-color:#111;}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <div class="top">
        <div>
          <h1>결제 진행</h1>
          <div class="muted">카카오페이 결제를 진행합니다.</div>
        </div>
        <a class="btn" href="../order/checkout.php">← 주문서로</a>
      </div>

      <div class="row">
        <div class="box">
          <div class="muted">주문번호</div>
          <strong><?= h($merchant_uid) ?></strong>
        </div>
        <div class="box">
          <div class="muted">표시 결제금액</div>
          <strong><?= number_format((int)$display_total) ?>원</strong>
        </div>
        <div class="box">
          <div class="muted">실제 결제금액(테스트)</div>
          <strong><?= number_format((int)$pay_amount) ?>원</strong>
        </div>
      </div>

      <div class="btns">
        <button id="payBtn" class="btn primary" type="button">카카오페이 결제하기</button>
        <button class="btn" type="button" onclick="location.href='../order/checkout.php'">결제 중단(주문서로)</button>
      </div>
    </div>
  </div>

  <script src="https://cdn.iamport.kr/v1/iamport.js"></script>
  <script>
  document.getElementById("payBtn").addEventListener("click", function () {
    if (typeof IMP === "undefined") {
      alert("결제 모듈(IMP) 로드 실패");
      return;
    }

    IMP.init("imp10721361");

    const merchant_uid = <?= json_encode($merchant_uid) ?>;
    const amount = <?= (int)$pay_amount ?>;

    IMP.request_pay({
      pg: "kakaopay.TC0ONETIME",
      pay_method: "card",
      merchant_uid: merchant_uid,
      name: "전자기기 쇼핑몰 주문",
      amount: amount,
      buyer_name: "테스트구매자",
      buyer_tel: "010-0000-0000",

      // ✅ 모바일 결제 필수 (SDK 1.1.8+)
      m_redirect_url: "https://<?= $_SERVER['HTTP_HOST'] ?>/electronics_shop/payment/payment_verify.php"
    }, function (rsp) {
      console.log("[PAY] rsp:", rsp);

      if (rsp.success) {
        location.href =
          "payment_verify.php?imp_uid=" + encodeURIComponent(rsp.imp_uid) +
          "&merchant_uid=" + encodeURIComponent(rsp.merchant_uid);
      } else {
        alert("결제 취소/실패: " + (rsp.error_msg || "알 수 없는 오류"));
        location.href = "../order/checkout.php";
      }
    });
  });
  </script>
</body>
</html>