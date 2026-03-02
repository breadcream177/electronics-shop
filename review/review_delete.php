<?php
// review/review_delete.php
session_start();
require_once __DIR__ . "/../DB/db.php";

if (!isset($_SESSION['user_id'])) {
  header("Location: /electronics_shop/login.php");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: /electronics_shop/index.php");
  exit;
}

$user_id    = (int)$_SESSION['user_id'];
$review_id  = (int)($_POST['review_id'] ?? 0);
$product_id = (int)($_POST['product_id'] ?? 0);

if ($review_id <= 0 || $product_id <= 0) exit("잘못된 요청");

// 본인 리뷰만 삭제
$stmt = $conn->prepare("DELETE FROM reviews WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $review_id, $user_id);
$stmt->execute();
$stmt->close();

header("Location: /electronics_shop/product_detail.php?id=" . $product_id);
exit;