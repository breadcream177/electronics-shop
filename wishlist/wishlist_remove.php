<?php
session_start();
require_once __DIR__ . "/../DB/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: /electronics_shop/login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$product_id = (int)($_POST['product_id'] ?? 0);

$stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id=? AND product_id=?");
$stmt->bind_param("ii", $user_id, $product_id);
$stmt->execute();
$stmt->close();

header("Location: /electronics_shop/wishlist/wishlist.php");
exit;