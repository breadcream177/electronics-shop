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
$qty = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

if ($cart_id <= 0) {
    header("Location: cart.php");
    exit;
}

if ($qty < 1) $qty = 1;
if ($qty > 99) $qty = 99;

// 본인 장바구니만 수정 가능
$sql = "UPDATE cart SET quantity=? WHERE id=? AND user_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $qty, $cart_id, $user_id);
$stmt->execute();
$stmt->close();

header("Location: cart.php");
exit;