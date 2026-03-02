<?php
session_start();
require_once __DIR__ . "/../DB/db.php";

$q = trim((string)($_GET['q'] ?? ''));

$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$whereSql = "";
$types = "";
$params = [];

if ($q !== '') {
  $whereSql = "WHERE title LIKE ? OR content LIKE ?";
  $types = "ss";
  $like = "%{$q}%";
  $params = [$like, $like];
}

// total count
$sqlCount = "SELECT COUNT(*) AS cnt FROM qna $whereSql";
$stmt = $conn->prepare($sqlCount);
if ($types !== "") {
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total = (int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
$stmt->close();

$totalPages = (int)ceil($total / $perPage);

// list
$sql = "
  SELECT
    q.id, q.title, q.created_at, q.user_id,
    COALESCE(NULLIF(u.display_name,''), u.username) AS writer_name
  FROM qna q
  LEFT JOIN users u ON u.id = q.user_id
  $whereSql
  ORDER BY q.id DESC
  LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);

if ($types !== "") {
  $types2 = $types . "ii";
  $params2 = array_merge($params, [$perPage, $offset]);
  $stmt->bind_param($types2, ...$params2);
} else {
  $stmt->bind_param("ii", $perPage, $offset);
}

$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <title>Q&A 게시판</title>
  <link rel="stylesheet" href="/electronics_shop/css/style.css">
  <style>
    body{background:#f6f7fb;}
    .wrap{max-width:980px; margin:24px auto; background:#fff; border:1px solid #eee; border-radius:16px; padding:18px;}
    .top{display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;}
    .btn{display:inline-block; padding:9px 12px; border-radius:10px; border:1px solid #ddd; text-decoration:none; color:#111; background:#fff;}
    .btn.primary{background:#111; color:#fff; border-color:#111;}
    .search{display:flex; gap:8px; flex-wrap:wrap; align-items:center; margin:12px 0;}
    .search input{padding:9px 12px; border:1px solid #ddd; border-radius:10px; width:260px;}
    table{width:100%; border-collapse:collapse;}
    th,td{padding:12px 10px; border-bottom:1px solid #eee; text-align:left;}
    th{background:#fafafa;}
    .muted{color:#666; font-size:13px;}
    .pager{display:flex; gap:8px; justify-content:center; margin-top:14px; flex-wrap:wrap;}
    .pager a{padding:7px 10px; border:1px solid #ddd; border-radius:10px; text-decoration:none; color:#111;}
    .pager .on{background:#111; color:#fff; border-color:#111;}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="top">
      <div>
        <h2 style="margin:0;">Q&A 게시판</h2>
        <div class="muted">질문/답변 형태의 간단 게시판 (목록/작성/상세/삭제)</div>
      </div>
      <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <a class="btn" href="/electronics_shop/index.php">상품 목록</a>
        <a class="btn" href="/electronics_shop/qna/guest_inquiry.php">비회원 문의</a>
        <a class="btn primary" href="/electronics_shop/qna/qna_write.php">글쓰기</a>
      </div>
    </div>

    <form method="get" class="search">
      <input type="text" name="q" placeholder="제목/내용 검색" value="<?= h($q) ?>">
      <button class="btn" type="submit">검색</button>
      <a class="btn" href="/electronics_shop/qna/qna_list.php">초기화</a>
    </form>

    <table>
      <thead>
        <tr>
          <th style="width:80px;">번호</th>
          <th>제목</th>
          <th style="width:140px;">작성자</th>
          <th style="width:160px;">작성일</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($total === 0): ?>
          <tr><td colspan="4" class="muted">게시글이 없습니다.</td></tr>
        <?php else: ?>
          <?php while($r = $result->fetch_assoc()): ?>
            <tr>
              <td><?= (int)$r['id'] ?></td>
              <td>
                <a href="/electronics_shop/qna/qna_view.php?id=<?= (int)$r['id'] ?>">
                  <?= h($r['title']) ?>
                </a>
              </td>
              <td><?= h($r['writer_name'] ?? ('유저#'.(int)$r['user_id'])) ?></td>
              <td class="muted"><?= h($r['created_at']) ?></td>
            </tr>
          <?php endwhile; ?>
        <?php endif; ?>
      </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
      <div class="pager">
        <?php for($p=1; $p<=$totalPages; $p++): ?>
          <?php
            $qs = "page={$p}";
            if ($q !== '') $qs .= "&q=" . urlencode($q);
          ?>
          <a class="<?= ($p===$page)?'on':'' ?>" href="/electronics_shop/qna/qna_list.php?<?= $qs ?>"><?= $p ?></a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>