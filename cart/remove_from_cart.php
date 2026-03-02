<?php
session_start();
require_once __DIR__ . "/../DB/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$IMG_BASE = "../images/";
$user_id = (int)$_SESSION['user_id'];
$cart_id = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;

if ($cart_id <= 0) {
    header("Location: cart.php");
    exit;
}

// 본인 장바구니만 삭제 가능하도록 보안 조건 추가
$sql = "DELETE FROM cart WHERE id=? AND user_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $cart_id, $user_id);
$stmt->execute();
$stmt->close();

header("Location: cart.php");
exit;