<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/role_check.php';
require_once dirname(__DIR__) . '/includes/dashboard_functions.php';
require_role(['admin']);

$pdo = db();
$stats = get_admin_dashboard_stats($pdo);
$donStatus = $pdo->query('SELECT status, COUNT(*) AS c FROM donations GROUP BY status')->fetchAll();
$campStatus = $pdo->query('SELECT status, COUNT(*) AS c FROM campaigns GROUP BY status')->fetchAll();
$userRoles = $pdo->query('SELECT role, COUNT(*) AS c FROM users GROUP BY role')->fetchAll();
$reportTypes = $pdo->query('SELECT report_type, COUNT(*) AS c FROM reports GROUP BY report_type')->fetchAll();
$activity = $pdo->query('SELECT action, description, created_at FROM activity_logs ORDER BY created_at DESC LIMIT 20')->fetchAll();
$topNgos = $pdo->query('SELECT ngo_name, total_received_amount FROM ngo_profiles ORDER BY total_received_amount DESC LIMIT 5')->fetchAll();
$topCampaigns = $pdo->query('SELECT title, collected_amount FROM campaigns ORDER BY collected_amount DESC LIMIT 5')->fetchAll();

function dn_analytics_bar_row_class(string $label, string $mode): string
{
    $m = strtolower(trim($label));
    if ($mode === 'status') {
        $positive = ['confirmed', 'verified', 'approved', 'active', 'resolved', 'accepted', 'completed'];
        $pending = ['pending', 'open', 'under_review', 'draft'];
        $negative = ['rejected', 'flagged', 'blocked', 'inactive', 'cancelled', 'suspended', 'temporary_hold'];
        if (in_array($m, $positive, true)) {
            return 'dn-analytics-bar-row--positive';
        }
        if (in_array($m, $pending, true)) {
            return 'dn-analytics-bar-row--pending';
        }
        if (in_array($m, $negative, true)) {
            return 'dn-analytics-bar-row--negative';
        }

        return 'dn-analytics-bar-row--neutral';
    }

    $accents = [
        'dn-analytics-bar-row--accent0',
        'dn-analytics-bar-row--accent1',
        'dn-analytics-bar-row--accent2',
        'dn-analytics-bar-row--accent3',
    ];

    return $accents[abs(crc32($label)) % 4];
}

/**
 * @param list<array<string, mixed>> $rows
 */
function dn_render_analytics_bars(array $rows, string $labelKey, string $valueKey, string $mode): string
{
    if ($rows === []) {
        return '<p class="dn-analytics-chart__empty">No data.</p>';
    }
    $max = 1;
    foreach ($rows as $r) {
        $max = max($max, (int) $r[$valueKey]);
    }
    $html = '';
    foreach ($rows as $r) {
        $label = (string) $r[$labelKey];
        $count = (int) $r[$valueKey];
        $w = $max > 0 ? ($count / $max) * 100 : 0;
        $rowClass = dn_analytics_bar_row_class($label, $mode);
        $html .= '<div class="dn-analytics-bar-row ' . sanitize($rowClass) . '">';
        $html .= '<div class="dn-analytics-bar-row__head">';
        $html .= '<span class="dn-analytics-bar-row__label">' . sanitize($label) . '</span>';
        $html .= '<span class="dn-analytics-bar-row__value">' . $count . '</span>';
        $html .= '</div>';
        $html .= '<div class="progress-wrap"><div class="progress-bar" style="width:' . number_format($w, 2, '.', '') . '%"></div></div>';
        $html .= '</div>';
    }

    return $html;
}

$pageTitle = 'Analytics';
$pageDescription = 'High-level counts, distribution charts, and the latest platform activity.';
require_once dirname(__DIR__) . '/includes/dashboard_layout_start.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';

$kpi = [
    [
        'label' => 'Total users',
        'value' => (string) (int) $stats['total_users'],
        'href' => APP_URL . '/admin/manage_users.php',
        'hint' => 'Manage accounts',
    ],
    [
        'label' => 'Total donations',
        'value' => (string) (int) $stats['total_donations'],
        'href' => APP_URL . '/admin/all_donations.php',
        'hint' => 'All transactions',
    ],
    [
        'label' => 'Confirmed amount',
        'value' => 'PKR ' . number_format((float) $stats['total_confirmed_donation_amount'], 2),
        'href' => APP_URL . '/admin/all_donations.php?status=confirmed',
        'hint' => 'Verified payouts',
    ],
    [
        'label' => 'Active campaigns',
        'value' => (string) (int) $stats['active_campaigns'],
        'href' => APP_URL . '/admin/manage_campaigns.php',
        'hint' => 'Campaign roster',
    ],
    [
        'label' => 'Open reports',
        'value' => (string) (int) $stats['open_reports'],
        'href' => APP_URL . '/admin/reports.php',
        'hint' => 'Needs review',
    ],
];
?>
<div class="dn-page-head">
  <h1 class="dn-page-title">Admin analytics</h1>
  <p class="dn-page-lead">Snapshot of users, giving, campaigns, and moderation workload.</p>
</div>

<section class="dn-analytics-kpi-grid" aria-label="Key metrics">
  <?php foreach ($kpi as $card): ?>
    <a class="dn-analytics-kpi-card" href="<?= sanitize($card['href']) ?>">
      <span class="dn-analytics-kpi-card__label"><?= sanitize($card['label']) ?></span>
      <span class="dn-analytics-kpi-card__value"><?= sanitize($card['value']) ?></span>
      <span class="dn-analytics-kpi-card__hint"><?= sanitize($card['hint']) ?> →</span>
    </a>
  <?php endforeach; ?>
</section>

<div class="dn-analytics-charts-grid">
  <article class="glass-card dn-analytics-chart">
    <h2 class="dn-analytics-chart__title">Donations by status</h2>
    <?= dn_render_analytics_bars($donStatus, 'status', 'c', 'status') ?>
  </article>
  <article class="glass-card dn-analytics-chart">
    <h2 class="dn-analytics-chart__title">Campaigns by status</h2>
    <?= dn_render_analytics_bars($campStatus, 'status', 'c', 'status') ?>
  </article>
  <article class="glass-card dn-analytics-chart">
    <h2 class="dn-analytics-chart__title">Users by role</h2>
    <?= dn_render_analytics_bars($userRoles, 'role', 'c', 'generic') ?>
  </article>
  <article class="glass-card dn-analytics-chart">
    <h2 class="dn-analytics-chart__title">Reports by type</h2>
    <?= dn_render_analytics_bars($reportTypes, 'report_type', 'c', 'generic') ?>
  </article>
</div>

<div class="dn-analytics-split">
  <article class="glass-card dn-analytics-rank-card">
    <h2 class="dn-analytics-chart__title">Top NGOs (confirmed received)</h2>
    <ol class="dn-analytics-rank-list">
      <?php foreach ($topNgos as $i => $n): ?>
        <li class="dn-analytics-rank-list__item">
          <span class="dn-analytics-rank-list__rank"><?= (int) $i + 1 ?></span>
          <span class="dn-analytics-rank-list__name"><?= sanitize($n['ngo_name']) ?></span>
          <span class="dn-analytics-rank-list__amt">PKR <?= number_format((float) $n['total_received_amount'], 2) ?></span>
        </li>
      <?php endforeach; ?>
      <?php if (!$topNgos): ?>
        <li class="dn-analytics-chart__empty">No NGO totals yet.</li>
      <?php endif; ?>
    </ol>
  </article>
  <article class="glass-card dn-analytics-rank-card">
    <h2 class="dn-analytics-chart__title">Top campaigns (collected)</h2>
    <ol class="dn-analytics-rank-list">
      <?php foreach ($topCampaigns as $i => $c): ?>
        <li class="dn-analytics-rank-list__item">
          <span class="dn-analytics-rank-list__rank"><?= (int) $i + 1 ?></span>
          <span class="dn-analytics-rank-list__name"><?= sanitize($c['title']) ?></span>
          <span class="dn-analytics-rank-list__amt">PKR <?= number_format((float) $c['collected_amount'], 2) ?></span>
        </li>
      <?php endforeach; ?>
      <?php if (!$topCampaigns): ?>
        <li class="dn-analytics-chart__empty">No campaign totals yet.</li>
      <?php endif; ?>
    </ol>
  </article>
</div>

<section class="glass-card dn-analytics-activity" aria-label="Recent activity">
  <h2 class="dn-analytics-chart__title">Recent activity log</h2>
  <ul class="dn-analytics-activity-list">
    <?php foreach ($activity as $a): ?>
      <li class="dn-analytics-activity-list__item">
        <div class="dn-analytics-activity-list__meta">
          <strong class="dn-analytics-activity-list__action"><?= sanitize($a['action']) ?></strong>
          <time class="dn-analytics-activity-list__time" datetime="<?= sanitize((string) $a['created_at']) ?>"><?= sanitize((string) $a['created_at']) ?></time>
        </div>
        <p class="dn-analytics-activity-list__desc"><?= sanitize((string) $a['description']) ?></p>
      </li>
    <?php endforeach; ?>
    <?php if (!$activity): ?>
      <li class="dn-analytics-chart__empty">No log entries yet.</li>
    <?php endif; ?>
  </ul>
</section>

<?php require_once dirname(__DIR__) . '/includes/dashboard_layout_end.php'; ?>
