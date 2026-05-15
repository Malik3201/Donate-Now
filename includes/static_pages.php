<?php
declare(strict_types=1);

/**
 * Helpers for pages/about.php, contact.php, privacy-policy.php, terms.php.
 * Usage: static_page_begin($title, $path); ... HTML ... static_page_end();
 */

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/ui_helpers.php';

/** Hero/section background URLs keyed by page slug */
function static_page_images(): array
{
    return [
        'hero' => 'https://www.shutterstock.com/image-photo/volunteer-holding-cardboard-box-donated-600nw-2565173299.jpg',
        'about' => 'https://img.magnific.com/free-photo/donate-sign-charity-campaign_53876-127165.jpg?semt=ais_hybrid&w=1400&q=80',
        'contact' => 'https://images.unsplash.com/photo-1423666639041-f56000c27a9a?auto=format&fit=crop&w=1400&q=80',
        'privacy' => 'https://images.unsplash.com/photo-1563986768609-322da13575f3?auto=format&fit=crop&w=1400&q=80',
        'terms' => 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?auto=format&fit=crop&w=1400&q=80',
        'values' => 'https://images.unsplash.com/photo-1559027615-cd4628902d4a?auto=format&fit=crop&w=1400&q=80',
        'cta' => 'https://images.unsplash.com/photo-1529390079861-591de354faf5?auto=format&fit=crop&w=1400&q=80',
    ];
}

function static_page_begin(string $pageTitle, string $activePath): void
{
    $GLOBALS['isStaticPage'] = true;
    $GLOBALS['staticActivePath'] = $activePath;
    $GLOBALS['pageTitle'] = $pageTitle;
    $GLOBALS['staticImages'] = static_page_images();
    require dirname(__DIR__) . '/includes/public_header.php';
}

function static_page_end(): void
{
    require dirname(__DIR__) . '/includes/public_footer.php';
}

function static_img(string $key): string
{
    $images = $GLOBALS['staticImages'] ?? static_page_images();

    return sanitize((string) ($images[$key] ?? $images['hero']));
}

function static_nav_active(string $path): string
{
    $current = (string) ($GLOBALS['staticActivePath'] ?? '');
    return str_contains($current, $path) ? 'active' : '';
}
