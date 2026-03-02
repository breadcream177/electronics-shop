<?php
// [전체 교체] kakao/kakao_callback.php
session_start();

require_once __DIR__ . "/../DB/db.php";
require_once __DIR__ . "/kakao_config.php";      // ✅ 키/시크릿/리다이렉트는 여기로 빼는 걸 추천
require_once __DIR__ . "/../auth/social_user.php";

$BASE = "/electronics_shop";

$code = $_GET['code'] ?? '';
if ($code === '') exit("인가 코드(code)가 없습니다.");

// (선택) state 검증
$state = $_GET['state'] ?? '';
if (!empty($_SESSION['kakao_state'])) {
  if ($state === '' || !hash_equals($_SESSION['kakao_state'], $state)) {
    exit("state 검증 실패(요청 위조 가능).");
  }
  unset($_SESSION['kakao_state']);
}

// 1) 토큰 요청
$token_url = "https://kauth.kakao.com/oauth/token";
$post = [
  "grant_type"    => "authorization_code",
  "client_id"     => KAKAO_REST_API_KEY,
  "redirect_uri"  => KAKAO_REDIRECT_URI,
  "code"          => $code,
];

if (defined('KAKAO_CLIENT_SECRET') && KAKAO_CLIENT_SECRET !== '') {
  $post["client_secret"] = KAKAO_CLIENT_SECRET;
}

$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded;charset=utf-8"]);

$res = curl_exec($ch);
if ($res === false) exit("토큰 요청 cURL 오류: " . curl_error($ch));
$http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$token = json_decode($res, true);
$access_token = $token['access_token'] ?? '';
if ($access_token === '') {
  echo "<pre>HTTP_CODE: {$http_code}\n"; print_r($token); echo "</pre>";
  exit("토큰 발급 실패(access_token 없음)");
}

// 2) 사용자 정보 요청
$me_url = "https://kapi.kakao.com/v2/user/me";
$ch = curl_init($me_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  "Authorization: Bearer {$access_token}",
  "Content-Type: application/x-www-form-urlencoded;charset=utf-8"
]);

$me_res = curl_exec($ch);
if ($me_res === false) exit("사용자 정보 요청 cURL 오류: " . curl_error($ch));
$me_http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$k = json_decode($me_res, true);
$kakao_id = $k['id'] ?? null;
$nickname = $k['properties']['nickname'] ?? '카카오사용자';
$email = $k['kakao_account']['email'] ?? null;

if (!$kakao_id) {
  echo "<pre>HTTP_CODE: {$me_http_code}\n"; print_r($k); echo "</pre>";
  exit("카카오 사용자 ID가 없습니다.");
}

// 3) 통합 함수로 사용자 찾기/생성
$provider = "kakao";
$provider_id = (string)$kakao_id;

$user = social_find_or_create_user($conn, $provider, $provider_id, $email, $nickname);

// 4) 세션
$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['username'] = (string)$user['username'];
$_SESSION['display_name'] = (string)(($user['display_name'] ?? '') ?: $user['username']); // ✅ 핵심

header("Location: {$BASE}/index.php");
exit;