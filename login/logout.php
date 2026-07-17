<?php
require_once __DIR__ . '/../layout/security_eyc.php';
eyc_send_security_headers();
eyc_start_secure_session();

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params['path'] ?? '/',
        'domain' => $params['domain'] ?? '',
        'secure' => eyc_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

session_unset();
session_destroy();

header('Location: login.php');
exit();
?>
