<?php
require_once __DIR__ . "/_admin_guard.php";
require_once __DIR__ . "/../DB/db.php";

$BASE = "/electronics_shop";

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
  header("Location: {$BASE}/admin/reviews.php");
  exit;
}

$stmt = $conn->prepare("DELETE FROM reviews WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

header("Location: {$BASE}/admin/reviews.php");
exit;