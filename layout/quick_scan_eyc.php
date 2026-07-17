<?php
if (!defined('eyc_LAYOUT')) {
    exit('Acceso no permitido.');
}

require_once __DIR__ . '/assets_eyc.php';

if (!function_exists('eyc_quick_scan_allowed')) {
    function eyc_quick_scan_allowed(): bool {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        if (($_SESSION['web_rol'] ?? '') === 'Admin') {
            return true;
        }

        $permisos = $_SESSION['permisos'] ?? [];
        if ($permisos === 'all') {
            return true;
        }

        $permisos = array_map('intval', (array)$permisos);
        return in_array(3, $permisos, true);
    }
}

if (!function_exists('eyc_render_quick_scan')) {
    function eyc_render_quick_scan(): void {
        static $rendered = false;

        if ($rendered || !eyc_quick_scan_allowed()) {
            return;
        }

        $rendered = true;
        $endpoint = htmlspecialchars(eyc_base_url('01_almacen/quick_scan_api.php'), ENT_QUOTES, 'UTF-8');
        $imageEndpoint = htmlspecialchars(eyc_base_url('01_almacen/scanner.php?img_prod='), ENT_QUOTES, 'UTF-8');
        $barcodeLogo = htmlspecialchars(eyc_base_url('img/completo2.png'), ENT_QUOTES, 'UTF-8');

        echo '
            <link rel="stylesheet" href="' . eyc_asset('assets/css/barcode_eyc.css') . '">
            <link rel="stylesheet" href="' . eyc_asset('assets/css/quick_scan_eyc.css') . '">
            <div class="eyc-quick-scan" id="eycQuickScanModal" aria-hidden="true" data-endpoint="' . $endpoint . '" data-image-endpoint="' . $imageEndpoint . '" data-barcode-logo="' . $barcodeLogo . '">
                <div class="eyc-quick-scan__backdrop" data-eyc-qs-close></div>
                <section class="eyc-quick-scan__panel" role="dialog" aria-modal="true" aria-labelledby="eycQuickScanTitle">
                    <header class="eyc-quick-scan__header">
                        <div class="eyc-quick-scan__mark">
                            <i class="bi bi-upc-scan"></i>
                        </div>
                        <div>
                            <p class="eyc-quick-scan__eyebrow">Lector rapido</p>
                            <h2 id="eycQuickScanTitle">Consultar producto</h2>
                        </div>
                        <button class="eyc-quick-scan__close" type="button" aria-label="Cerrar lector" data-eyc-qs-close>
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </header>

                    <form class="eyc-quick-scan__form" id="eycQuickScanForm" autocomplete="off">
                        <label class="eyc-quick-scan__label" for="eycQuickScanInput">Codigo o etiqueta</label>
                        <div class="eyc-quick-scan__input-wrap">
                            <i class="bi bi-search"></i>
                            <input id="eycQuickScanInput" name="codigo" type="text" inputmode="text" autocomplete="off" placeholder="Escanea o escribe el codigo..." spellcheck="false">
                            <button type="submit">
                                <i class="bi bi-arrow-return-left"></i>
                                <span>Consultar</span>
                            </button>
                        </div>
                        <div class="eyc-quick-scan__hint">
                            <span><kbd>F2</kbd> abre este lector</span>
                            <span><kbd>Enter</kbd> consulta el producto</span>
                        </div>
                    </form>

                    <div class="eyc-quick-scan__status" id="eycQuickScanStatus" role="status" aria-live="polite">
                        Listo para escanear.
                    </div>

                    <div class="eyc-quick-scan__result" id="eycQuickScanResult"></div>
                </section>
            </div>
            <script src="' . eyc_asset('assets/js/barcode_eyc.js') . '"></script>
            <script src="' . eyc_asset('assets/js/quick_scan_eyc.js') . '"></script>
        ';
    }
}
