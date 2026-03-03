<?php
// google/google_config.php

define('GOOGLE_CLIENT_ID', '949509083613-a1s0mlljkstq6sbmn8amdva8r8qpi64t.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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'); // ✅ 너 시크릿 넣기

// ✅ 현재 접속 도메인(ngrok/localhost) 자동 인식해서 redirect_uri 생성
function google_current_base_url(): string {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // 프로젝트 루트 경로 (고정)
    return $scheme . '://' . $host . '/electronics_shop';
}

define('GOOGLE_REDIRECT_URI', google_current_base_url() . '/google/google_callback.php');

// OpenID 기본 스코프
define('GOOGLE_SCOPE', 'openid email profile');