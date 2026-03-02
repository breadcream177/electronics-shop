<?php
require_once __DIR__ . "/_admin_guard.php";
require_once __DIR__ . "/../DB/db.php";

$page_title = "관리자 - 유저 관리";
include __DIR__ . "/_admin_header.php";

$BASE = "/electronics_shop";

$kw = trim((string)($_GET['kw'] ?? ''));

$where = "";
$types = "";
$params = [];

if ($kw !== "") {
  $where = "WHERE username LIKE ? OR email LIKE ?";
  $types = "ss";
  $params[] = "%{$kw}%";
  $params[] = "%{$kw}%";
}

$sql = "
SELECT id, username, email, login_type, kakao_id, google_id, created_at
FROM users
{$where}
ORDER BY id DESC
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
  <h1 class="title">유저 관리</h1>
  <form method="get" class="row" style="margin:0;">
    <input class="input" type="text" name="kw" placeholder="username 또는 email 검색" value="<?php echo htmlspecialchars($kw); ?>">
    <button class="btn primary" type="submit">검색</button>
    <a class="btn" href="<?php echo $BASE; ?>/admin/users.php">초기화</a>
  </form>
</div>

<div class="card">
  <h2 class="title" style="margin-bottom:8px;">유저 목록</h2>
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>username</th>
        <th>email</th>
        <th>login_type</th>
        <th>kakao_id</th>
        <th>google_id</th>
        <th>가입일</th>
      </tr>
    </thead>
    <tbody>
      <?php while($u = $res->fetch_assoc()) { ?>
        <tr>
          <td><?php echo (int)$u['id']; ?></td>
          <td><?php echo htmlspecialchars($u['username'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($u['email'] ?? ''); ?></td>
          <td><span class="pill"><?php echo htmlspecialchars($u['login_type'] ?? ''); ?></span></td>
          <td><?php echo htmlspecialchars($u['kakao_id'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($u['google_id'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($u['created_at'] ?? ''); ?></td>
        </tr>
      <?php } ?>
    </tbody>
  </table>
</div>

<?php
if (isset($stmt) && $stmt instanceof mysqli_stmt) $stmt->close();
include __DIR__ . "/_admin_footer.php";