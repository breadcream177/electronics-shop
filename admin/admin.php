<?php
require_once __DIR__ . "/_admin_guard.php";
require_once __DIR__ . "/../DB/db.php";

$page_title = "관리자 - 주문 목록";
include __DIR__ . "/_admin_header.php";

$BASE = "/electronics_shop";

$status_filter = (string)($_GET['status'] ?? 'all'); // all|paid|ready|shipped|done|canceled|pending
$allowed_filter = ['all','paid','ready','shipped','done','canceled','pending'];

if (!in_array($status_filter, $allowed_filter, true)) $status_filter = 'all';

$where = "";
$params = [];
$types = "";

if ($status_filter !== 'all') {
  // pending은 미결제/대기 상태로 보는 용도(프로젝트마다 다를 수 있어 status 기준만)
  if ($status_filter === 'pending') {
    $where = "WHERE status <> 'paid'";
  } else {
    $where = "WHERE status = ?";
    $types = "s";
    $params[] = $status_filter;
  }
}

$sql = "
SELECT id, merchant_uid, user_id, display_total, pay_amount, status, pay_status, created_at, paid_at
FROM orders
{$where}
ORDER BY id DESC
";

if ($types !== '') {
  $stmt = $conn->prepare($sql);
  $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $res = $stmt->get_result();
} else {
  $res = $conn->query($sql);
}

function status_kr($st){
  $st = (string)$st;
  return match($st){
    'paid' => '결제완료',
    'ready' => '상품준비중',
    'shipped' => '배송중',
    'done' => '배송완료',
    'canceled' => '취소',
    default => $st,
  };
}
?>
<div class="card" style="margin-bottom:14px;">
  <h1 class="title">주문 관리</h1>
  <div class="row">
    <form method="get" class="row" style="margin:0;">
      <span class="muted">상태 필터</span>
      <select name="status">
        <?php
          $opts = [
            'all'=>'전체',
            'paid'=>'결제완료',
            'ready'=>'상품준비중',
            'shipped'=>'배송중',
            'done'=>'배송완료',
            'canceled'=>'취소',
            'pending'=>'(paid 제외)'
          ];
          foreach($opts as $k=>$v){
            $sel = ($status_filter===$k) ? "selected" : "";
            echo "<option value='".htmlspecialchars($k)."' {$sel}>".htmlspecialchars($v)."</option>";
          }
        ?>
      </select>
      <button class="btn primary" type="submit">적용</button>
      <a class="btn" href="<?php echo $BASE; ?>/admin/admin.php">초기화</a>
    </form>
  </div>
</div>

<div class="card">
  <h2 class="title" style="margin-bottom:8px;">주문 목록</h2>
  <div class="muted" style="margin-bottom:10px;">모든 주문 내역 관리표</div>

  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>주문번호</th>
        <th>유저ID</th>
        <th>표시금액</th>
        <th>실결제</th>
        <th>상태</th>
        <th>상태 변경</th>
        <th>주문일</th>
        <th>결제일</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($r = $res->fetch_assoc()) { ?>
        <?php
          $id = (int)$r['id'];
          $st = (string)($r['status'] ?? '');
        ?>
        <tr>
          <td><?php echo $id; ?></td>
          <td><?php echo htmlspecialchars($r['merchant_uid'] ?? ''); ?></td>
          <td><?php echo (int)($r['user_id'] ?? 0); ?></td>
          <td><?php echo number_format((int)($r['display_total'] ?? 0)); ?>원</td>
          <td><?php echo number_format((int)($r['pay_amount'] ?? 0)); ?>원</td>
          <td><span class="pill"><?php echo htmlspecialchars(status_kr($st)); ?></span></td>

          <td>
            <form method="post" action="<?php echo $BASE; ?>/admin/order_status_update.php" class="row" style="margin:0;">
              <input type="hidden" name="id" value="<?php echo $id; ?>">
              <select name="status">
                <?php
                  $cand = ['paid'=>'결제완료','ready'=>'상품준비중','shipped'=>'배송중','done'=>'배송완료','canceled'=>'취소'];
                  foreach($cand as $k=>$v){
                    $sel = ($st===$k) ? "selected" : "";
                    echo "<option value='".htmlspecialchars($k)."' {$sel}>".htmlspecialchars($v)."</option>";
                  }
                ?>
              </select>
              <button class="btn" type="submit">변경</button>
            </form>
          </td>

          <td><?php echo htmlspecialchars($r['created_at'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($r['paid_at'] ?? ''); ?></td>
        </tr>
      <?php } ?>
    </tbody>
  </table>
</div>

<?php
if (isset($stmt) && $stmt instanceof mysqli_stmt) $stmt->close();
include __DIR__ . "/_admin_footer.php";