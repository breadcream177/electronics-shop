<?php
session_start();

$BASE = "/electronics_shop";

// 로그인 체크
if (!isset($_SESSION['user_id'])) {
  header("Location: {$BASE}/login.php");
  exit;
}

// 관리자 체크(기존 방식 유지)
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
  http_response_code(403);
  exit("관리자만 접근 가능");
}