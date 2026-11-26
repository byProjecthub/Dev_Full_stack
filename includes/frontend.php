<?php
declare(strict_types=1);

/** Shared presentation helpers; no database, session or API configuration. */
function vueports_url(string $path = 'index.php'): string
{
    static $base = null;
    if ($base === null) {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $scriptDir = str_replace('\\', '/', dirname($script));
        if (in_array(basename($scriptDir), ['client', 'admin', 'legal', 'blog'], true)) {
            $scriptDir = dirname($scriptDir);
        }
        $base = ($scriptDir === '/' || $scriptDir === '.') ? '' : rtrim($scriptDir, '/');
    }
    return $base . '/' . ltrim($path, '/');
}

function vueports_brand(): string
{
    return '<svg class="vp-brand-mark" viewBox="0 0 36 42" aria-hidden="true"><path d="M2 3h8l10 27L30 3h5L22 39h-6Z" fill="#c8f58a"/><path d="M29 3h6L22 39h-6Z" fill="#87b9f8"/></svg><span class="vp-brand-name">Vueports<small>SOLUTIONS</small></span>';
}
