<?php
if (!defined('eyc_LAYOUT')) {
    exit('Acceso no permitido.');
}

if (!function_exists('eyc_base_url')) {
    function eyc_base_url(string $path = ''): string {
        $base = defined('eyc_BASE_URL') ? eyc_BASE_URL : './';
        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('eyc_version')) {
    function eyc_version(): string {
        $versionFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'version_eyc.txt';
        $version = is_file($versionFile) ? trim((string)file_get_contents($versionFile)) : '1.1.10';
        $version = preg_replace('/[^0-9A-Za-z._-]/', '', $version) ?: '1.1.10';

        return $version;
    }
}

if (!function_exists('eyc_asset_url')) {
    function eyc_asset_url(string $path): string {
        $url = eyc_base_url($path);
        $separator = strpos($url, '?') === false ? '?' : '&';

        return $url . $separator . 'v=' . rawurlencode(eyc_version());
    }
}

if (!function_exists('eyc_asset')) {
    function eyc_asset(string $path): string {
        return htmlspecialchars(eyc_asset_url($path), ENT_QUOTES, 'UTF-8');
    }
}