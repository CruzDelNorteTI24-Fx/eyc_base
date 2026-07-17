<?php
ob_start();
session_start();
date_default_timezone_set('America/Lima');

function eyc_note_json(array $payload, int $status = 200): void {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function eyc_note_fail(string $message, int $status = 400): void {
    eyc_note_json(['ok' => false, 'message' => $message], $status);
}

function eyc_note_has_access(): bool {
    if (!isset($_SESSION['usuario'])) {
        return false;
    }

    if (($_SESSION['web_rol'] ?? '') === 'Admin') {
        return true;
    }

    $permisos = $_SESSION['permisos'] ?? [];

    if ($permisos === 'all') {
        return true;
    }

    if (!is_array($permisos)) {
        return false;
    }

    $ids = array_map('intval', $permisos);
    return in_array(3, $ids, true) || in_array(9, $ids, true);
}

function eyc_note_text($value, string $fallback = ''): string {
    $text = trim((string)($value ?? ''));
    return $text !== '' ? $text : $fallback;
}

function eyc_note_number($value, int $decimals = 4): string {
    $number = (float)str_replace(',', '.', (string)($value ?? 0));
    $text = number_format($number, $decimals, '.', '');
    return rtrim(rtrim($text, '0'), '.') ?: '0';
}

function eyc_note_money($value, int $decimals = 4): string {
    $number = (float)str_replace(',', '.', (string)($value ?? 0));
    return number_format($number, $decimals, '.', '');
}

function eyc_note_parts($datetime): array {
    $ts = strtotime((string)$datetime);

    if (!$ts) {
        $ts = time();
    }

    return [
        'fecha' => date('Y-m-d', $ts),
        'hora' => date('H:i:s', $ts),
        'fecha_label' => date('d/m/Y', $ts),
    ];
}

function eyc_note_safe_filename(string $value): string {
    $value = preg_replace('/[^A-Za-z0-9._-]+/', '_', trim($value));
    return trim($value, '_') ?: 'nota';
}

function eyc_note_bind(mysqli_stmt $stmt, string $types, array &$params): void {
    if ($types === '') {
        return;
    }

    $refs = [];
    foreach ($params as $key => &$value) {
        $refs[$key] = &$value;
    }

    array_unshift($refs, $types);
    call_user_func_array([$stmt, 'bind_param'], $refs);
}

function eyc_note_fetch_one(mysqli $conn, string $sql, string $types = '', array $params = []): ?array {
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new RuntimeException('No se pudo preparar la consulta.');
    }

    if ($types !== '') {
        eyc_note_bind($stmt, $types, $params);
    }

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new RuntimeException($error ?: 'No se pudo ejecutar la consulta.');
    }

    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $row ?: null;
}

function eyc_note_fetch_all(mysqli $conn, string $sql, string $types = '', array $params = []): array {
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new RuntimeException('No se pudo preparar la consulta.');
    }

    if ($types !== '') {
        eyc_note_bind($stmt, $types, $params);
    }

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new RuntimeException($error ?: 'No se pudo ejecutar la consulta.');
    }

    $result = $stmt->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();

    return $rows;
}

if (!isset($_SESSION['usuario'])) {
    eyc_note_fail('Sesion no iniciada.', 401);
}

if (!eyc_note_has_access()) {
    eyc_note_fail('No tienes permiso para generar esta nota.', 403);
}

define('ACCESS_GRANTED', true);
require_once __DIR__ . '/../.c0nn3ct/db_secure.php';

if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    eyc_note_fail('No se pudo conectar a la base de datos.', 500);
}

$conn->set_charset('utf8mb4');

try {
    $idNota = (int)($_GET['id_nota'] ?? 0);
    $idMovimiento = (int)($_GET['id_movimiento'] ?? 0);

    if ($idNota <= 0 && $idMovimiento > 0) {
        $mov = eyc_note_fetch_one(
            $conn,
            "SELECT clm_alm_mov_idNOTA FROM tb_alm_movimientos WHERE clm_alm_mov_id = ? LIMIT 1",
            'i',
            [$idMovimiento]
        );

        $idNota = (int)($mov['clm_alm_mov_idNOTA'] ?? 0);
    }

    if ($idNota <= 0) {
        eyc_note_fail('No se encontro una nota valida para descargar.');
    }

    $nota = eyc_note_fetch_one(
        $conn,
        "
        SELECT
            ns.clm_nota_id,
            UPPER(TRIM(COALESCE(ns.clm_nota_serie, ''))) AS serie,
            ns.clm_nota_corr,
            COALESCE(
                NULLIF(TRIM(CAST(ns.clm_nota_sco AS CHAR)), ''),
                CONCAT(COALESCE(ns.clm_nota_serie, ''), '-', LPAD(COALESCE(ns.clm_nota_corr, 0), 4, '0'))
            ) AS nota_codigo,
            ns.clm_nota_fecha,
            ns.clm_nota_responsable,
            ns.clm_nota_DNI,
            ns.clm_nota_modulo,
            ns.clm_nota_motivo,
            ns.clm_nota_espacio,
            ns.clm_nota_proveedor,
            ns.clm_nota_placa,
            p.clm_placas_BUS AS bus,
            p.clm_placas_PLACA AS placa,
            CASE
                WHEN p.clm_placas_id IS NOT NULL
                    AND TRIM(COALESCE(p.clm_placas_BUS, '')) <> ''
                    AND TRIM(COALESCE(p.clm_placas_PLACA, '')) <> ''
                THEN CONCAT(TRIM(p.clm_placas_BUS), ' (', TRIM(p.clm_placas_PLACA), ')')
                WHEN p.clm_placas_id IS NOT NULL AND TRIM(COALESCE(p.clm_placas_BUS, '')) <> ''
                THEN TRIM(p.clm_placas_BUS)
                WHEN p.clm_placas_id IS NOT NULL AND TRIM(COALESCE(p.clm_placas_PLACA, '')) <> ''
                THEN TRIM(p.clm_placas_PLACA)
                ELSE ''
            END AS unidad_label
        FROM tb_notas_salida ns
        LEFT JOIN tb_placas p ON ns.clm_nota_placa = p.clm_placas_id
        WHERE ns.clm_nota_id = ?
        LIMIT 1
        ",
        'i',
        [$idNota]
    );

    if (!$nota) {
        eyc_note_fail('No se encontro la nota solicitada.', 404);
    }

    $series = (string)$nota['serie'];
    $allowedSeries = ['NS', 'NE', 'CM', 'AB'];

    if (!in_array($series, $allowedSeries, true)) {
        eyc_note_fail('La serie de esta nota no tiene formato PDF configurado.');
    }

    $movimientos = eyc_note_fetch_all(
        $conn,
        "
        SELECT
            m.clm_alm_mov_id,
            m.clm_alm_mov_itmtable,
            m.clm_alm_mov_TIPO,
            m.clm_alm_mov_cantidad,
            m.clm_alm_mov_preciounitario,
            m.clm_alm_mov_monto,
            m.clm_mov_factura,
            m.clm_alm_mov_OBSERVACION,
            p.clm_alm_producto_codigo,
            p.clm_alm_producto_NOMBRE,
            p.clm_alm_producto_unidad,
            c.clm_alm_categoria_DESCRIPCION
        FROM tb_alm_movimientos m
        JOIN tb_alm_producto p ON p.clm_alm_producto_id = m.clm_alm_mov_idPRODUCTO
        LEFT JOIN tb_alm_categoria c ON c.clm_alm_categoria_id = p.clm_alm_producto_idCATEGORIA
        WHERE m.clm_alm_mov_idNOTA = ?
        ORDER BY
            CAST(NULLIF(m.clm_alm_mov_itmtable, '') AS UNSIGNED) ASC,
            m.clm_alm_mov_id ASC
        ",
        'i',
        [$idNota]
    );

    $parts = eyc_note_parts($nota['clm_nota_fecha'] ?? null);
    $products = [];
    $total = 0.0;
    $docRef = '';
    $ruc = '20403002101';

    foreach ($movimientos as $mov) {
        $codigo = eyc_note_text($mov['clm_alm_producto_codigo'] ?? '', 'S/C');
        $nombre = eyc_note_text($mov['clm_alm_producto_NOMBRE'] ?? '', 'Producto sin nombre');
        $unidad = eyc_note_text($mov['clm_alm_producto_unidad'] ?? '');
        $categoria = eyc_note_text($mov['clm_alm_categoria_DESCRIPCION'] ?? '');
        $description = '(' . $codigo . ') ' . $nombre . ($unidad !== '' ? ' - ' . $unidad : '');
        $amount = (float)str_replace(',', '.', (string)($mov['clm_alm_mov_monto'] ?? 0));

        $products[] = [
            'qty' => eyc_note_number($mov['clm_alm_mov_cantidad'] ?? 0),
            'unit' => $unidad,
            'code' => $codigo,
            'category' => $categoria,
            'name' => $nombre,
            'description' => $description,
            'unitPrice' => eyc_note_money($mov['clm_alm_mov_preciounitario'] ?? 0),
            'amount' => eyc_note_money($amount),
            'movementType' => eyc_note_text($mov['clm_alm_mov_TIPO'] ?? ''),
            'observation' => eyc_note_text($mov['clm_alm_mov_OBSERVACION'] ?? ''),
        ];

        $total += $amount;

        if ($docRef === '') {
            $docRef = eyc_note_text($mov['clm_mov_factura'] ?? '');
        }

    }

    if (!$products) {
        $products[] = [
            'qty' => '0',
            'unit' => '',
            'code' => '',
            'category' => '',
            'name' => 'Sin productos registrados',
            'description' => 'Sin productos registrados',
            'unitPrice' => '0.0000',
            'amount' => '0.0000',
            'movementType' => '',
            'observation' => '',
        ];
    }

    $responsable = eyc_note_text($nota['clm_nota_responsable'] ?? '', eyc_note_text($_SESSION['usuario'] ?? '', '-'));
    $dni = eyc_note_text($nota['clm_nota_DNI'] ?? '', eyc_note_text($_SESSION['DNI'] ?? '', '-'));
    $codigoNota = eyc_note_text($nota['nota_codigo'] ?? '', $series . '-' . str_pad((string)($nota['clm_nota_corr'] ?? '0'), 4, '0', STR_PAD_LEFT));
    $space = eyc_note_text($nota['clm_nota_espacio'] ?? '', '-');
    $provider = eyc_note_text($nota['clm_nota_proveedor'] ?? '', '-');
    $module = eyc_note_text($nota['clm_nota_modulo'] ?? '', $series === 'CM' || $series === 'AB' ? 'Combustible' : 'Almacen');
    $reason = eyc_note_text($nota['clm_nota_motivo'] ?? '', '-');
    $unitText = eyc_note_text($nota['unidad_label'] ?? '', eyc_note_text($nota['bus'] ?? '', eyc_note_text($nota['placa'] ?? '', '-')));
    $fileName = eyc_note_safe_filename('nota_' . strtolower($series) . '_' . $codigoNota) . '.pdf';

    $noteData = [
        'id' => (int)$nota['clm_nota_id'],
        'series' => $series,
        'correlativo' => (string)($nota['clm_nota_corr'] ?? ''),
        'notaCodigo' => $codigoNota,
        'ruc' => $ruc,
        'fecha' => $parts['fecha'],
        'hora' => $parts['hora'],
        'impreso' => date('d/m/Y H:i:s'),
        'module' => $module,
        'space' => $space,
        'provider' => $provider,
        'documentRef' => $docRef,
        'unitText' => $unitText,
        'actor' => $provider,
        'reason' => $reason,
        'responsible' => $responsable,
        'dni' => $dni,
        'products' => $products,
        'total' => 'S/. ' . number_format($total, 4, '.', ''),
        'footerLabel' => 'Eyc',
        'fileName' => $fileName,
    ];

    if ($series === 'NS') {
        $noteData['actorLabel'] = 'Entregado a';
    } elseif ($series === 'CM') {
        $noteData['actorLabel'] = 'Conductor';
    } elseif ($series === 'AB') {
        $noteData['providerLabel'] = 'Suministrador';
    } elseif ($series === 'NE') {
        $noteData['providerLabel'] = 'Proveedor';
    }

    eyc_note_json([
        'ok' => true,
        'id_nota' => (int)$nota['clm_nota_id'],
        'id_movimiento' => $idMovimiento,
        'series' => $series,
        'nota_codigo' => $codigoNota,
        'noteData' => $noteData,
    ]);
} catch (Throwable $e) {
    eyc_note_fail($e->getMessage() ?: 'No se pudo preparar la nota para PDF.', 500);
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
