<?php
require_once dirname(__DIR__) . '/includes/ui_helpers.php';
$isLandingPage = $isLandingPage ?? false;
$pageTitle = $pageTitle ?? 'Donate Now';
$landingCssPath = dirname(__DIR__) . '/assets/css/landing.css';
$landingCssVersion = is_file($landingCssPath) ? (string) filemtime($landingCssPath) : (string) time();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars((string) $pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="<?= asset_url('assets/css/variables.css') ?>">
  <link rel="stylesheet" href="<?= asset_url('assets/css/base.css') ?>">
  <link rel="stylesheet" href="<?= asset_url('assets/css/layout.css') ?>">
  <link rel="stylesheet" href="<?= asset_url('assets/css/components.css') ?>">
  <link rel="stylesheet" href="<?= asset_url('assets/css/responsive.css') ?>">
  <link rel="stylesheet" href="<?= asset_url('assets/css/landing.css') ?>?v=<?= urlencode($landingCssVersion) ?>">
</head>
<body class="<?= $isLandingPage ? 'landing-body' : '' ?>">
<header class="site-header landing-header <?= $isLandingPage ? 'is-landing' : '' ?>" id="siteHeader">
  <div class="container nav-wrap">
    <a class="nav-brand" href="<?= APP_URL ?>/index.php" aria-label="Donate Now Home">
      <span class="brand-mark" aria-hidden="true"></span>
      <span class="brand-name">Donate Now</span>
    </a>
    <button id="mobileNavToggle" class="mobile-toggle" type="button" aria-expanded="false" aria-controls="primaryNav" aria-label="Toggle navigation menu">
      <span></span>
      <span></span>
      <span></span>
    </button>
    <nav id="primaryNav" class="nav-links" aria-label="Primary navigation">
      <a class="nav-link <?= active_nav_class('/index.php') ?>" href="<?= APP_URL ?>/index.php">Home</a>
      <a class="nav-link" href="<?= APP_URL ?>/public/campaigns.php">Campaigns</a>
      <a class="nav-link <?= active_nav_class('/pages/how-it-works.php') ?>" href="<?= APP_URL ?>/pages/how-it-works.php">How It Works</a>
      <a class="nav-link" href="<?= APP_URL ?>/pages/about.php">NGOs</a>
      <a class="nav-link" href="<?= APP_URL ?>/auth/register.php?role=volunteer">Volunteer</a>
      <a class="nav-link" href="<?= APP_URL ?>/pages/faqs.php">Safety</a>
      <a class="nav-link <?= active_nav_class('/pages/contact.php') ?>" href="<?= APP_URL ?>/pages/contact.php">Contact</a>
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
