<?php
session_start();
require_once __DIR__ . "/../DB/db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: /electronics_shop/qna/qna_list.php");
  exit;
}

if (!isset($_SESSION['user_id'])) {
  header("Location: /electronics_shop/login.php");
  exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
  header("Location: /electronics_shop/qna/qna_list.php");
  exit;
}

$user_id = (int)$_SESSION['user_id'];

// 본인 글만 삭제
$stmt = $conn->prepare("DELETE FROM qna WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$stmt->close();

header("Location: /electronics_shop/qna/qna_list.php");
exit;