<?php
declare(strict_types=1);

/**
 * Branding and asset URLs: logo, favicon, placeholders, mobile-app.css loader.
 * Used by public pages, dashboard layout, and emails (app_email_logo_url).
 */

require_once dirname(__DIR__) . '/config/app.php';

function app_logo_relative_path(): string
{
    return 'assets/logo-icon.png';
}

function app_logo_url(): string
{
    $rel = app_logo_relative_path();
    $abs = dirname(__DIR__) . '/' . $rel;
    $v = is_file($abs) ? (string) filemtime($abs) : (string) time();

    return asset_url($rel) . '?v=' . rawurlencode($v);
}

/** Public CDN URL for logo in transactional emails (must be HTTPS and internet-reachable). */
function app_email_logo_url(): string
{
    $custom = trim((string) env_value('EMAIL_LOGO_URL', ''));
    if ($custom !== '') {
        return $custom;
    }

    return 'https://ik.imagekit.io/Uespak/logo-icon.png';
}

function app_favicon_tags(): string
{
    $url = htmlspecialchars(app_logo_url(), ENT_QUOTES, 'UTF-8');

    return '<link rel="icon" type="image/png" href="' . $url . '">' . "\n"
        . '  <link rel="apple-touch-icon" href="' . $url . '">';
}

/**
 * Site logo image (logo-icon.png) for headers, sidebars, footers.
 */
function mobile_app_css_tag(): string
{
    $path = dirname(__DIR__) . '/assets/css/mobile-app.css';
    $v = is_file($path) ? (string) filemtime($path) : (string) time();

    return '<link rel="stylesheet" href="' . htmlspecialchars(asset_url('assets/css/mobile-app.css'), ENT_QUOTES, 'UTF-8') . '?v=' . urlencode($v) . '">';
}

function app_logo_img(string $class = 'app-logo', int $width = 40, int $height = 40, string $alt = 'Donate Now'): string
{
    $url = htmlspecialchars(app_logo_url(), ENT_QUOTES, 'UTF-8');
    $classEsc = htmlspecialchars($class, ENT_QUOTES, 'UTF-8');
    $altEsc = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');

    return '<img class="' . $classEsc . '" src="' . $url . '" width="' . $width . '" height="' . $height . '" alt="' . $altEsc . '" decoding="async">';
}

function image_or_placeholder(?string $imageUrl, string $type = 'campaign'): string
{
    if (!empty($imageUrl)) {
        return resolve_url($imageUrl);
    }

    $placeholders = [
        'campaign' => 'https://ik.imagekit.io/demo/img/image1.jpg',
        'profile' => 'https://ik.imagekit.io/demo/default-image.jpg',
        'hero' => 'https://ik.imagekit.io/demo/medium_cafe_B1iTdD0C.jpg',
    ];

    return $placeholders[$type] ?? $placeholders['campaign'];
}

function active_nav_class(string $path): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    return str_contains($uri, $path) ? 'active' : '';
}

/** Inline SVG for dashboard sidebar (no icon fonts). */
function dn_nav_icon(string $name): string
{
    $s = 'width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"';
    switch ($name) {
        case 'home':
            return '<svg ' . $s . '><path d="M3 10.5 12 3l9 7.5"/><path d="M5 10v10a1 1 0 0 0 1 1h4v-7h4v7h4a1 1 0 0 0 1-1V10"/></svg>';
        case 'users':
            return '<svg ' . $s . '><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>';
        case 'shield':
            return '<svg ' . $s . '><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>';
        case 'megaphone':
            return '<svg ' . $s . '><path d="m3 11 18-5v12L3 13v-2z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>';
        case 'heart':
            return '<svg ' . $s . '><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1 7.8 7.8 7.8-7.8 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg>';
        case 'currency':
            return '<svg ' . $s . '><circle cx="12" cy="12" r="10"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 18V6"/></svg>';
        case 'flag':
            return '<svg ' . $s . '><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" x2="4" y1="22" y2="15"/></svg>';
        case 'chart':
            return '<svg ' . $s . '><path d="M3 3v18h18"/><path d="m18 17-5-5-4 4-3-3"/></svg>';
        case 'bell':
            return '<svg ' . $s . '><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>';
        case 'user':
            return '<svg ' . $s . '><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
        case 'logout':
            return '<svg ' . $s . '><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>';
        case 'search':
            return '<svg ' . $s . '><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>';
        case 'compass':
            return '<svg ' . $s . '><circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/></svg>';
        case 'folder':
            return '<svg ' . $s . '><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>';
        case 'plus':
            return '<svg ' . $s . '><path d="M5 12h14"/><path d="M12 5v14"/></svg>';
        case 'wallet':
            return '<svg ' . $s . '><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/></svg>';
        case 'clipboard':
            return '<svg ' . $s . '><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/></svg>';
        case 'handshake':
            return '<svg ' . $s . '><path d="m11 17 2 2a1.74 1.74 0 1 0 3-1.5l-3-3.5"/><path d="m16 16 2-2a1.74 1.74 0 1 0-3-1.5l-3 3.5"/><path d="m8 8 2 2a1.74 1.74 0 1 1-1.5 3L5 10"/><path d="m7 13 2-2a1.74 1.74 0 1 1-1.5-3L5 10"/></svg>';
        case 'close':
            return '<svg ' . $s . '><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>';
        case 'menu':
            return '<svg ' . $s . '><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="18" y2="18"/></svg>';
        case 'mail':
            return '<svg ' . $s . '><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>';
        case 'file-text':
            return '<svg ' . $s . '><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>';
        default:
            return '<svg ' . $s . '><circle cx="12" cy="12" r="3"/></svg>';
    }
}

/** Status pill for donations, NGOs, reports, etc. */
function dash_status_badge(string $status): string
{
    $raw = trim($status);
    $key = strtolower(str_replace([' ', '-'], ['_', '_'], $raw));
    $label = htmlspecialchars($raw, ENT_QUOTES, 'UTF-8');
    $attr = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
    return '<span class="dn-badge" data-status="' . $attr . '">' . $label . '</span>';
}