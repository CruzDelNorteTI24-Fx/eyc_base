<?php
define('ACCESS_GRANTED', true);

require_once __DIR__ . '/../layout/security_eyc.php';
eyc_send_security_headers();
eyc_start_secure_session();

function eyc_login_photo_data_uri($blob): string {
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

function eyc_login_redirect_error(string $usuario = '', string $error = '1'): void {
    if ($error === '1') {
        eyc_login_rate_register_failure($usuario);
    }

    header('Location: login.php?error=' . rawurlencode($error));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

$usuario = trim((string)($_POST['usuario'] ?? ''));
$clave = trim((string)($_POST['clave'] ?? ''));
$csrfToken = (string)($_POST['csrf_token'] ?? '');

if (!eyc_verify_csrf($csrfToken, 'login')) {
    eyc_login_redirect_error('', 'csrf');
}

if ($usuario === '' || $clave === '') {
    eyc_login_redirect_error($usuario);
}

$rateStatus = eyc_login_rate_status($usuario);
if (!empty($rateStatus['blocked'])) {
    eyc_login_redirect_error('', 'blocked');
}

require_once __DIR__ . '/../.c0nn3ct/db_secure.php';

$claveHash = hash('sha256', $clave);

$stmt = $conn->prepare("
    SELECT
        u.id_usuario,
        u.usuario,
        u.contrasena,
        u.nombre,
        u.DNI,
        u.clm_usuarios_sede,
        u.web_rol,
        s.clm_sedes_name AS sede_nombre,
        u.clm_tra_imagen AS foto_usuario
    FROM tb_usuarios u
    LEFT JOIN tb_sedes s ON s.clm_sedes_id = u.clm_usuarios_sede
    LEFT JOIN tb_trabajador t ON t.clm_tra_id = (
        SELECT t2.clm_tra_id
        FROM tb_trabajador t2
        WHERE t2.clm_tra_dni = u.DNI
          AND t2.clm_tra_imagen IS NOT NULL
          AND t2.clm_tra_imagen <> ''
        ORDER BY t2.clm_tra_id DESC
        LIMIT 1
    )
    WHERE u.usuario = ?
    LIMIT 1
");

if (!$stmt) {
    eyc_login_redirect_error($usuario);
}

$stmt->bind_param('s', $usuario);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows !== 1) {
    eyc_login_redirect_error($usuario);
}

$fila = $resultado->fetch_assoc();
$claveBd = (string)($fila['contrasena'] ?? '');

if (!hash_equals($claveBd, $claveHash)) {
    eyc_login_redirect_error($usuario);
}

eyc_login_rate_clear($usuario);
$_SESSION = [];
session_regenerate_id(true);

$_SESSION['id_usuario'] = $fila['id_usuario'];
$_SESSION['usuario'] = $fila['usuario'];
$_SESSION['web_rol'] = $fila['web_rol'];
$_SESSION['nombre'] = $fila['nombre'];
$_SESSION['DNI'] = $fila['DNI'];
$_SESSION['clm_usuarios_sede'] = $fila['clm_usuarios_sede'];
$_SESSION['clm_usuarios_sede_nombre'] = trim((string)($fila['sede_nombre'] ?? '')) !== ''
    ? $fila['sede_nombre']
    : $fila['clm_usuarios_sede'];

$_SESSION['eyc_user_photo_checked'] = true;
$fotoPerfil = eyc_login_photo_data_uri($fila['foto_usuario'] ?? null);
if ($fotoPerfil !== '') {
    $_SESSION['eyc_user_photo'] = $fotoPerfil;
} else {
    unset($_SESSION['eyc_user_photo']);
}

if ($fila['web_rol'] === 'Admin') {
    $_SESSION['permisos'] = 'all';
    $_SESSION['vista_redirect'] = 'index.php';
    header('Location: ../index.php');
    exit();
}

$idUsuario = (int)$fila['id_usuario'];
$stmtPermiso = $conn->prepare('SELECT id_modulo, vista_redirect FROM tb_permisos WHERE id_usuario = ?');

if (!$stmtPermiso) {
    header('Location: none_permisos.php');
    exit();
}

$stmtPermiso->bind_param('i', $idUsuario);
$stmtPermiso->execute();
$resPermiso = $stmtPermiso->get_result();

if ($resPermiso->num_rows <= 0) {
    $_SESSION['permisos'] = [];
    $_SESSION['vistas'] = [];
    header('Location: none_permisos.php');
    exit();
}

$permisos = [];
$vistas = [];

while ($permiso = $resPermiso->fetch_assoc()) {
    $permisos[] = $permiso['id_modulo'];
    $vistas[] = $permiso['vista_redirect'];
}

$_SESSION['permisos'] = $permisos;
$_SESSION['vistas'] = $vistas;

foreach ($permisos as $index => $modulo) {
    $vista = $vistas[$index];

    if ($modulo == 1) {
        if ($vista == 'checklist-limpieza') {
            header('Location: ../checklistlimpieza.php');
            exit();
        }
        if ($vista == 'checklist-carro') {
            header('Location: ../checklistcarro.php');
            exit();
        }
    } elseif ($modulo == 6) {
        if ($vista == 'r-gen') {
            header('Location: ../01_contratos/nregrcdn_h.php');
            exit();
        }
        if ($vista == 'e-gen') {
            header('Location: ../01_entrevistas/reentrev.php');
            exit();
        }
    } elseif ($modulo == 5) {
        if ($vista == 'c-limp') {
            header('Location: ../index.php');
            exit();
        }
        if ($vista == 'c-sab') {
            header('Location: ../01_amantenimiento/lista_cheklist.php');
            exit();
        }
        if ($vista == 'c-lalu') {
            header('Location: ../01_amantenimiento/lista_cheklist.php');
            exit();
        }
    } elseif ($modulo == 10) {
        if ($vista == 'f-flotayoperaciones') {
            header('Location: ../index.php');
            exit();
        }
        if ($vista == 'f-placas') {
            header('Location: ../01_flota/gest_plac.php');
            exit();
        }
        if ($vista == 'f-progcond') {
            header('Location: ../01_flota/programacion_condt.php');
            exit();
        }
        if ($vista == 'f-proghor') {
            header('Location: ../01_flota/programacion_horarios.php');
            exit();
        }
    }
}

header('Location: none_permisos.php');
exit();
