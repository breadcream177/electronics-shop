<?php
// kakao/kakao_config.php
// ✅ 카카오 REST API 키/시크릿
define('KAKAO_REST_API_KEY', 'b774f3ca77d25ca68563ef7368ed0740');

// (선택) 사용 중이면 넣고, 아니면 빈 문자열 유지
define('KAKAO_CLIENT_SECRET', 'jWAQhoW554R3P3w0A6Jqp5WHsTKhs5Nn');

// ✅ 현재 접속 도메인(ngrok/localhost) 자동 인식해서 redirect_uri 생성
function kakao_current_base_url(): string {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // 프로젝트 루트 경로 (고정)
    return $scheme . '://' . $host . '/electronics_shop';
}

define('KAKAO_REDIRECT_URI', kakao_current_base_url() . '/kakao/kakao_callback.php');