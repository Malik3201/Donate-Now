<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';

function location_default_lat(): float
{
    return 31.5204;
}

function location_default_lng(): float
{
    return 74.3587;
}

function location_has_coords($lat, $lng): bool
{
    if ($lat === null || $lng === null || $lat === '' || $lng === '') {
        return false;
    }
    $latF = (float) $lat;
    $lngF = (float) $lng;

    return $latF >= -90 && $latF <= 90 && $lngF >= -180 && $lngF <= 180 && !($latF === 0.0 && $lngF === 0.0);
}

/**
 * @return array{lat: ?float, lng: ?float, label: ?string}
 */
function location_coords_from_post(string $prefix = ''): array
{
    $p = $prefix !== '' ? rtrim($prefix, '_') . '_' : '';
    $latRaw = trim((string) ($_POST[$p . 'latitude'] ?? ''));
    $lngRaw = trim((string) ($_POST[$p . 'longitude'] ?? ''));
    $label = trim((string) ($_POST[$p . 'location_label'] ?? ''));

    if ($latRaw === '' && $lngRaw === '') {
        return ['lat' => null, 'lng' => null, 'label' => $label !== '' ? $label : null];
    }

    $lat = (float) $latRaw;
    $lng = (float) $lngRaw;
    if (!location_has_coords($lat, $lng)) {
        return ['lat' => null, 'lng' => null, 'label' => $label !== '' ? $label : null];
    }

    return ['lat' => $lat, 'lng' => $lng, 'label' => $label !== '' ? $label : null];
}

function location_map_assets_html(): string
{
    static $printed = false;
    if ($printed) {
        return '';
    }
    $printed = true;

    $cssPath = dirname(__DIR__) . '/assets/css/location-map.css';
    $jsPath = dirname(__DIR__) . '/assets/js/location-map.js';
    $cssV = is_file($cssPath) ? (string) filemtime($cssPath) : (string) time();
    $jsV = is_file($jsPath) ? (string) filemtime($jsPath) : (string) time();

    return '
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<link rel="stylesheet" href="' . htmlspecialchars(asset_url('assets/css/location-map.css'), ENT_QUOTES, 'UTF-8') . '?v=' . urlencode($cssV) . '">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="' . htmlspecialchars(asset_url('assets/js/location-map.js'), ENT_QUOTES, 'UTF-8') . '?v=' . urlencode($jsV) . '" defer></script>';
}

function render_location_map_display(
    $lat,
    $lng,
    string $title = 'Location',
    ?string $address = null,
    string $mapId = 'dn-map-display'
): string {
    if (!location_has_coords($lat, $lng)) {
        return '';
    }

    $latF = (float) $lat;
    $lngF = (float) $lng;
    $assets = location_map_assets_html();

    $addrHtml = '';
    if ($address !== null && trim($address) !== '') {
        $addrHtml = '<p class="dn-location-map__address">' . htmlspecialchars(trim($address), ENT_QUOTES, 'UTF-8') . '</p>';
    }

    $id = htmlspecialchars($mapId, ENT_QUOTES, 'UTF-8');
    $titleEsc = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

    return $assets . '
<section class="dn-location-map-section glass-card" aria-label="' . $titleEsc . '">
  <h2 class="dn-location-map-section__title">' . $titleEsc . '</h2>
  ' . $addrHtml . '
  <div id="' . $id . '" class="dn-location-map dn-location-map--display"
    data-lat="' . htmlspecialchars((string) $latF, ENT_QUOTES, 'UTF-8') . '"
    data-lng="' . htmlspecialchars((string) $lngF, ENT_QUOTES, 'UTF-8') . '"
    data-readonly="1"></div>
</section>';
}

function render_location_map_picker(
    $lat,
    $lng,
    ?string $address = null,
    ?string $locationLabel = null,
    string $prefix = '',
    bool $optional = false,
    bool $showAddressField = true,
    bool $showLocationLabel = false,
    string $pickerId = 'dn-map-picker'
): string {
    $p = $prefix !== '' ? rtrim($prefix, '_') . '_' : '';
    $latF = location_has_coords($lat, $lng) ? (float) $lat : location_default_lat();
    $lngF = location_has_coords($lat, $lng) ? (float) $lng : location_default_lng();
    $hasExisting = location_has_coords($lat, $lng);
    $optNote = $optional ? ' <span class="dn-location-map__optional">(optional)</span>' : '';

    $assets = location_map_assets_html();

    $labelField = '';
    if ($showLocationLabel) {
        $labelField = '
  <div class="form-group">
    <label for="' . htmlspecialchars($p . 'location_label', ENT_QUOTES, 'UTF-8') . '">Location name (optional)</label>
    <input type="text" id="' . htmlspecialchars($p . 'location_label', ENT_QUOTES, 'UTF-8') . '" name="' . htmlspecialchars($p . 'location_label', ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars((string) ($locationLabel ?? ''), ENT_QUOTES, 'UTF-8') . '" placeholder="e.g. Food distribution point">
  </div>';
    }

    $addressField = '';
    if ($showAddressField) {
        $addressField = '
  <div class="form-group" style="margin-top:0.65rem;">
    <label for="map_address_display">Street address</label>
    <input type="text" name="address" id="map_address_display" value="' . htmlspecialchars((string) ($address ?? ''), ENT_QUOTES, 'UTF-8') . '" placeholder="Office or contact address">
  </div>';
    }

    $pickerEsc = htmlspecialchars($pickerId, ENT_QUOTES, 'UTF-8');
    $latName = htmlspecialchars($p . 'latitude', ENT_QUOTES, 'UTF-8');
    $lngName = htmlspecialchars($p . 'longitude', ENT_QUOTES, 'UTF-8');
    $latId = htmlspecialchars($p . 'latitude', ENT_QUOTES, 'UTF-8');
    $lngId = htmlspecialchars($p . 'longitude', ENT_QUOTES, 'UTF-8');
    $latVal = $hasExisting ? htmlspecialchars((string) $latF, ENT_QUOTES, 'UTF-8') : '';
    $lngVal = $hasExisting ? htmlspecialchars((string) $lngF, ENT_QUOTES, 'UTF-8') : '';

    return $assets . '
<div class="dn-location-map-picker">
  <label class="dn-location-map-picker__label">Set location on map' . $optNote . '</label>
  <p class="help-text">Search, click the map, or drag the pin.</p>
  ' . $labelField . '
  <div class="dn-location-map-search">
    <input type="search" class="dn-location-map-search__input" placeholder="Search city, area, or address…" autocomplete="off" aria-label="Search location">
    <button type="button" class="outline-button dn-location-map-search__btn">Search</button>
  </div>
  <div id="' . $pickerEsc . '" class="dn-location-map dn-location-map--picker"
    data-lat="' . htmlspecialchars((string) $latF, ENT_QUOTES, 'UTF-8') . '"
    data-lng="' . htmlspecialchars((string) $lngF, ENT_QUOTES, 'UTF-8') . '"
    data-has-coords="' . ($hasExisting ? '1' : '0') . '"
    data-readonly="0"></div>
  <input type="hidden" name="' . $latName . '" id="' . $latId . '" value="' . $latVal . '">
  <input type="hidden" name="' . $lngName . '" id="' . $lngId . '" value="' . $lngVal . '">
  ' . $addressField . '
  <p class="dn-location-map-coords help-text" aria-live="polite"></p>
</div>';
}

function ngo_detail_page_url(string $role, int $ngoProfileId): string
{
    $base = match ($role) {
        'admin' => '/admin/ngo_detail.php',
        'donor' => '/donor/ngo_detail.php',
        'volunteer' => '/volunteer/ngo_detail.php',
        default => '',
    };
    if ($base === '') {
        return '';
    }

    return APP_URL . $base . '?id=' . $ngoProfileId;
}

function render_campaign_location_maps(array $row): string
{
    $parts = [];
    $campLat = $row['latitude'] ?? null;
    $campLng = $row['longitude'] ?? null;
    if (location_has_coords($campLat, $campLng)) {
        $campTitle = trim((string) ($row['location_label'] ?? ''));
        $parts[] = render_location_map_display(
            $campLat,
            $campLng,
            $campTitle !== '' ? $campTitle : 'Campaign location',
            null,
            'dn-map-campaign'
        );
    }

    $ngoLat = $row['ngo_latitude'] ?? null;
    $ngoLng = $row['ngo_longitude'] ?? null;
    $ngoAddress = isset($row['ngo_address']) ? (string) $row['ngo_address'] : null;
    if (location_has_coords($ngoLat, $ngoLng)) {
        $ngoName = trim((string) ($row['ngo_name'] ?? ''));
        $parts[] = render_location_map_display(
            $ngoLat,
            $ngoLng,
            $ngoName !== '' ? $ngoName . ' headquarters' : 'NGO headquarters',
            $ngoAddress,
            'dn-map-ngo'
        );
    }

    if ($parts === []) {
        return '';
    }

    return '<div class="dn-location-maps-stack">' . implode('', $parts) . '</div>';
}
