<?php
require_once __DIR__ . "/_admin_guard.php";
require_once __DIR__ . "/../DB/db.php";

$page_title = "관리자 - 리뷰 관리";
include __DIR__ . "/_admin_header.php";

$BASE = "/electronics_shop";

$kw = trim((string)($_GET['kw'] ?? ''));

$where = "";
$types = "";
$params = [];

if ($kw !== "") {
  $where = "WHERE p.name LIKE ? OR u.username LIKE ? OR r.comment LIKE ?";
  $types = "sss";
  $params[] = "%{$kw}%";
  $params[] = "%{$kw}%";
  $params[] = "%{$kw}%";
}

$sql = "
SELECT
  r.id, r.product_id, r.user_id, r.rating, r.comment, r.created_at,
  p.name AS product_name,
  u.username AS user_name
FROM reviews r
LEFT JOIN products p ON p.id = r.product_id
LEFT JOIN users u ON u.id = r.user_id
{$where}
ORDER BY r.id DESC
";

if ($types !== "") {
  $stmt = $conn->prepare($sql);
  $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $res = $stmt->get_result();
} else {
  $res = $conn->query($sql);
}
?>
<div class="card" style="margin-bottom:14px;">
  <h1 class="title">리뷰 관리</h1>
  <form method="get" class="row" style="margin:0;">
    <input class="input" type="text" name="kw" placeholder="상품명/유저/내용 검색" value="<?php echo htmlspecialchars($kw); ?>">
    <button class="btn primary" type="submit">검색</button>
    <a class="btn" href="<?php echo $BASE; ?>/admin/reviews.php">초기화</a>
  </form>
</div>

<div class="card">
  <h2 class="title" style="margin-bottom:8px;">리뷰 목록</h2>
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>상품</th>
        <th>작성자</th>
        <th>별점</th>
        <th>내용</th>
        <th>작성일</th>
        <th>관리</th>
      </tr>
    </thead>
    <tbody>
      <?php while($r = $res->fetch_assoc()) { ?>
        <?php $rid = (int)$r['id']; ?>
        <tr>
          <td><?php echo $rid; ?></td>
          <td>
            <?php echo htmlspecialchars($r['product_name'] ?? ('#'.(int)$r['product_id'])); ?>
            <div class="muted" style="font-size:12px;">product_id: <?php echo (int)$r['product_id']; ?></div>
          </td>
          <td>
            <?php echo htmlspecialchars($r['user_name'] ?? ('#'.(int)$r['user_id'])); ?>
            <div class="muted" style="font-size:12px;">user_id: <?php echo (int)$r['user_id']; ?></div>
          </td>
          <td><span class="pill">⭐ <?php echo (int)($r['rating'] ?? 0); ?></span></td>
          <td style="white-space:pre-wrap;word-break:break-word;"><?php echo htmlspecialchars($r['comment'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($r['created_at'] ?? ''); ?></td>
          <td>
            <form method="post" action="<?php echo $BASE; ?>/admin/review_delete.php" onsubmit="return confirm('이 리뷰를 삭제할까요?');" style="margin:0;">
              <input type="hidden" name="id" value="<?php echo $rid; ?>">
              <button class="btn danger" type="submit">삭제</button>
            </form>
          </td>
        </tr>
      <?php } ?>
    </tbody>
  </table>
</div>

<?php
if (isset($stmt) && $stmt instanceof mysqli_stmt) $stmt->close();
include __DIR__ . "/_admin_footer.php";