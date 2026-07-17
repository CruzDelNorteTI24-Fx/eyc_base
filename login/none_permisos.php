<?php
require_once __DIR__ . '/../layout/security_eyc.php';
eyc_send_security_headers();
eyc_start_secure_session();

define('eyc_LAYOUT', true);
define('eyc_BASE_URL', '../');

require_once __DIR__ . '/../layout/sidebar_eyc.php';
require_once __DIR__ . '/../layout/header_eyc.php';
require_once __DIR__ . '/../layout/footer_eyc.php';
require_once __DIR__ . '/../layout/content_eyc.php';

function eyc_np_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$isLogged = isset($_SESSION['usuario']) && trim((string)$_SESSION['usuario']) !== '';
$userName = trim((string)($_SESSION['nombre'] ?? $_SESSION['usuario'] ?? 'Usuario'));
$role = trim((string)($_SESSION['web_rol'] ?? 'Sin sesion'));
$requestedPath = 'No identificada';

if (!empty($_SERVER['HTTP_REFERER'])) {
    $parts = parse_url((string)$_SERVER['HTTP_REFERER']);
    if (is_array($parts)) {
        $refererHost = strtolower((string)($parts['host'] ?? ''));
        $currentHost = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
        if ($refererHost === '' || $currentHost === '' || $refererHost === $currentHost) {
            $requestedPath = (string)($parts['path'] ?? 'No identificada');
            if (!empty($parts['query'])) {
                $requestedPath .= '?' . $parts['query'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acceso no permitido | eyc</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="<?= eyc_asset('img/eyc.png') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= eyc_asset('assets/css/sidebar_eyc.css') ?>">
    <link rel="stylesheet" href="<?= eyc_asset('assets/css/header_eyc.css') ?>">
    <link rel="stylesheet" href="<?= eyc_asset('assets/css/main_eyc.css') ?>">
    <link rel="stylesheet" href="<?= eyc_asset('assets/css/footer_eyc.css') ?>">
    <link rel="stylesheet" href="<?= eyc_asset('assets/css/content_eyc.css') ?>">
    <link rel="stylesheet" href="<?= eyc_asset('assets/css/none_permisos_eyc.css') ?>">
</head>
<body class="<?= $isLogged ? 'with-sidebar' : 'eyc-denied-guest' ?>">
<?php if ($isLogged): ?>
    <?php eyc_render_header(); ?>
    <?php eyc_render_sidebar(); ?>
<?php else: ?>
    <header class="eyc-denied-guest-header">
        <a href="<?= eyc_np_h(eyc_base_url('login/login.php')) ?>" class="eyc-denied-brand" aria-label="Ir al login">
            <img src="<?= eyc_np_h(eyc_base_url('img/eyc.png')) ?>" alt="eyc">
            <span>
                <strong>eyc</strong>
                <small>ERP Operativo de Transporte</small>
            </span>
        </a>
    </header>
<?php endif; ?>

<main class="<?= $isLogged ? 'main-content eyc-main eyc-main--module eyc-main--compact-access' : 'eyc-denied-main eyc-main eyc-main--compact-access' ?>" role="main">
    <div class="eyc-main__inner eyc-denied-shell">
        <?php eyc_render_content_separator('top'); ?>

        <section class="eyc-denied-card" aria-labelledby="deniedTitle">
            <div class="eyc-denied-icon" aria-hidden="true"><i class="bi bi-shield-lock-fill"></i></div>
            <div class="eyc-denied-copy">
                <span class="eyc-denied-kicker">Control de acceso</span>
                <h1 id="deniedTitle">Acceso no permitido</h1>
                <p>Tu usuario no tiene permiso para ingresar a esta interfaz. Si necesitas acceso, solicita la habilitacion a un administrador.</p>
            </div>

            <dl class="eyc-denied-meta">
                <div><dt>Usuario</dt><dd><?= eyc_np_h($userName) ?></dd></div>
                <div><dt>Rol</dt><dd><?= eyc_np_h($role) ?></dd></div>
                <div><dt>Vista solicitada</dt><dd><?= eyc_np_h($requestedPath) ?></dd></div>
            </dl>

            <div class="eyc-denied-actions">
                <a href="<?= eyc_np_h(eyc_base_url('index.php')) ?>" class="eyc-denied-btn eyc-denied-btn--primary"><i class="bi bi-house-door-fill" aria-hidden="true"></i><span>Ir al panel</span></a>
                <button type="button" class="eyc-denied-btn eyc-denied-btn--ghost" onclick="history.length > 1 ? history.back() : location.href='<?= eyc_np_h(eyc_base_url('index.php')) ?>'"><i class="bi bi-arrow-left" aria-hidden="true"></i><span>Volver</span></button>
                <?php if (!$isLogged): ?>
                    <a href="<?= eyc_np_h(eyc_base_url('login/login.php')) ?>" class="eyc-denied-btn eyc-denied-btn--ghost"><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i><span>Iniciar sesion</span></a>
                <?php endif; ?>
            </div>
        </section>

        <?php eyc_render_content_separator('bottom'); ?>
    </div>
</main>

<?php eyc_render_footer(); ?>

<script src="<?= eyc_asset('assets/js/header_eyc.js') ?>"></script>
<script src="<?= eyc_asset('assets/js/sidebar_eyc.js') ?>"></script>
</body>
</html>
