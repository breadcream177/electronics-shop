<?php
// wishlist_add.php : 찜 추가 (중복 방지) 후 원래 페이지로 돌아가기
session_start();
require_once __DIR__ . "/../DB/db.php";

if (!isset($_SESSION['user_id'])) {
  header("Location: /electronics_shop/login.php");
  exit;
}

$user_id = (int)$_SESSION['user_id'];

// product_id는 GET 또는 POST 둘 다 받게 (버튼 구현 방식이 달라도 동작)
$product_id = 0;
if (isset($_POST['product_id'])) $product_id = (int)$_POST['product_id'];
else if (isset($_GET['product_id'])) $product_id = (int)$_GET['product_id'];

if ($product_id <= 0) {
  header("Location: /electronics_shop/index.php");
  exit;
}

// 중복 찜 방지: wishlist 테이블에 (user_id, product_id) UNIQUE 가 있으면 INSERT IGNORE가 가장 깔끔
$stmt = $conn->prepare("INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?, ?)");
$stmt->bind_param("ii", $user_id, $product_id);
$stmt->execute();
$stmt->close();

// 돌아갈 페이지: referer가 있으면 그쪽, 없으면 index
$back = $_SERVER['HTTP_REFERER'] ?? ("/electronics_shop/index.php");

// 외부로 리다이렉트 방지: 우리 사이트 경로만 허용
if (strpos($back, "/electronics_shop/") === false) {
  $back = "/electronics_shop/index.php";
}

header("Location: " . $back);
exit;