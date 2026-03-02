<?php
// [전체 교체] google/google_callback.php
session_start();

require_once __DIR__ . '/../DB/db.php';
require_once __DIR__ . '/google_config.php';
require_once __DIR__ . '/../auth/social_user.php';

$BASE = "/electronics_shop";

if (!isset($_GET['code'])) {
    die('구글 로그인 실패: code 없음');
}

$code = $_GET['code'];

/* 1) 토큰 요청 */
$token_url = 'https://oauth2.googleapis.com/token';
$post_fields = [
    'code' => $code,
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'grant_type' => 'authorization_code'
];

$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$token_response = curl_exec($ch);
if ($token_response === false) die('cURL 오류(token): ' . curl_error($ch));
curl_close($ch);

$token_data = json_decode($token_response, true);
$access_token = $token_data['access_token'] ?? '';
if ($access_token === '') die('토큰 발급 실패: ' . htmlspecialchars($token_response));

/* 2) 사용자 정보 요청 */
$userinfo_url = 'https://openidconnect.googleapis.com/v1/userinfo';

$ch = curl_init($userinfo_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$userinfo_response = curl_exec($ch);
if ($userinfo_response === false) die('cURL 오류(userinfo): ' . curl_error($ch));
curl_close($ch);

$g = json_decode($userinfo_response, true);
$google_sub = $g['sub'] ?? '';
if ($google_sub === '') die('사용자 정보 조회 실패: ' . htmlspecialchars($userinfo_response));

$email = $g['email'] ?? null;
$name  = $g['name'] ?? 'Google User';

/* 3) 통합 함수로 사용자 찾기/생성 */
$provider = "google";
$provider_id = (string)$google_sub;

$user = social_find_or_create_user($conn, $provider, $provider_id, $email, $name);

/* 4) 세션 */
$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['username'] = (string)$user['username'];
$_SESSION['display_name'] = (string)(($user['display_name'] ?? '') ?: $user['username']); // ✅ 핵심

header("Location: {$BASE}/index.php");
exit;