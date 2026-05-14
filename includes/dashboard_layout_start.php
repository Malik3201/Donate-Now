<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/ui_helpers.php';

$pageTitle = $pageTitle ?? 'Dashboard';
$pageDescription = $pageDescription ?? null;

$dashCssPath = dirname(__DIR__) . '/assets/css/dashboard.css';
$dashCssV = is_file($dashCssPath) ? (string) filemtime($dashCssPath) : (string) time();
$dashRespPath = dirname(__DIR__) . '/assets/css/dashboard-responsive.css';
$dashRespV = is_file($dashRespPath) ? (string) filemtime($dashRespPath) : (string) time();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars((string) $pageTitle, ENT_QUOTES, 'UTF-8') ?> | Donate Now</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="<?= asset_url('assets/css/variables.css') ?>">
  <link rel="stylesheet" href="<?= asset_url('assets/css/base.css') ?>">
  <link rel="stylesheet" href="<?= asset_url('assets/css/layout.css') ?>">
  <link rel="stylesheet" href="<?= asset_url('assets/css/components.css') ?>">
  <link rel="stylesheet" href="<?= asset_url('assets/css/forms.css') ?>">
  <link rel="stylesheet" href="<?= asset_url('assets/css/tables.css') ?>">
  <link rel="stylesheet" href="<?= asset_url('assets/css/dashboard.css') ?>?v=<?= urlencode($dashCssV) ?>">
  <link rel="stylesheet" href="<?= asset_url('assets/css/dashboard-responsive.css') ?>?v=<?= urlencode($dashRespV) ?>">
  <link rel="stylesheet" href="<?= asset_url('assets/css/responsive.css') ?>">
</head>
<body class="dashboard-app">
<div class="dashboard-shell" id="dashboardShell">
  <div class="dashboard-sidebar-overlay" id="dashboardSidebarOverlay" aria-hidden="true"></div>
  <?php require dirname(__DIR__) . '/includes/dashboard_sidebar.php'; ?>
  <div class="dashboard-main">
    <?php require dirname(__DIR__) . '/includes/dashboard_topbar.php'; ?>
