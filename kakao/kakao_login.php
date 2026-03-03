<?php
// kakao/kakao_login.php
session_start();

require_once __DIR__ . "/kakao_config.php";

// (선택) state로 CSRF 방지 - 간단히 세션에 저장
if (empty($_SESSION['kakao_state'])) {
  $_SESSION['kakao_state'] = bin2hex(random_bytes(16));
}
$state = $_SESSION['kakao_state'];

// ✅ 여기서도 config의 KAKAO_REDIRECT_URI를 그대로 사용 (login/callback 일치)
$auth_url = "https://kauth.kakao.com/oauth/authorize"
  . "?response_type=code"
  . "&client_id=" . urlencode(KAKAO_REST_API_KEY)
  . "&redirect_uri=" . urlencode(KAKAO_REDIRECT_URI)
  . "&state=" . urlencode($state);

header("Location: " . $auth_url);
exit;