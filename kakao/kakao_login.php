<?php
// kakao/kakao_login.php
session_start();

$KAKAO_REST_API_KEY = "b774f3ca77d25ca68563ef7368ed0740";
$REDIRECT_URI = "http://localhost/electronics_shop/kakao/kakao_callback.php";

// (선택) state로 CSRF 방지 - 간단히 세션에 저장
if (empty($_SESSION['kakao_state'])) {
  $_SESSION['kakao_state'] = bin2hex(random_bytes(16));
}
$state = $_SESSION['kakao_state'];

$auth_url = "https://kauth.kakao.com/oauth/authorize"
  . "?response_type=code"
  . "&client_id=" . urlencode($KAKAO_REST_API_KEY)
  . "&redirect_uri=" . urlencode($REDIRECT_URI)
  . "&state=" . urlencode($state);

header("Location: " . $auth_url);
exit;