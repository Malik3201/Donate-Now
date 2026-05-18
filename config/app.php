<?php
declare(strict_types=1);

/**
 * Application bootstrap: sessions + .env loading + universal base URL.
 * APP_URL auto-detects host, scheme, and subfolder unless .env overrides safely.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Read a key from getenv() or from the project root `.env` file. */
function env_value(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    if ($value !== false) {
        return $value;
    }

    static $loaded = false;
    if (!$loaded) {
        $envFile = dirname(__DIR__) . '/.env';
        if (is_file($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            foreach ($lines as $line) {
                if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
                    continue;
                }
                [$k, $v] = explode('=', $line, 2);
                $k = trim($k);
                $v = trim($v, " \t\n\r\0\x0B\"'");
                putenv($k . '=' . $v);
                $_ENV[$k] = $v;
            }
        }
        $loaded = true;
    }

    $value = getenv($key);
    return $value === false ? $default : $value;
}

function app_project_root(): string
{
    return dirname(__DIR__);
}

function app_is_local_host(string $host): bool
{
    $host = strtolower(trim($host));
    if ($host === '') {
        return true;
    }
    if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
        return true;
    }

    return str_ends_with($host, '.localhost') || str_ends_with($host, '.local');
}

function app_detect_scheme(): string
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $forwarded = strtolower(trim(explode(',', (string) $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]));
        if (in_array($forwarded, ['http', 'https'], true)) {
            return $forwarded;
        }
    }

    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return 'https';
    }

    if (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443') {
        return 'https';
    }

    return 'http';
}

function app_normalize_base_path(string $path): string
{
    $path = str_replace('\\', '/', $path);
    $path = '/' . trim($path, '/');

    return $path === '/' ? '' : $path;
}

/**
 * Subdirectory path when the app is not deployed at the web root (e.g. /Donate-Now).
 */
function app_detect_base_path(): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $docRoot = (string) ($_SERVER['DOCUMENT_ROOT'] ?? '');
    $docRootReal = $docRoot !== '' ? realpath($docRoot) : false;
    $projectReal = realpath(app_project_root());

    if ($docRootReal && $projectReal && str_starts_with($projectReal, $docRootReal)) {
        $relative = substr($projectReal, strlen($docRootReal));
        $cached = app_normalize_base_path($relative);

        return $cached;
    }

    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $segments = array_values(array_filter(explode('/', dirname($scriptName)), static fn (string $s): bool => $s !== ''));

    if ($docRootReal) {
        for ($i = count($segments); $i >= 0; $i--) {
            $candidate = $i === 0 ? '' : '/' . implode('/', array_slice($segments, 0, $i));
            $indexPath = $docRootReal . $candidate . '/index.php';
            if (is_file($indexPath)) {
                $cached = app_normalize_base_path($candidate);

                return $cached;
            }
        }
    }

    $cached = '';

    return $cached;
}

function app_join_url(string $baseUrl, string $path = ''): string
{
    $baseUrl = rtrim($baseUrl, '/');
    $path = ltrim(str_replace('\\', '/', $path), '/');

    if ($path === '') {
        return $baseUrl;
    }

    return $baseUrl . '/' . $path;
}

function app_resolve_base_url(): string
{
    static $resolved = null;
    if ($resolved !== null) {
        return $resolved;
    }

    $envUrl = rtrim(trim((string) env_value('APP_URL', '')), '/');
    $hostHeader = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $hostOnly = strtolower(preg_replace('/:\d+$/', '', $hostHeader) ?? $hostHeader);
    $isCli = PHP_SAPI === 'cli' || $hostHeader === '';

    $detectedBasePath = app_detect_base_path();
    $detectedUrl = '';
    if (!$isCli) {
        $detectedUrl = rtrim(app_detect_scheme() . '://' . $hostHeader . $detectedBasePath, '/');
    }

    $forceEnv = filter_var((string) env_value('FORCE_APP_URL', ''), FILTER_VALIDATE_BOOLEAN);

    if ($envUrl !== '') {
        $envHost = strtolower((string) (parse_url($envUrl, PHP_URL_HOST) ?? ''));
        $envIsLocal = app_is_local_host($envHost);
        $runtimeIsLocal = app_is_local_host($hostOnly);

        $useEnv = $forceEnv
            || $isCli
            || ($hostOnly !== '' && $envHost !== '' && $envHost === $hostOnly)
            || ($runtimeIsLocal && $envIsLocal);

        // .env still says localhost but the site is served on a live domain — auto-detect instead.
        if ($useEnv && !$forceEnv && $envIsLocal && !$runtimeIsLocal && $detectedUrl !== '') {
            $useEnv = false;
        }

        if ($useEnv) {
            $resolved = $envUrl;

            return $resolved;
        }
    }

    if ($detectedUrl !== '') {
        $resolved = $detectedUrl;

        return $resolved;
    }

    $resolved = $envUrl !== '' ? $envUrl : 'http://localhost';

    return $resolved;
}

if (!defined('APP_BASE_PATH')) {
    define('APP_BASE_PATH', app_detect_base_path());
}

if (!defined('APP_URL')) {
    define('APP_URL', app_resolve_base_url());
}

/** Web path prefix for this install (empty string at domain root). */
function base_path(): string
{
    return APP_BASE_PATH;
}

/** Absolute application URL for a route or file under the project root. */
function app_url(string $path = ''): string
{
    return app_join_url(APP_URL, $path);
}

/** Alias of app_url(). */
function url(string $path = ''): string
{
    return app_url($path);
}

/** Public asset URL (css, js, images under /assets). */
function asset_url(string $path): string
{
    return app_url($path);
}

/** Full URL of the current request. */
function current_url(): string
{
    $scheme = app_detect_scheme();
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');

    return $scheme . '://' . $host . $uri;
}

/**
 * Turn relative or root-absolute paths into full URLs.
 * Leaves http(s) and protocol-relative external URLs unchanged.
 */
function resolve_url(?string $url): string
{
    if ($url === null) {
        return '';
    }

    $url = trim($url);
    if ($url === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $url)) {
        return $url;
    }

    if (str_starts_with($url, '//')) {
        return app_detect_scheme() . ':' . $url;
    }

    if (str_starts_with($url, '/')) {
        $base = base_path();
        $path = $url;
        if ($base !== '' && !str_starts_with($path, $base . '/') && $path !== $base) {
            $path = $base . $path;
        }

        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');

        return app_detect_scheme() . '://' . $host . $path;
    }

    return app_url($url);
}
