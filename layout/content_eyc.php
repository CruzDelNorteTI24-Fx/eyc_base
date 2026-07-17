<?php
if (!defined('eyc_LAYOUT')) {
    exit('Acceso no permitido.');
}

function eyc_content_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function eyc_render_content_separator(string $position = 'section', array $options = []): void {
    $allowedPositions = ['top', 'bottom', 'section'];
    $position = in_array($position, $allowedPositions, true) ? $position : 'section';

    $extraClass = trim((string)($options['class'] ?? ''));
    $label = trim((string)($options['label'] ?? ''));
    $classes = 'eyc-content-separator eyc-content-separator--' . $position;

    if ($extraClass !== '') {
        $classes .= ' ' . $extraClass;
    }

    if ($label !== '') {
        echo '<div class="eyc-content-separator-wrap eyc-content-separator-wrap--'.eyc_content_h($position).'">';
        echo '<hr class="'.eyc_content_h($classes).'" aria-hidden="true">';
        echo '<span>'.eyc_content_h($label).'</span>';
        echo '<hr class="'.eyc_content_h($classes).'" aria-hidden="true">';
        echo '</div>';
        return;
    }

    echo '<hr class="'.eyc_content_h($classes).'" aria-hidden="true">';
}