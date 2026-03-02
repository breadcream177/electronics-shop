<?php
// review/review_add.php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: /electronics_shop/index.php");
  exit;
}

require_once __DIR__ . "/../DB/db.php";

if (!isset($_SESSION['user_id'])) {
  header("Location: /electronics_shop/login.php");
  exit;
}

$user_id    = (int)$_SESSION['user_id'];
$product_id = (int)($_POST['product_id'] ?? 0);
$rating     = (int)($_POST['rating'] ?? 0);
$comment    = trim((string)($_POST['comment'] ?? ''));
$review_id  = (int)($_POST['review_id'] ?? 0);

if ($product_id <= 0) exit("잘못된 요청(product_id)");
if ($rating < 1 || $rating > 5) exit("평점은 1~5만 가능합니다.");
if ($comment === '') exit("리뷰 내용을 입력하세요.");

// 1) review_id가 있으면 해당 리뷰만 수정 (본인 + 상품 검증)
if ($review_id > 0) {
  $stmt = $conn->prepare("
    UPDATE reviews
    SET rating = ?, comment = ?, created_at = NOW()
    WHERE id = ? AND user_id = ? AND product_id = ?
  ");
  $stmt->bind_param("isiii", $rating, $comment, $review_id, $user_id, $product_id);
  $stmt->execute();
  $affected = $stmt->affected_rows;
  $stmt->close();

  // 본인 글이 아니거나 이미 삭제된 경우 등
  if ($affected === 0) {
    // 에러를 크게 내지 않고 상세로 돌려보냄
    header("Location: /electronics_shop/product_detail.php?id=" . $product_id);
    exit;
  }

  header("Location: /electronics_shop/product_detail.php?id=" . $product_id);
  exit;
}

// 2) 신규는 항상 INSERT (한 사람이 여러 리뷰 작성 가능)
$stmt = $conn->prepare("
  INSERT INTO reviews (user_id, product_id, rating, comment, created_at)
  VALUES (?, ?, ?, ?, NOW())
");
$stmt->bind_param("iiis", $user_id, $product_id, $rating, $comment);
$stmt->execute();
$stmt->close();

header("Location: /electronics_shop/product_detail.php?id=" . $product_id);
exit;