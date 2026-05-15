<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/ui_helpers.php';
$landingCssPath = dirname(__DIR__) . '/assets/css/landing.css';
$landingCssVersion = is_file($landingCssPath) ? (string) filemtime($landingCssPath) : (string) time();
$authCssPath = dirname(__DIR__) . '/assets/css/auth.css';
$authCssVersion = is_file($authCssPath) ? (string) filemtime($authCssPath) : (string) time();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title><?= isset($pageTitle) ? sanitize((string) $pageTitle) : 'Authentication' ?> | Donate Now</title>
  <?= app_favicon_tags() ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="<?= asset_url('assets/css/variables.css') ?>">
  <link rel="stylesheet" href="<?= asset_url('assets/css/base.css') ?>">
  <link rel="stylesheet" href="<?= asset_url('assets/css/layout.css') ?>">
  <link rel="stylesheet" href="<?= asset_url('assets/css/components.css') ?>">
  <link rel="stylesheet" href="<?= asset_url('assets/css/landing.css') ?>?v=<?= urlencode($landingCssVersion) ?>">
  <link rel="stylesheet" href="<?= asset_url('assets/css/forms.css') ?>">
  <link rel="stylesheet" href="<?= asset_url('assets/css/auth.css') ?>?v=<?= urlencode($authCssVersion) ?>">
  <link rel="stylesheet" href="<?= asset_url('assets/css/brand.css') ?>">
  <link rel="stylesheet" href="<?= asset_url('assets/css/responsive.css') ?>">
  <?= mobile_app_css_tag() ?>
</head>
<body class="landing-body auth-page">
<header class="auth-topbar auth-header">
  <div class="container auth-topbar-inner">
    <a class="auth-brand" href="<?= APP_URL ?>/index.php" aria-label="Donate Now home">
      <?= app_logo_img('app-logo brand-mark', 38, 38) ?>
      <span class="auth-brand-text">Donate Now</span>
    </a>
    <a class="auth-back-home" href="<?= APP_URL ?>/index.php">Back to Home</a>
  </div>
</header>
