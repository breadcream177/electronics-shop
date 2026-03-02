<?php
// tools/cleanup_unpaid_orders.php
// 실행 예: php tools/cleanup_unpaid_orders.php
require_once __DIR__ . "/../DB/db.php";

// 정책: 결제대기 + 미결제 + 2시간 지난 주문 삭제
// paid는 절대 삭제하지 않음
$conn->begin_transaction();
try {
  $sql = "SELECT id FROM orders
          WHERE status='결제대기'
            AND (pay_status IS NULL OR pay_status!='paid')
            AND created_at < (NOW() - INTERVAL 2 HOUR)";
  $rs = $conn->query($sql);

  $ids = [];
  while ($r = $rs->fetch_assoc()) $ids[] = (int)$r['id'];

  foreach ($ids as $oid) {
    $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id=?");
    $stmt->bind_param("i", $oid);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM orders WHERE id=?");
    $stmt->bind_param("i", $oid);
    $stmt->execute();
    $stmt->close();
  }

  $conn->commit();
  echo "OK: deleted " . count($ids) . " unpaid orders\n";

} catch (Throwable $e) {
  $conn->rollback();
  echo "FAIL: " . $e->getMessage() . "\n";
}