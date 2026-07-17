<?php
if (!defined('eyc_LAYOUT')) {
    exit('Acceso no permitido.');
}

require_once __DIR__ . '/assets_eyc.php';

if (!function_exists('eyc_base_url')) {
    function eyc_base_url(string $path = ''): string {
        $base = defined('eyc_BASE_URL') ? eyc_BASE_URL : './';
        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }
}

function eyc_header_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function eyc_header_initials(string $name): string {
    $name = trim($name);
    if ($name === '') {
        return 'eyc';
    }

    $parts = preg_split('/\s+/', $name) ?: [];
    $initials = '';

    foreach ($parts as $part) {
        if ($part === '') continue;

        $letter = function_exists('mb_substr')
            ? mb_substr($part, 0, 1, 'UTF-8')
            : substr($part, 0, 1);

        $initials .= function_exists('mb_strtoupper')
            ? mb_strtoupper($letter, 'UTF-8')
            : strtoupper($letter);

        if (function_exists('mb_strlen') ? mb_strlen($initials, 'UTF-8') >= 2 : strlen($initials) >= 2) break;
    }

    return $initials !== '' ? $initials : 'eyc';
}

function eyc_header_valid_date(?string $value): ?string {
    $value = trim((string)$value);

    if ($value === '' || $value === '0000-00-00') {
        return null;
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
        return null;
    }

    return substr($value, 0, 10);
}

function eyc_header_birthdate_from_db(): ?string {
    $dni = trim((string)($_SESSION['DNI'] ?? ''));

    if ($dni === '') {
        return null;
    }

    if (!empty($_SESSION['eyc_header_birthdate_checked'])) {
        return eyc_header_valid_date($_SESSION['fecha_nacimiento'] ?? null);
    }

    $_SESSION['eyc_header_birthdate_checked'] = true;

    global $conn;
    if (!isset($conn) || !($conn instanceof mysqli)) {
        return null;
    }

    $sql = "
        SELECT clm_tra_fecha_nacimiento
        FROM tb_trabajador
        WHERE clm_tra_dni = ?
          AND clm_tra_fecha_nacimiento IS NOT NULL
          AND clm_tra_fecha_nacimiento <> ''
          AND clm_tra_fecha_nacimiento <> '0000-00-00'
        ORDER BY clm_tra_id DESC
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('s', $dni);
    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }

    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $birthdate = eyc_header_valid_date($row['clm_tra_fecha_nacimiento'] ?? null);
    if ($birthdate !== null) {
        $_SESSION['fecha_nacimiento'] = $birthdate;
    }

    return $birthdate;
}

function eyc_header_user_birthdate(): ?string {
    $sessionKeys = [
        'fecha_nacimiento',
        'fechaNacimiento',
        'clm_tra_fecha_nacimiento',
        'nacimiento',
    ];

    foreach ($sessionKeys as $key) {
        $date = eyc_header_valid_date($_SESSION[$key] ?? null);
        if ($date !== null) {
            return $date;
        }
    }

    return eyc_header_birthdate_from_db();
}

function eyc_header_age_label(): string {
    $birthdate = eyc_header_user_birthdate();

    if ($birthdate === null) {
        return 'No registrada';
    }

    try {
        $tz = new DateTimeZone('America/Lima');
        $birth = new DateTimeImmutable($birthdate, $tz);
        $today = new DateTimeImmutable('today', $tz);

        if ($birth > $today) {
            return 'No registrada';
        }

        $age = $birth->diff($today)->y;
        if ($age < 0 || $age > 120) {
            return 'No registrada';
        }

        return $age . ' años';
    } catch (Throwable $e) {
        return 'No registrada';
    }
}

function eyc_header_photo_data_uri($blob): string {
    if (!is_string($blob) || $blob === '') {
        return '';
    }

    $mime = 'image/jpeg';
    if (function_exists('getimagesizefromstring')) {
        $info = @getimagesizefromstring($blob);
        if (is_array($info) && !empty($info['mime'])) {
            $mime = (string)$info['mime'];
        }
    }

    return 'data:' . $mime . ';base64,' . base64_encode($blob);
}

function eyc_header_sede_name(string $sedeId): string {
    $sessionName = trim((string)($_SESSION['clm_usuarios_sede_nombre'] ?? ''));
    if ($sessionName !== '') {
        return $sessionName;
    }

    if ($sedeId === '') {
        return '';
    }

    global $conn;
    if (!isset($conn) || !($conn instanceof mysqli)) {
        return $sedeId;
    }

    $stmt = $conn->prepare('SELECT clm_sedes_name FROM tb_sedes WHERE clm_sedes_id = ? LIMIT 1');
    if (!$stmt) {
        return $sedeId;
    }

    $stmt->bind_param('i', $sedeId);
    if (!$stmt->execute()) {
        $stmt->close();
        return $sedeId;
    }

    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $name = trim((string)($row['clm_sedes_name'] ?? ''));
    if ($name !== '') {
        $_SESSION['clm_usuarios_sede_nombre'] = $name;
        return $name;
    }

    return $sedeId;
}

function eyc_header_user_photo(string $dni): string {
    $sessionPhoto = trim((string)($_SESSION['eyc_user_photo'] ?? ''));
    if ($sessionPhoto !== '' && preg_match('/^data:image\/[a-zA-Z0-9.+-]+;base64,/', $sessionPhoto)) {
        return $sessionPhoto;
    }

    if ($dni === '' || !empty($_SESSION['eyc_user_photo_checked'])) {
        return '';
    }

    $_SESSION['eyc_user_photo_checked'] = true;

    global $conn;
    if (!isset($conn) || !($conn instanceof mysqli)) {
        return '';
    }

    $sql = "
        SELECT clm_tra_imagen
        FROM tb_trabajador
        WHERE clm_tra_dni = ?
          AND clm_tra_imagen IS NOT NULL
          AND clm_tra_imagen <> ''
        ORDER BY clm_tra_id DESC
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return '';
    }

    $stmt->bind_param('s', $dni);
    if (!$stmt->execute()) {
        $stmt->close();
        return '';
    }

    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $photo = eyc_header_photo_data_uri($row['clm_tra_imagen'] ?? null);
    if ($photo !== '') {
        $_SESSION['eyc_user_photo'] = $photo;
    }

    return $photo;
}
function eyc_header_user_data(): array {
    $displayName = trim((string)($_SESSION['nombre'] ?? ''));
    $username = trim((string)($_SESSION['usuario'] ?? 'Usuario'));

    if ($displayName === '') {
        $displayName = $username;
    }

    $role = trim((string)($_SESSION['web_rol'] ?? 'Usuario'));
    $dni = trim((string)($_SESSION['DNI'] ?? ''));
    $sedeId = trim((string)($_SESSION['clm_usuarios_sede'] ?? ''));
    $sedeNombre = eyc_header_sede_name($sedeId);
    $photo = eyc_header_user_photo($dni);

    if ($photo !== '' && !preg_match('/^data:image\/[a-zA-Z0-9.+-]+;base64,/', $photo)) {
        $photo = '';
    }

    return [
        'display_name' => $displayName,
        'username' => $username,
        'role' => $role !== '' ? $role : 'Usuario',
        'dni' => $dni !== '' ? $dni : 'No registrado',
        'age' => eyc_header_age_label(),
        'sede' => $sedeNombre !== '' ? $sedeNombre : ($sedeId !== '' ? $sedeId : 'No asignada'),
        'initials' => eyc_header_initials($displayName),
        'photo' => $photo,
        'photo_hint' => 'Solicite a administrador su foto de perfil',
    ];
}

function eyc_render_header(array $options = []): void {
    $user = eyc_header_user_data();
    $homeUrl = $options['home_url'] ?? eyc_base_url('index.php');
    $logoutUrl = $options['logout_url'] ?? eyc_base_url('login/logout.php');
    $logoEmpresa = $options['logo_empresa'] ?? eyc_base_url('img/eyc.png');
    $logoSistema = $options['logo_sistema'] ?? eyc_base_url('img/completo.png');
    ?>
    <?php if (empty($GLOBALS['eyc_header_critical_printed'])): $GLOBALS['eyc_header_critical_printed'] = true; ?>
        <style id="eycHeaderCritical">
            .eyc-header {
                color: #f8fbff !important;
                background: radial-gradient(circle at 18% -60%, rgba(37, 166, 223, 0.22), transparent 35%), linear-gradient(90deg, #142232 0%, #1c3043 54%, #22394e 100%) !important;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
                box-shadow: 0 10px 26px rgba(7, 18, 29, 0.18) !important;
            }
            .eyc-header__brand,
            .eyc-header__brand:hover,
            .eyc-user-trigger,
            .eyc-user-trigger:hover,
            .eyc-user-trigger:focus,
            .eyc-user-summary strong {
                color: #fff !important;
            }
            .eyc-header__brand-sub,
            .eyc-user-summary span,
            .eyc-user-chevron {
                color: rgba(232, 240, 248, 0.72) !important;
            }
            .eyc-user-trigger {
                background: rgba(255, 255, 255, 0.07) !important;
                border-color: rgba(255, 255, 255, 0.16) !important;
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.10) !important;
            }
            .eyc-user-trigger:hover,
            .eyc-user-trigger:focus,
            .eyc-user-menu:hover .eyc-user-trigger,
            .eyc-user-menu.is-open .eyc-user-trigger {
                background: rgba(255, 255, 255, 0.13) !important;
                border-color: rgba(255, 255, 255, 0.34) !important;
            }
            .eyc-header__logo-wrap {
                background: rgb(233, 231, 231) !important;
            }
        </style>
    <?php endif; ?>
    <header class="eyc-header" id="eycHeader">
        <div class="eyc-header__inner">
            <a class="eyc-header__brand" href="<?= eyc_header_h($homeUrl) ?>" aria-label="Ir al panel principal">
                <span class="eyc-header__logo-wrap">
                    <img src="<?= eyc_header_h($logoEmpresa) ?>" alt="Norte 360" class="eyc-header__logo-main">
                </span>
                <span class="eyc-header__brand-copy">
                    <img src="<?= eyc_header_h($logoSistema) ?>" alt="Norte 360" class="eyc-header__logo-system">
                    <span class="eyc-header__brand-sub">ERP Operativo de Transporte</span>
                </span>
            </a>

            <div class="eyc-header__actions">
                <div class="eyc-user-menu" data-eyc-user-menu>
                    <button type="button" class="eyc-user-trigger" data-eyc-user-toggle aria-expanded="false" aria-controls="eycUserDropdown">
                        <span class="eyc-user-avatar" aria-hidden="true">
                            <?php if ($user['photo'] !== ''): ?>
                                <img src="<?= eyc_header_h($user['photo']) ?>" alt="">
                            <?php else: ?>
                                <i class="bi bi-person-badge-fill"></i>
                            <?php endif; ?>
                        </span>
                        <span class="eyc-user-summary">
                            <strong><?= eyc_header_h($user['username']) ?></strong>
                            <span><?= eyc_header_h($user['role']) ?></span>
                        </span>
                        <i class="bi bi-chevron-down eyc-user-chevron" aria-hidden="true"></i>
                    </button>

                    <div class="eyc-user-dropdown" id="eycUserDropdown" role="menu">
                        <div class="eyc-user-dropdown__head">
                            <span class="eyc-user-avatar eyc-user-avatar--lg">
                                <?php if ($user['photo'] !== ''): ?>
                                    <img src="<?= eyc_header_h($user['photo']) ?>" alt="Foto de perfil">
                                <?php else: ?>
                                    <?= eyc_header_h($user['initials']) ?>
                                <?php endif; ?>
                            </span>
                            <div>
                                <strong><?= eyc_header_h($user['display_name']) ?></strong>
                                <span>@<?= eyc_header_h($user['username']) ?></span>
                                <?php if ($user['photo'] === ''): ?>
                                    <span class="eyc-user-photo-hint"><?= eyc_header_h($user['photo_hint']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="eyc-user-grid">
                            <div>
                                <span>DNI</span>
                                <strong><?= eyc_header_h($user['dni']) ?></strong>
                            </div>
                            <div>
                                <span>Edad</span>
                                <strong><?= eyc_header_h($user['age']) ?></strong>
                            </div>
                            <div>
                                <span>Rol</span>
                                <strong><?= eyc_header_h($user['role']) ?></strong>
                            </div>
                            <div>
                                <span>Sede</span>
                                <strong><?= eyc_header_h($user['sede']) ?></strong>
                            </div>
                        </div>

                        <div class="eyc-user-dropdown__foot">
                            <a href="<?= eyc_header_h($logoutUrl) ?>" class="eyc-logout-link">
                                <i class="bi bi-box-arrow-right"></i>
                                <span>Cerrar sesión</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <?php
}