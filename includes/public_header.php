<?php
/**
 * Public site header + CSS stack (home, static pages, public campaign detail).
 * Set $isLandingPage = true on index.php for landing-specific nav/hero behavior.
 */
require_once dirname(__DIR__) . '/includes/ui_helpers.php';
$isLandingPage = $isLandingPage ?? false;
$isStaticPage = $isStaticPage ?? ($GLOBALS['isStaticPage'] ?? false);
$pageTitle = $pageTitle ?? 'Donate Now';
$staticActivePath = (string) ($GLOBALS['staticActivePath'] ?? ($_SERVER['REQUEST_URI'] ?? ''));
$landingCssPath = dirname(__DIR__) . '/assets/css/landing.css';
$landingCssVersion = is_file($landingCssPath) ? (string) filemtime($landingCssPath) : (string) time();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title><?= htmlspecialchars((string) $pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <?= app_favicon_tags() ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="<?= asset_url('assets/css/variables.css') ?>">
  <link rel="stylesheet" href="<?= asset_url('assets/css/base.css') ?>">
  <link rel="stylesheet" href="<?= asset_url('assets/css/layout.css') ?>">
  <link rel="stylesheet" href="<?= asset_url('assets/css/components.css') ?>">
  <link rel="stylesheet" href="<?= asset_url('assets/css/responsive.css') ?>">
  <link rel="stylesheet" href="<?= asset_url('assets/css/landing.css') ?>?v=<?= urlencode($landingCssVersion) ?>">
  <link rel="stylesheet" href="<?= asset_url('assets/css/brand.css') ?>">
  <?php if ($isStaticPage): ?>
  <?php $staticCssPath = dirname(__DIR__) . '/assets/css/static-pages.css'; $staticCssV = is_file($staticCssPath) ? (string) filemtime($staticCssPath) : (string) time(); ?>
  <link rel="stylesheet" href="<?= asset_url('assets/css/static-pages.css') ?>?v=<?= urlencode($staticCssV) ?>">
  <?php endif; ?>
  <?= mobile_app_css_tag() ?>
</head>
<body class="<?= $isLandingPage || $isStaticPage ? 'landing-body' : '' ?><?= $isStaticPage ? ' static-page' : '' ?>">
<header class="site-header landing-header <?= $isLandingPage ? 'is-landing' : '' ?>" id="siteHeader">
  <div class="container nav-wrap">
    <a class="nav-brand" href="<?= APP_URL ?>/index.php" aria-label="Donate Now Home">
      <?= app_logo_img('app-logo brand-mark', 40, 40) ?>
      <span class="brand-name">Donate Now</span>
    </a>
    <button id="mobileNavToggle" class="mobile-toggle" type="button" aria-expanded="false" aria-controls="primaryNav" aria-label="Toggle navigation menu">
      <span></span>
      <span></span>
      <span></span>
    </button>
    <nav id="primaryNav" class="nav-links" aria-label="Primary navigation">
      <a class="nav-link <?= active_nav_class('/index.php') ?>" href="<?= APP_URL ?>/index.php">Home</a>
      <a class="nav-link <?= active_nav_class('/pages/about.php') ?>" href="<?= !empty($isLandingPage) ? APP_URL . '/index.php#about' : APP_URL . '/pages/about.php' ?>">About Us</a>
      <a class="nav-link <?= active_nav_class('/pages/contact.php') ?>" href="<?= APP_URL ?>/pages/contact.php">Contact Us</a>
      <a class="nav-link <?= active_nav_class('/pages/privacy-policy.php') ?>" href="<?= APP_URL ?>/pages/privacy-policy.php">Privacy Policy</a>
      <a class="nav-link <?= active_nav_class('/pages/terms.php') ?>" href="<?= APP_URL ?>/pages/terms.php">Terms</a>
      <div class="mobile-actions">
        <a class="btn btn-ghost" href="<?= APP_URL ?>/auth/login.php">Login</a>
        <a class="btn btn-primary" href="<?= APP_URL ?>/auth/register.php">Register</a>
      </div>
    </nav>
    <div class="nav-actions">
      <a class="btn btn-ghost" href="<?= APP_URL ?>/auth/login.php">Login</a>
      <a class="btn btn-primary" href="<?= APP_URL ?>/auth/register.php">Register</a>
    </div>
  </div>
</header>
