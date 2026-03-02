<?php
session_start();
require_once __DIR__ . "/../DB/db.php";
require_once __DIR__ . "/../config_portone_v1.php";

/**
 * 제출/시연용 옵션
 * true  = 실패 시 API 응답/디버그 출력
 * false = 사용자 친화 메시지만 출력
 */
define('APP_DEBUG', false);

function render_fail(string $message, $debug = null, int $httpCode = 400): void {
    http_response_code($httpCode);
    echo "<h2>결제 취소 실패</h2>";
    echo "<p>" . htmlspecialchars($message) . "</p>";

    if (APP_DEBUG && $debug !== null) {
        echo "<hr><pre style='white-space:pre-wrap;word-break:break-word;'>";
        print_r($debug);
        echo "</pre>";
    }

    echo "<p><a href='../order/my_orders.php'>주문내역으로</a></p>";
    exit;
}

function render_ok(string $merchant_uid): void {
    echo "<h2>취소 성공</h2>";
    echo "<p>주문번호: <b>" . htmlspecialchars($merchant_uid) . "</b></p>";
    echo "<p><a href='../order/my_orders.php'>주문내역으로</a></p>";
    exit;
}

// 로그인 체크
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id  = (int)$_SESSION['user_id'];
$order_id = (int)($_POST['order_id'] ?? 0);
if ($order_id <= 0) {
    render_fail("잘못된 요청입니다. (order_id 없음)");
}

// 1) 주문 조회 (본인 주문만)
$stmt = $conn->prepare("SELECT id, user_id, status, pay_status, pay_amount, merchant_uid, imp_uid
                        FROM orders
                        WHERE id=? AND user_id=?
                        LIMIT 1");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    render_fail("주문을 찾을 수 없습니다.");
}

// 상태 체크: pay_status 우선, 없으면 status로 fallback
$pay_state = (string)($order['pay_status'] ?? $order['status'] ?? '');
if ($pay_state !== 'paid') {
    render_fail("결제완료 상태가 아니라 취소할 수 없습니다.");
}

if (empty($order['imp_uid'])) {
    render_fail("imp_uid가 없어 취소할 수 없습니다. (결제 검증 시 DB 저장 필요)");
}

// (선택) 테스트 결제(100원)만 취소 허용하고 싶으면 주석 해제
// if ((int)($order['pay_amount'] ?? 0) !== 100) {
//     render_fail("테스트 결제(100원)만 취소할 수 있도록 제한되어 있습니다.");
// }

// 2) 토큰 발급
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
$token_err = curl_error($ch);
curl_close($ch);

if ($token_res === false) {
    render_fail("토큰 발급 중 네트워크 오류가 발생했습니다.", ["curl_error"=>$token_err], 500);
}

$token_json = json_decode($token_res, true);
if (!is_array($token_json) || ($token_json['code'] ?? -1) != 0) {
    render_fail("토큰 발급 실패", ["http"=>$token_http, "raw"=>$token_res, "json"=>$token_json], 500);
}

$access_token = $token_json['response']['access_token'] ?? '';
if ($access_token === '') {
    render_fail("토큰 발급 실패(access_token 없음)", $token_json, 500);
}

// 3) 결제 취소 요청 (전체취소: amount 넣지 않음)
$cancel_url = "https://api.iamport.kr/payments/cancel";
$reason = "사용자 요청 즉시취소";

$cancel_payload = json_encode([
    "imp_uid" => $order['imp_uid'],
    "reason"  => $reason
], JSON_UNESCAPED_UNICODE);

$ch = curl_init($cancel_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: {$access_token}"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $cancel_payload);

$cancel_res = curl_exec($ch);
$cancel_http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$cancel_err = curl_error($ch);
curl_close($ch);

if ($cancel_res === false) {
    render_fail("결제 취소 요청 중 네트워크 오류가 발생했습니다.", ["curl_error"=>$cancel_err], 500);
}

$cancel_json = json_decode($cancel_res, true);
if (!is_array($cancel_json) || ($cancel_json['code'] ?? -1) != 0) {
    render_fail("결제 취소 실패", ["http"=>$cancel_http, "raw"=>$cancel_res, "json"=>$cancel_json], 500);
}

// 4) DB 업데이트
$stmt = $conn->prepare("
    UPDATE orders
       SET status='canceled',
           pay_status='canceled',
           canceled_at=NOW(),
           cancel_reason=?
     WHERE id=? AND user_id=?
");
$stmt->bind_param("sii", $reason, $order['id'], $user_id);
$stmt->execute();
$stmt->close();

render_ok($order['merchant_uid']);