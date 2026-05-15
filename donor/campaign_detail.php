<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/ui_helpers.php';
require_once dirname(__DIR__) . '/includes/location_helpers.php';

$pdo = db();
$campaignId = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT c.*, cc.name AS category_name, np.ngo_name, np.description AS ngo_description, np.latitude AS ngo_latitude, np.longitude AS ngo_longitude, np.address AS ngo_address, u.email AS ngo_email
FROM campaigns c
LEFT JOIN campaign_categories cc ON cc.id = c.category_id
INNER JOIN ngo_profiles np ON np.id = c.ngo_id
INNER JOIN users u ON u.id = np.user_id
WHERE c.id = :id AND c.status IN ('pending','approved','active') LIMIT 1");
$stmt->execute(['id' => $campaignId]);
$campaign = $stmt->fetch();
if (!$campaign) {
    http_response_code(404);
    exit('Campaign not found.');
}

$canDonate = in_array((string) $campaign['status'], ['approved', 'active'], true);
$target = (float) $campaign['target_amount'];
$collected = (float) $campaign['collected_amount'];
$progress = $target > 0 ? min(100, ($collected / $target) * 100) : 0;
$heroImg = sanitize(image_or_placeholder((string) ($campaign['image_url'] ?? ''), 'campaign'));
$catLabel = trim((string) ($campaign['category_name'] ?? ''));

$stmt = $pdo->prepare('SELECT update_title, update_description, created_at FROM campaign_updates WHERE campaign_id = :campaign_id ORDER BY created_at DESC');
$stmt->execute(['campaign_id' => $campaignId]);
$updates = $stmt->fetchAll();

$donateUrl = is_logged_in()
    ? (APP_URL . '/donor/donate.php?campaign_id=' . $campaignId)
    : (APP_URL . '/auth/login.php');

$donorDashboard = is_logged_in() && (($_SESSION['role'] ?? '') === 'donor');

if ($donorDashboard) {
    require_once dirname(__DIR__) . '/includes/auth_check.php';
    require_once dirname(__DIR__) . '/includes/role_check.php';
    require_role(['donor']);
    $pageTitle = (string) $campaign['title'];
    $pageDescription = 'Campaign story, funding progress, and NGO profile.';
    require_once dirname(__DIR__) . '/includes/dashboard_layout_start.php';
    require dirname(__DIR__) . '/includes/breadcrumbs.php';
    echo '<div class="dn-campaign-detail-page">';
} else {
    $pageTitle = (string) $campaign['title'];
    $dashCssPath = dirname(__DIR__) . '/assets/css/dashboard.css';
    $dashCssV = is_file($dashCssPath) ? (string) filemtime($dashCssPath) : (string) time();
    ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | Donate Now</title>
  <?= app_favicon_tags() ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('assets/css/variables.css'), ENT_QUOTES, 'UTF-8') ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('assets/css/dashboard.css'), ENT_QUOTES, 'UTF-8') ?>?v=<?= urlencode($dashCssV) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('assets/css/brand.css'), ENT_QUOTES, 'UTF-8') ?>">
  <?= mobile_app_css_tag() ?>
</head>
<body class="dashboard-app">
<div class="dashboard-shell" style="min-height:100vh;padding:1.5rem;box-sizing:border-box;">
<div class="dn-campaign-detail-page">
  <p style="margin:0 0 1rem;"><a class="outline-button" href="<?= htmlspecialchars(APP_URL . '/index.php', ENT_QUOTES, 'UTF-8') ?>">← Home</a></p>
<?php
}
?>
<header class="dn-campaign-hero">
    <div class="dn-campaign-hero__visual">
      <img src="<?= $heroImg ?>" alt="<?= htmlspecialchars((string) $campaign['title'], ENT_QUOTES, 'UTF-8') ?>" width="1200" height="640" fetchpriority="high">
      <div class="dn-campaign-hero__scrim" aria-hidden="true"></div>
    </div>
    <div class="dn-campaign-hero__content">
      <div class="dn-campaign-hero__chips">
        <?php if ($catLabel !== ''): ?>
          <span class="dn-campaign-hero__chip"><?= htmlspecialchars($catLabel, ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
        <?= dash_status_badge((string) $campaign['status']) ?>
      </div>
      <h1 class="dn-campaign-hero__title"><?= htmlspecialchars((string) $campaign['title'], ENT_QUOTES, 'UTF-8') ?></h1>
      <p class="dn-campaign-hero__ngo">
        <span class="dn-campaign-hero__ngo-label">Organized by</span>
        <strong><?= htmlspecialchars((string) $campaign['ngo_name'], ENT_QUOTES, 'UTF-8') ?></strong>
      </p>
    </div>
  </header>

  <?php if (!$canDonate): ?>
    <div class="dn-campaign-alert dn-campaign-alert--warn">
      <strong>Awaiting platform approval</strong>
      <p>This campaign is visible but not open for donations yet. Please check back once the status is <strong>approved</strong> or <strong>active</strong>.</p>
    </div>
  <?php endif; ?>

  <section class="dn-campaign-funding glass-card" aria-labelledby="funding-heading">
    <h2 id="funding-heading" class="sr-only">Fundraising progress</h2>
    <div class="dn-campaign-funding__row">
      <div class="dn-campaign-funding__stat">
        <span class="dn-campaign-funding__label">Raised</span>
        <span class="dn-campaign-funding__amount">PKR <?= number_format($collected, 2) ?></span>
      </div>
      <div class="dn-campaign-funding__stat">
        <span class="dn-campaign-funding__label">Goal</span>
        <span class="dn-campaign-funding__amount">PKR <?= number_format($target, 2) ?></span>
      </div>
      <div class="dn-campaign-funding__stat dn-campaign-funding__stat--accent">
        <span class="dn-campaign-funding__label">Progress</span>
        <span class="dn-campaign-funding__amount"><?= number_format($progress, 1) ?>%</span>
      </div>
    </div>
    <div class="dn-campaign-funding__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) round($progress) ?>">
      <span style="width:<?= number_format($progress, 2) ?>%;"></span>
    </div>
    <div class="dn-campaign-funding__cta">
      <?php if ($canDonate): ?>
        <a class="gradient-button dn-campaign-funding__donate" href="<?= htmlspecialchars($donateUrl, ENT_QUOTES, 'UTF-8') ?>"><?= is_logged_in() ? 'Donate to this campaign' : 'Log in to donate' ?></a>
        <?php if (!is_logged_in()): ?><p class="help-text" style="margin:0;">Create or sign in to a donor account to submit payment proof.</p><?php endif; ?>
      <?php else: ?>
        <span class="outline-button dn-campaign-funding__donate is-disabled" aria-disabled="true">Donating unavailable</span>
      <?php endif; ?>
    </div>
  </section>

  <div class="dn-campaign-detail-grid">
    <div class="dn-campaign-main">
      <section class="glass-card dn-campaign-section">
        <h2 class="dn-campaign-section__title">About this campaign</h2>
        <div class="dn-campaign-section__meta">
          <?php if (!empty($campaign['start_date']) || !empty($campaign['end_date'])): ?>
            <span><?php if (!empty($campaign['start_date'])): ?>Starts <?= htmlspecialchars((string) $campaign['start_date'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?><?php if (!empty($campaign['start_date']) && !empty($campaign['end_date'])): ?> · <?php endif; ?><?php if (!empty($campaign['end_date'])): ?>Ends <?= htmlspecialchars((string) $campaign['end_date'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?></span>
          <?php endif; ?>
        </div>
        <div class="dn-campaign-prose">
          <?= nl2br(htmlspecialchars((string) $campaign['description'], ENT_QUOTES, 'UTF-8')) ?>
        </div>
        <?= render_campaign_location_maps($campaign) ?>
      </section>

      <section class="data-panel dn-campaign-updates" style="margin-top:0;border-radius:22px;">
        <h2 class="dn-campaign-section__title" style="padding:0 0 0.5rem;">Campaign updates</h2>
        <?php if (!$updates): ?>
          <p class="empty-state" style="margin:0;padding:1.5rem 0;">No updates published yet.</p>
        <?php endif; ?>
        <ul class="dn-campaign-timeline">
          <?php foreach ($updates as $update): ?>
            <li class="dn-campaign-timeline__item">
              <div class="dn-campaign-timeline__dot" aria-hidden="true"></div>
              <div class="dn-campaign-timeline__card glass-card">
                <div class="dn-campaign-timeline__head">
                  <strong><?= htmlspecialchars((string) $update['update_title'], ENT_QUOTES, 'UTF-8') ?></strong>
                  <time datetime="<?= htmlspecialchars((string) $update['created_at'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $update['created_at'], ENT_QUOTES, 'UTF-8') ?></time>
                </div>
                <div class="dn-campaign-prose dn-campaign-prose--compact">
                  <?= nl2br(htmlspecialchars((string) $update['update_description'], ENT_QUOTES, 'UTF-8')) ?>
                </div>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      </section>
    </div>

    <aside class="dn-campaign-aside">
      <div class="glass-card dn-campaign-ngo-card">
        <h2 class="dn-campaign-section__title">About the NGO</h2>
        <p class="dn-campaign-ngo-card__name"><?= htmlspecialchars((string) $campaign['ngo_name'], ENT_QUOTES, 'UTF-8') ?></p>
        <p class="dn-campaign-ngo-card__email"><a href="mailto:<?= htmlspecialchars((string) $campaign['ngo_email'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $campaign['ngo_email'], ENT_QUOTES, 'UTF-8') ?></a></p>
        <div class="dn-campaign-prose dn-campaign-prose--compact">
          <?= nl2br(htmlspecialchars(trim((string) ($campaign['ngo_description'] ?? '')), ENT_QUOTES, 'UTF-8')) ?>
        </div>
        <?php
        $ngoUrl = $donorDashboard ? ngo_detail_page_url('donor', (int) $campaign['ngo_id']) : '';
        if ($ngoUrl !== ''): ?>
          <a class="outline-button" style="margin-top:0.75rem;width:100%;justify-content:center;" href="<?= htmlspecialchars($ngoUrl, ENT_QUOTES, 'UTF-8') ?>">View NGO on map</a>
        <?php endif; ?>
      </div>
      <div class="glass-card dn-campaign-trust-card">
        <h3 class="dn-campaign-trust-card__title">Transparent giving</h3>
        <ul class="dn-campaign-trust-card__list">
          <li>Proof-based donations with NGO review</li>
          <li>Clear status from pending to confirmed</li>
          <li>Report concerns if something looks off</li>
        </ul>
        <?php if ($donorDashboard): ?>
          <a class="outline-button" style="width:100%;justify-content:center;margin-top:0.5rem;" href="<?= htmlspecialchars(APP_URL . '/reports/create_report.php', ENT_QUOTES, 'UTF-8') ?>">Report an issue</a>
        <?php endif; ?>
      </div>
    </aside>
  </div>
</div>
<?php
if ($donorDashboard) {
    require_once dirname(__DIR__) . '/includes/dashboard_layout_end.php';
} else {
    echo '</div></body></html>';
}
