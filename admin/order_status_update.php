<?php
require_once __DIR__ . "/_admin_guard.php";
require_once __DIR__ . "/../DB/db.php";

$BASE = "/electronics_shop";

$id = (int)($_POST['id'] ?? 0);
$status = (string)($_POST['status'] ?? '');

$allowed = ['paid','ready','shipped','done','canceled'];

if ($id <= 0 || !in_array($status, $allowed, true)) {
  header("Location: {$BASE}/admin/admin.php");
  exit;
}

$stmt = $conn->prepare("UPDATE orders SET status=? WHERE id=?");
$stmt->bind_param("si", $status, $id);
$stmt->execute();
$stmt->close();

header("Location: {$BASE}/admin/admin.php");
exit;