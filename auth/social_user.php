<?php
// [새 파일 추가] /auth/social_user.php
function social_find_or_create_user(mysqli $conn, string $provider, string $provider_id, ?string $email, ?string $username_hint): array
{
    // 1) provider + provider_id 로 먼저 찾기
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE provider=? AND provider_id=? LIMIT 1");
    $stmt->bind_param("ss", $provider, $provider_id);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($u) return $u;

    // 2) (있다면) email로 기존 계정 연결
    if ($email) {
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE email=? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $u2 = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($u2) {
            // 기존 계정에 provider 정보 연결
            $stmt = $conn->prepare("UPDATE users SET provider=?, provider_id=? WHERE id=?");
            $stmt->bind_param("ssi", $provider, $provider_id, $u2['id']);
            $stmt->execute();
            $stmt->close();
            return $u2;
        }
    }

    // 3) 없으면 신규 생성
    $base = $username_hint ?: ($provider . "_" . substr($provider_id, 0, 8));
    $username = $base;

    // username 충돌 방지(유니크 없다고 했으니 최소한 코드로 피함)
    for ($i = 0; $i < 5; $i++) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username=? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$exists) break;
        $username = $base . "_" . rand(1000, 9999);
    }

    // 소셜계정은 로컬비번이 없으니 더미 해시
    $dummy = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (username, password, email, provider, provider_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $username, $dummy, $email, $provider, $provider_id);
    $stmt->execute();
    $new_id = (int)$stmt->insert_id;
    $stmt->close();

    return ["id" => $new_id, "username" => $username];
}