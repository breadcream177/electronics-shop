<?php
// config.php
// 프로젝트 공통 설정 파일 (include/require 용)

// 서버 내부 경로 (config.php가 프로젝트 루트에 있을 때 정상)
define('BASE_PATH', __DIR__);

// 브라우저 접근 URL 기준 (프로젝트 루트 URL)
// 예: http://localhost/electronics_shop/...
define('BASE_URL', '/electronics_shop');

// 개발/시연 모드
// true  : 이메일 대신 화면에 링크/아이디 출력 가능 (시연 안정성)
// false : 운영 모드(출력 금지, 이메일 발송 구조로 동작)
define('DEV_MODE', true);

/**
 * BASE_URL 기준으로 안전하게 URL을 만드는 헬퍼
 * 예: url('/auth/find_id.php') -> /electronics_shop/auth/find_id.php
 */
function url(string $path): string {
    $path = '/' . ltrim($path, '/');
    return rtrim(BASE_URL, '/') . $path;
}