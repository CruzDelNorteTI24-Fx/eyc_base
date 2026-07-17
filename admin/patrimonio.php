<?php
define('eyc_ADMIN_CATALOG', true);
require_once __DIR__ . '/_admin_catalogos.php';

eyc_admin_render_head('Patrimonio');
?>
<?php eyc_render_header(['title' => 'Patrimonio', 'subtitle' => 'Administracion']); ?>
<?php eyc_render_sidebar(); ?>

<main class="main-content eyc-main eyc-main--module eyc-main--compact-access" role="main">
    <div class="eyc-main__inner admin-cat-shell">
        <?php eyc_render_content_separator('top'); ?>

        <section class="admin-cat-hero">
            <div>
                <span class="admin-cat-kicker"><i class="bi bi-gem" aria-hidden="true"></i> Administracion - Maestros</span>
                <h1>Patrimonio</h1>
            </div>
        </section>

        <section class="admin-cat-panel">
            <div class="admin-cat-panel__head">
                <div>
                    <h2>Diseno pendiente</h2>
                </div>
            </div>
            <div class="admin-cat-placeholder">
                <i class="bi bi-tools" aria-hidden="true"></i>
                <strong>Construyending</strong>
            </div>
        </section>

        <?php eyc_render_content_separator('bottom'); ?>
    </div>
</main>

<?php eyc_render_footer(); ?>
<?php eyc_admin_render_close(); ?>
<?php $conn->close(); ?>
