<?php
if (!defined('eyc_LAYOUT')) {
    exit('Acceso no permitido.');
}

require_once __DIR__ . '/assets_eyc.php';

if (!function_exists('eyc_bus_lookup_allowed')) {
    function eyc_bus_lookup_allowed(): bool {
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
        return in_array(10, $permisos, true) || in_array(5, $permisos, true);
    }
}

if (!function_exists('eyc_render_bus_lookup')) {
    function eyc_render_bus_lookup(): void {
        static $rendered = false;

        if ($rendered || !eyc_bus_lookup_allowed()) {
            return;
        }

        $rendered = true;
        $endpoint = htmlspecialchars(eyc_base_url('01_flota/bus_lookup_api.php'), ENT_QUOTES, 'UTF-8');

        echo '
            <link rel="stylesheet" href="' . eyc_asset('assets/css/bus_lookup_eyc.css') . '">

            <button class="eyc-bus-lookup-fab" type="button" aria-label="Consultar unidad" data-eyc-bus-open>
                <span class="eyc-bus-lookup-fab__road"></span>
                <span class="eyc-bus-lookup-fab__icon"><i class="bi bi-bus-front-fill"></i></span>
            </button>

            <div class="eyc-bus-lookup" id="eycBusLookupModal" aria-hidden="true" data-endpoint="' . $endpoint . '">
                <div class="eyc-bus-lookup__backdrop" data-eyc-bus-close></div>
                <section class="eyc-bus-lookup__panel" role="dialog" aria-modal="true" aria-labelledby="eycBusLookupTitle">
                    <header class="eyc-bus-lookup__header">
                        <div class="eyc-bus-lookup__mark">
                            <i class="bi bi-bus-front-fill"></i>
                        </div>
                        <div>
                            <p class="eyc-bus-lookup__eyebrow">Operacion en vivo</p>
                            <h2 id="eycBusLookupTitle" style="background-color: #0000; color: white; padding: 10px;">Consultar unidad</h2>
                        </div>
                        <button class="eyc-bus-lookup__close" type="button" aria-label="Cerrar consulta" data-eyc-bus-close>
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </header>

                    <form class="eyc-bus-lookup__form" id="eycBusLookupForm" autocomplete="off">
                        <label class="eyc-bus-lookup__label" for="eycBusLookupInput">Bus, placa o dueno</label>
                        <div class="eyc-bus-lookup__input-wrap">
                            <i class="bi bi-search"></i>
                            <input id="eycBusLookupInput" name="q" type="text" inputmode="text" autocomplete="off" placeholder="Ej. BUS 158, ABC-321..." spellcheck="false">
                            <button type="submit">
                                <i class="bi bi-arrow-return-left"></i>
                                <span>Consultar</span>
                            </button>
                        </div>
                    </form>

                    <div class="eyc-bus-lookup__status" id="eycBusLookupStatus" role="status" aria-live="polite">
                        Busca una unidad para ver su estado operativo.
                    </div>

                    <div class="eyc-bus-lookup__result" id="eycBusLookupResult"></div>
                </section>
            </div>

            <script src="' . eyc_asset('assets/js/bus_lookup_eyc.js') . '"></script>
        ';
    }
}
