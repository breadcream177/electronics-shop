<?php
session_start();
require_once __DIR__ . "/../DB/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$IMG_BASE = "../images/";
$user_id = (int)$_SESSION['user_id'];
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$qty = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

if ($product_id <= 0) {
    die("잘못된 요청입니다.");
}
if ($qty <= 0) $qty = 1;
if ($qty > 99) $qty = 99;

// 이미 담겨있으면 수량 증가, 없으면 추가
$checkSql = "SELECT id, quantity FROM cart WHERE user_id=? AND product_id=?";
$stmt = $conn->prepare($checkSql);
$stmt->bind_param("ii", $user_id, $product_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if ($row) {
    $newQty = (int)$row['quantity'] + $qty;
    if ($newQty > 99) $newQty = 99;

    $updSql = "UPDATE cart SET quantity=? WHERE id=?";
    $stmt = $conn->prepare($updSql);
    $stmt->bind_param("ii", $newQty, $row['id']);
    $stmt->execute();
    $stmt->close();
} else {
    $insSql = "INSERT INTO cart (user_id, product_id, quantity, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($insSql);
    $stmt->bind_param("iii", $user_id, $product_id, $qty);
    $stmt->execute();
    $stmt->close();
}

$move_from_wishlist = isset($_POST['move_from_wishlist']) ? (int)$_POST['move_from_wishlist'] : 0;

// ✅ (옵션) 찜에서 담아온 경우, 찜 목록에서도 제거
if ($move_from_wishlist === 1) {
    $del = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ? LIMIT 1");
    $del->bind_param("ii", $user_id, $product_id);
    $del->execute();
    $del->close();
}

header("Location: cart.php");
exit;