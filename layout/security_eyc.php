<?php
if (!defined('eyc_SECURITY_LOADED')) {
    define('eyc_SECURITY_LOADED', true);
}

function eyc_is_https(): bool {
    $https = strtolower((string)($_SERVER['HTTPS'] ?? ''));
    $forwardedProto = strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    $forwardedSsl = strtolower((string)($_SERVER['HTTP_X_FORWARDED_SSL'] ?? ''));

    return $https === 'on'
        || $https === '1'
        || $forwardedProto === 'https'
        || $forwardedSsl === 'on';
}

function eyc_send_security_headers(): void {
    if (headers_sent()) {
        return;
    }

    header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');

    if (eyc_is_https()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }

    $csp = implode('; ', [
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net",
        "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net",
        "img-src 'self' data:",
        "font-src 'self' data: https://cdnjs.cloudflare.com https://cdn.jsdelivr.net",
        "connect-src 'self'",
        "frame-ancestors 'self'",
        "base-uri 'self'",
        "form-action 'self'",
    ]);
    header('Content-Security-Policy: ' . $csp);
}

function eyc_start_secure_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');

    if (eyc_is_https()) {
        ini_set('session.cookie_secure', '1');
    }

    $params = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $params['path'] ?: '/',
        'domain' => $params['domain'] ?? '',
        'secure' => eyc_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

function eyc_csrf_token(string $scope = 'default'): string {
    eyc_start_secure_session();
    $key = 'eyc_csrf_' . $scope;

    if (empty($_SESSION[$key]) || !is_string($_SESSION[$key])) {
        $_SESSION[$key] = bin2hex(random_bytes(32));
    }

    return $_SESSION[$key];
}

function eyc_verify_csrf(?string $token, string $scope = 'default'): bool {
    eyc_start_secure_session();
    $key = 'eyc_csrf_' . $scope;

    return is_string($token)
        && isset($_SESSION[$key])
        && is_string($_SESSION[$key])
        && hash_equals($_SESSION[$key], $token);
}

function eyc_client_ip(): string {
    return (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

function eyc_login_rate_file(string $usuario): string {
    $dir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'eyc_login_rate';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }

    $key = hash('sha256', strtolower(trim($usuario)) . '|' . eyc_client_ip());
    return $dir . DIRECTORY_SEPARATOR . $key . '.json';
}

function eyc_login_rate_status(string $usuario, int $limit = 6, int $windowSeconds = 900, int $lockSeconds = 900): array {
    $now = time();
    $file = eyc_login_rate_file($usuario);
    $state = ['attempts' => [], 'locked_until' => 0];

    if (is_file($file)) {
        $decoded = json_decode((string)@file_get_contents($file), true);
        if (is_array($decoded)) {
            $state = array_merge($state, $decoded);
        }
    }

    $attempts = array_values(array_filter(
        array_map('intval', (array)($state['attempts'] ?? [])),
        static fn($stamp) => $stamp >= ($now - $windowSeconds)
    ));

    $lockedUntil = (int)($state['locked_until'] ?? 0);
    if ($lockedUntil > $now) {
        return [
            'blocked' => true,
            'remaining_seconds' => $lockedUntil - $now,
            'attempts' => count($attempts),
        ];
    }

    if ($lockedUntil > 0 && $lockedUntil <= $now) {
        $lockedUntil = 0;
    }

    if (count($attempts) >= $limit) {
        $lockedUntil = $now + $lockSeconds;
        @file_put_contents($file, json_encode([
            'attempts' => $attempts,
            'locked_until' => $lockedUntil,
        ]), LOCK_EX);

        return [
            'blocked' => true,
            'remaining_seconds' => $lockSeconds,
            'attempts' => count($attempts),
        ];
    }

    @file_put_contents($file, json_encode([
        'attempts' => $attempts,
        'locked_until' => $lockedUntil,
    ]), LOCK_EX);

    return [
        'blocked' => false,
        'remaining_seconds' => 0,
        'attempts' => count($attempts),
    ];
}

function eyc_login_rate_register_failure(string $usuario): void {
    if (trim($usuario) === '') {
        return;
    }

    $status = eyc_login_rate_status($usuario);
    if (!empty($status['blocked'])) {
        return;
    }

    $file = eyc_login_rate_file($usuario);
    $state = ['attempts' => [], 'locked_until' => 0];

    if (is_file($file)) {
        $decoded = json_decode((string)@file_get_contents($file), true);
        if (is_array($decoded)) {
            $state = array_merge($state, $decoded);
        }
    }

    $state['attempts'][] = time();
    @file_put_contents($file, json_encode($state), LOCK_EX);
    eyc_login_rate_status($usuario);
}

function eyc_login_rate_clear(string $usuario): void {
    if (trim($usuario) === '') {
        return;
    }

    $file = eyc_login_rate_file($usuario);
    if (is_file($file)) {
        @unlink($file);
    }
}
