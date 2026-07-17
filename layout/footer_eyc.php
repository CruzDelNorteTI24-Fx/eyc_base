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

function eyc_footer_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function eyc_footer_version(): string {
    return eyc_version();
}

function eyc_render_footer(array $options = []): void {
    global $h2bd_img, $h2bd_name;

    $year = date('Y');
    $version = $options['version'] ?? eyc_footer_version();
    $logoUrl = $options['logo_url'] ?? eyc_base_url('img/eyc.png');
    $homeUrl = $options['home_url'] ?? eyc_base_url('index.php');
    $supportUrl = $options['support_url'] ?? 'https://wa.me/51944532822?text=Hola%2C%20quisiera%20hacer%20una%20consulta%20sobre%20eyc.';

    $links = $options['links'] ?? [
        [
            'label' => 'Panel principal',
            'url' => $homeUrl,
            'icon' => 'bi bi-house-door-fill',
        ],
        [
            'label' => 'Soporte',
            'url' => $supportUrl,
            'icon' => 'bi bi-whatsapp',
            'target' => '_blank',
        ],
    ];

    $eggImg = trim((string)($h2bd_img ?? ''));
    $eggName = trim((string)($h2bd_name ?? ''));
    ?>
    <footer class="eyc-footer" role="contentinfo">
        <div class="eyc-footer__inner">
            <div class="eyc-footer__brand">
                <a href="<?= eyc_footer_h($homeUrl) ?>" class="eyc-footer__logo-link" aria-label="Ir al panel principal">
                    <img src="<?= eyc_footer_h($logoUrl) ?>" alt="Eyc" class="eyc-footer__logo">
                </a>
                <div>
                    <strong>eyc Web</strong>
                    <span>ERP Operativo de Transporte</span>
                </div>
            </div>

            <nav class="eyc-footer__links" aria-label="Enlaces del pie de pagina">
                <?php foreach ($links as $link): ?>
                    <?php
                    $target = $link['target'] ?? '';
                    $rel = $target === '_blank' ? 'noopener noreferrer' : '';
                    ?>
                    <a href="<?= eyc_footer_h($link['url'] ?? '#') ?>"
                       <?= $target !== '' ? 'target="'.eyc_footer_h($target).'"' : '' ?>
                       <?= $rel !== '' ? 'rel="'.eyc_footer_h($rel).'"' : '' ?>>
                        <i class="<?= eyc_footer_h($link['icon'] ?? 'bi bi-link-45deg') ?>" aria-hidden="true"></i>
                        <span><?= eyc_footer_h($link['label'] ?? 'Enlace') ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="eyc-footer__meta">
                <span class="eyc-footer__version">
                    <i class="bi bi-tag-fill" aria-hidden="true"></i>
                    Version <?= eyc_footer_h($version) ?>
                </span>
                <span>&copy; <?= eyc_footer_h($year) ?> Eyc. Todos los derechos reservados.</span>
            </div>
        </div>

        <?php if ($eggImg !== '' && $eggName !== ''): ?>
            <div class="eyc-footer__egg" id="eycFooterEgg" hidden>
                <img src="<?= eyc_footer_h($eggImg) ?>" alt="Credito interno">
                <span><?= eyc_footer_h($eggName) ?></span>
            </div>
            <script>
                document.addEventListener('keydown', function (event) {
                    if (!event.ctrlKey || !event.altKey || event.key.toLowerCase() !== 'm') return;

                    const egg = document.getElementById('eycFooterEgg');
                    if (!egg) return;

                    egg.hidden = !egg.hidden;
                });
            </script>
        <?php endif; ?>
    </footer>
    <?php
}