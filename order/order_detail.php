<?php
session_start();
require_once __DIR__ . "/../DB/db.php";

if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }
$user_id = (int)$_SESSION['user_id'];
$order_id = (int)($_GET['id'] ?? 0);

$sql = "SELECT * FROM orders WHERE id=? AND user_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$order) die("주문 없음");

$sql = "SELECT oi.quantity, oi.price, p.name, p.image
        FROM order_items oi JOIN products p ON oi.product_id=p.id
        WHERE oi.order_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items = $stmt->get_result();
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>주문상세</title></head>
<body>
<h1>주문상세</h1>
<p><a href="my_orders.php">← 주문내역</a></p>

<p>주문번호: <?php echo htmlspecialchars($order['merchant_uid']); ?></p>
<p>상태: <?php echo htmlspecialchars($order['status']); ?></p>
<p>표시금액: <?php echo number_format((int)($order['display_total'] ?? 0)); ?>원</p>
<p>실결제: <?php echo number_format((int)($order['pay_amount'] ?? 0)); ?>원</p>

<h3>상품</h3>
<table border="1" cellpadding="10" cellspacing="0">
<tr><th>상품</th><th>가격</th><th>수량</th></tr>

<?php while($it = $items->fetch_assoc()) { ?>
<tr>
  <td>
    <img src="../images/<?php echo htmlspecialchars($it['image']); ?>" width="50">
    <?php echo htmlspecialchars($it['name']); ?>
  </td>
  <td><?php echo number_format((int)$it['price']); ?>원</td>
  <td><?php echo (int)$it['quantity']; ?></td>
</tr>
<?php } ?>
</table>

<?php if (($order['status'] ?? '') === 'paid') : ?>
  <form method="post" action="../Payment/payment_cancel.php"
        onsubmit="return confirm('정말 즉시 취소할까요?');">
    <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
    <button type="submit">결제 즉시 취소</button>
  </form>
<?php endif; ?>

</body></html>
<?php $stmt->close(); ?>