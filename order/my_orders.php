<?php
session_start();
require_once __DIR__ . "/../DB/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); // ✅ 루트 login.php로
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// ✅ 컬럼명 통일: display_total 사용
$sql = "SELECT id, merchant_uid, display_total, status, created_at, paid_at
        FROM orders
        WHERE user_id=?
        ORDER BY id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>주문내역</title>
</head>
<body>
<h1>주문내역</h1>

<!-- ✅ order 폴더가 아니라 루트 메인으로 -->
<p><a href="../index.php">← 메인</a></p>

<table border="1" cellpadding="10" cellspacing="0">
    <tr>
        <th>주문번호</th>
        <th>표시금액</th>
        <th>상태</th>
        <th>주문일</th>
        <th>결제일</th>
        <th>상세</th>
    </tr>

    <?php while ($row = $res->fetch_assoc()) { ?>
        <tr>
            <td><?php echo htmlspecialchars($row['merchant_uid']); ?></td>
            <td><?php echo number_format((int)($row['display_total'] ?? 0)); ?>원</td>
            <td><?php echo htmlspecialchars($row['status']); ?></td>
            <td><?php echo htmlspecialchars($row['created_at']); ?></td>
            <td><?php echo htmlspecialchars($row['paid_at'] ?? ''); ?></td>

            <!-- ✅ 상세도 같은 폴더니까 그대로 OK -->
            <td><a href="order_detail.php?id=<?php echo (int)$row['id']; ?>">보기</a></td>
        </tr>
    <?php } ?>
</table>

</body>
</html>
<?php
$stmt->close();