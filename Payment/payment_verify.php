<?php
session_start();
require_once __DIR__ . "/../DB/db.php";
require_once __DIR__ . "/../config_portone_v1.php";

/**
 * 제출/시연용 설정
 * - true  : 실패 시 raw 응답 등 디버그 출력
 * - false : 사용자 친화적 메시지만 출력
 */
define('APP_DEBUG', false);

$imp_uid = $_GET['imp_uid'] ?? '';
$merchant_uid = $_GET['merchant_uid'] ?? '';

if ($imp_uid === '' || $merchant_uid === '') {
  http_response_code(400);
  exit("잘못된 요청입니다. (imp_uid/merchant_uid 없음)");
}

function die_with_error(string $title, $debugData = null): void {
  ?>
  <!doctype html>
  <html lang="ko">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>결제 실패</title>
    <style>
      body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:#f6f7fb;margin:0}
      .wrap{max-width:720px;margin:48px auto;padding:0 16px}
      .card{background:#fff;border:1px solid #e7e8ee;border-radius:14px;padding:22px;box-shadow:0 6px 18px rgba(0,0,0,.06)}
      .title{margin:0 0 10px;font-size:22px}
      .desc{margin:0 0 16px;color:#444;line-height:1.5}
      .btns{display:flex;gap:10px;flex-wrap:wrap;margin-top:16px}
      .btn{display:inline-block;padding:10px 14px;border-radius:10px;text-decoration:none;border:1px solid #d7d9e2;background:#fff;color:#111}
      .btn.primary{background:#111;color:#fff;border-color:#111}
      pre{background:#0b1020;color:#e6e6e6;padding:12px;border-radius:10px;overflow:auto;white-space:pre-wrap;word-break:break-word}
      .small{color:#666;font-size:13px}
    </style>
  </head>
  <body>
    <div class="wrap">
      <div class="card">
        <h1 class="title">결제 처리 실패</h1>
        <p class="desc"><?php echo htmlspecialchars($title); ?></p>

        <?php if (APP_DEBUG && $debugData !== null): ?>
          <p class="small">디버그 정보(제출/시연 시에는 APP_DEBUG=false 권장)</p>
          <pre><?php print_r($debugData); ?></pre>
        <?php endif; ?>

        <div class="btns">
          <a class="btn primary" href="../order/checkout.php">주문서로 돌아가기</a>
          <a class="btn" href="../index.php">메인으로 이동</a>
        </div>
      </div>
    </div>
  </body>
  </html>
  <?php
  exit;
}

/** 1) 토큰 발급 */
$token_url = "https://api.iamport.kr/users/getToken";
$token_payload = json_encode([
  "imp_key" => $PORTONE_V1_REST_KEY,
  "imp_secret" => $PORTONE_V1_REST_SECRET
], JSON_UNESCAPED_UNICODE);

$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $token_payload);

$token_res = curl_exec($ch);
$token_http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$token_json = json_decode($token_res, true);

if (!is_array($token_json) || ($token_json['code'] ?? -1) != 0) {
  die_with_error("토큰 발급에 실패했습니다.", ["http" => $token_http, "raw" => $token_res, "json" => $token_json]);
}

$access_token = $token_json['response']['access_token'] ?? '';
if ($access_token === '') {
  die_with_error("토큰 발급에 실패했습니다. (access_token 없음)", $token_json);
}

/** 2) 결제 조회 (merchant_uid 기준) */
$pay_url = "https://api.iamport.kr/payments/find/" . urlencode($merchant_uid);

$ch = curl_init($pay_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: {$access_token}"]);

$pay_res = curl_exec($ch);
$pay_http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$pay_json = json_decode($pay_res, true);
if (!is_array($pay_json) || ($pay_json['code'] ?? -1) != 0) {
  die_with_error("결제 조회에 실패했습니다.", ["http" => $pay_http, "raw" => $pay_res, "json" => $pay_json]);
}

$payment = $pay_json['response'] ?? null;
if (!$payment) {
  die_with_error("결제 조회에 실패했습니다. (response 없음)", $pay_json);
}

/** 3) DB 주문 조회 */
$stmt = $conn->prepare("SELECT * FROM orders WHERE merchant_uid=? LIMIT 1");
$stmt->bind_param("s", $merchant_uid);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
  die_with_error("DB에서 주문을 찾을 수 없습니다.", ["merchant_uid" => $merchant_uid]);
}

/** 4) 금액/상태 검증 */
$paid_amount = (int)($payment['amount'] ?? 0);
$paid_status = (string)($payment['status'] ?? '');
$db_amount   = (int)($order['pay_amount'] ?? 0);
$api_merchant_uid = (string)($payment['merchant_uid'] ?? '');

if ($api_merchant_uid !== $merchant_uid) {
  die_with_error("결제 정보가 주문 정보와 일치하지 않습니다.(merchant_uid 불일치)", [
    "db" => $merchant_uid, "api" => $api_merchant_uid, "payment" => $payment
  ]);
}

if ($paid_amount !== $db_amount) {
  die_with_error("결제 금액이 주문 금액과 일치하지 않습니다.", [
    "db_amount" => $db_amount, "paid_amount" => $paid_amount, "payment" => $payment
  ]);
}

if ($paid_status !== 'paid') {
  die_with_error("결제가 아직 완료 상태가 아닙니다.", ["status" => $paid_status, "payment" => $payment]);
}

/** 5) 결제 성공 처리 (트랜잭션) */
$api_imp_uid = (string)($payment['imp_uid'] ?? $imp_uid);

// 이미 처리된 결제(새로고침/재진입) 방지
$is_already_paid = (($order['pay_status'] ?? '') === 'paid' || ($order['status'] ?? '') === 'paid');

$conn->begin_transaction();
try {
  if (!$is_already_paid) {
    // 주문 상태 업데이트
    $stmt = $conn->prepare("
      UPDATE orders
         SET status='paid',
             pay_status='paid',
             imp_uid=?,
             paid_at=NOW()
       WHERE id=?
    ");
    $stmt->bind_param("si", $api_imp_uid, $order['id']);
    $stmt->execute();
    $stmt->close();

    // 장바구니 비우기
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id=?");
    $stmt->bind_param("i", $order['user_id']);
    $stmt->execute();
    $stmt->close();
  }

  $conn->commit();
} catch (Throwable $e) {
  $conn->rollback();
  die_with_error("DB 처리 중 오류가 발생했습니다.", $e->getMessage());
}

/** 화면 표시용 값 */
$display_total = (int)($order['display_total'] ?? $paid_amount);
$pay_method = (string)($payment['pay_method'] ?? 'kakaopay');
$pg_provider = (string)($payment['pg_provider'] ?? '');
$paid_at_api = (int)($payment['paid_at'] ?? 0);
$paid_at_str = $paid_at_api > 0 ? date('Y-m-d H:i:s', $paid_at_api) : '';
?>
<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>결제 성공</title>
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:#f6f7fb;margin:0}
    .wrap{max-width:720px;margin:48px auto;padding:0 16px}
    .card{background:#fff;border:1px solid #e7e8ee;border-radius:14px;padding:22px;box-shadow:0 6px 18px rgba(0,0,0,.06)}
    .title{margin:10px 0 10px;font-size:24px}
    .badge{display:inline-block;padding:6px 10px;border-radius:999px;background:#111;color:#fff;font-size:12px}
    .sub{color:#666;font-weight:500;margin:0 0 6px}
    .grid{display:grid;grid-template-columns:160px 1fr;gap:10px;margin-top:16px}
    .label{color:#666}
    .value{color:#111;font-weight:600}
    .btns{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px}
    .btn{display:inline-block;padding:10px 14px;border-radius:10px;text-decoration:none;border:1px solid #d7d9e2;background:#fff;color:#111}
    .btn.primary{background:#111;color:#fff;border-color:#111}
    .warn{margin-top:10px;color:#666;font-size:13px}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <div class="badge">결제 완료</div>
      <h1 class="title">결제가 정상 처리되었습니다.</h1>
      <p class="sub">주문이 접수되었으며 주문내역에서 확인할 수 있습니다.</p>

      <div class="grid" role="table" aria-label="결제 정보">
        <div class="label">주문번호</div>
        <div class="value"><?php echo htmlspecialchars($merchant_uid); ?></div>

        <div class="label">표시 결제금액</div>
        <div class="value"><?php echo number_format($display_total); ?>원</div>

        <div class="label">실제 결제검증금액</div>
        <div class="value"><?php echo number_format($paid_amount); ?>원</div>

        <div class="label">결제수단</div>
        <div class="value">
          <?php
            echo htmlspecialchars($pay_method);
            if ($pg_provider !== '') echo " <span style='color:#666;font-weight:500'>(" . htmlspecialchars($pg_provider) . ")</span>";
          ?>
        </div>

        <div class="label">결제일시</div>
        <div class="value">
          <?php echo $paid_at_str !== '' ? htmlspecialchars($paid_at_str) : '확인됨'; ?>
        </div>
      </div>

      <?php if (($order['pay_status'] ?? '') === 'paid' || ($order['status'] ?? '') === 'paid'): ?>
        <p class="warn">※ 새로고침 등으로 결제 검증 페이지가 다시 열려도 중복 처리되지 않습니다.</p>
      <?php endif; ?>

      <div class="btns">
        <a class="btn primary" href="../order/my_orders.php">주문내역 보기</a>
        <a class="btn" href="../index.php">메인으로 이동</a>
      </div>
    </div>
  </div>
</body>
</html>