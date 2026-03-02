<?php
// google/google_config.php

define('GOOGLE_CLIENT_ID', '949509083613-a1s0mlljkstq6sbmn8amdva8r8qpi64t.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');

// ⚠️ google 폴더로 바뀐 콜백 경로
define('GOOGLE_REDIRECT_URI', 'http://localhost/electronics_shop/google/google_callback.php');

// OpenID 기본 스코프
define('GOOGLE_SCOPE', 'openid email profile');