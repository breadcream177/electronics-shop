<?php
session_start();
require_once __DIR__ . "/../DB/db.php";

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$user_id = (int)$_SESSION['user_id'];

$receiver = trim($_POST['receiver_name'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$address  = trim($_POST['address'] ?? '');
$request  = trim($_POST['request'] ?? '');

$display_total = (int)($_POST['display_total'] ?? 0);
$pay_amount    = (int)($_POST['pay_amount'] ?? 0); // 100원

if ($display_total <= 0 || $pay_amount <= 0) die("잘못된 주문입니다.");

// 1) 장바구니 다시 조회 + 표시총액 검증
$sql = "SELECT c.product_id, c.quantity, p.price
        FROM cart c JOIN products p ON c.product_id=p.id
        WHERE c.user_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

$items = [];
$total_check = 0;
while ($row = $res->fetch_assoc()) {
    $total_check += $row['price'] * $row['quantity'];
    $items[] = $row;
}
$stmt->close();

if (count($items) === 0) die("장바구니가 비어있습니다.");
if ($total_check !== $display_total) die("금액 검증 실패");

// 2) merchant_uid 생성 (고유)
$merchant_uid = "ORDER_" . $user_id . "_" . time();

// 3) orders 생성 (기존 컬럼 total_price/status 유지 가정)
$sql = "INSERT INTO orders
        (user_id, total_price, status, created_at,
         receiver_name, phone, address, request,
         display_total, pay_amount, merchant_uid, pay_status)
        VALUES (?, ?, '결제대기', NOW(),
                ?, ?, ?, ?,
                ?, ?, ?, 'ready')";
$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "iissssiis",
    $user_id, $display_total,
    $receiver, $phone, $address, $request,
    $display_total, $pay_amount, $merchant_uid
);
$stmt->execute();
$order_id = $stmt->insert_id;
$stmt->close();

// 4) order_items 생성
$sql = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
foreach ($items as $it) {
    $stmt->bind_param("iiii", $order_id, $it['product_id'], $it['quantity'], $it['price']);
    $stmt->execute();
}
$stmt->close();

// 5) cart 비우기
$sql = "DELETE FROM cart WHERE user_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();

// 6) 결제 페이지로 이동 (100원 결제)
header("Location: ../Payment/payment.php?order_id=".$order_id);
exit;