<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/role_check.php';
require_once dirname(__DIR__) . '/includes/dashboard_functions.php';
require_role(['admin']);

$pdo = db();
$counts = get_admin_dashboard_stats($pdo);

$recentDonations = $pdo->query("SELECT d.id,d.amount,d.status,d.created_at,du.full_name donor_name,nu.ngo_name,c.title campaign_title FROM donations d INNER JOIN donor_profiles dp ON dp.id=d.donor_id INNER JOIN users du ON du.id=dp.user_id INNER JOIN ngo_profiles nu ON nu.id=d.ngo_id INNER JOIN campaigns c ON c.id=d.campaign_id ORDER BY d.created_at DESC LIMIT 8")->fetchAll();
$pendingNgos = $pdo->query("SELECT np.id,np.ngo_name,u.email,u.created_at FROM ngo_profiles np INNER JOIN users u ON u.id=np.user_id WHERE np.verification_status='pending' ORDER BY u.created_at DESC LIMIT 6")->fetchAll();
$pendingCampaigns = $pdo->query("SELECT c.id,c.title,np.ngo_name,c.created_at FROM campaigns c INNER JOIN ngo_profiles np ON np.id=c.ngo_id WHERE c.status='pending' ORDER BY c.created_at DESC LIMIT 6")->fetchAll();
$openReports = $pdo->query("SELECT r.id,r.subject,r.report_type,r.created_at,u.full_name reporter_name FROM reports r INNER JOIN users u ON u.id=r.reporter_user_id WHERE r.status IN ('open','under_review') ORDER BY r.created_at DESC LIMIT 6")->fetchAll();

$spotlights = [
    [
        'label' => 'Total users',
        'value' => (string) (int) $counts['total_users'],
        'hint' => 'Directory & roles',
        'href' => APP_URL . '/admin/manage_users.php',
        'icon' => 'users',
        'variant' => 'users',
    ],
    [
        'label' => 'Confirmed volume',
        'value' => 'PKR ' . number_format((float) $counts['total_confirmed_donation_amount'], 2),
        'hint' => 'Verified donations',
        'href' => APP_URL . '/admin/all_donations.php?status=confirmed',
        'icon' => 'currency',
        'variant' => 'money',
    ],
    [
        'label' => 'Active campaigns',
        'value' => (string) (int) $counts['active_campaigns'],
        'hint' => 'Live fundraising',
        'href' => APP_URL . '/admin/manage_campaigns.php',
        'icon' => 'chart',
        'variant' => 'campaigns',
    ],
    [
        'label' => 'Open reports',
        'value' => (string) (int) $counts['open_reports'],
        'hint' => 'Needs triage',
        'href' => APP_URL . '/admin/reports.php',
        'icon' => 'flag',
        'variant' => 'reports',
    ],
];

$communityTiles = [
    ['label' => 'Active users', 'value' => (int) $counts['active_users'], 'icon' => 'user', 'tone' => 'a'],
    ['label' => 'Blocked / suspended / hold', 'value' => (int) $counts['blocked_suspended_hold_users'], 'icon' => 'shield', 'tone' => 'b'],
    ['label' => 'Total donors', 'value' => (int) $counts['total_donors'], 'icon' => 'heart', 'tone' => 'c'],
    ['label' => 'Total NGOs', 'value' => (int) $counts['total_ngos'], 'icon' => 'megaphone', 'tone' => 'd'],
    ['label' => 'Pending NGOs', 'value' => (int) $counts['pending_ngos'], 'icon' => 'clipboard', 'tone' => 'a'],
    ['label' => 'Verified NGOs', 'value' => (int) $counts['verified_ngos'], 'icon' => 'shield', 'tone' => 'b'],
    ['label' => 'Total volunteers', 'value' => (int) $counts['total_volunteers'], 'icon' => 'handshake', 'tone' => 'c'],
];

$operationsTiles = [
    ['label' => 'Total campaigns', 'value' => (int) $counts['total_campaigns'], 'icon' => 'folder', 'tone' => 'a'],
    ['label' => 'Pending donations', 'value' => (int) $counts['pending_donations'], 'icon' => 'bell', 'tone' => 'b'],
    ['label' => 'Confirmed donations', 'value' => (int) $counts['confirmed_donations'], 'icon' => 'currency', 'tone' => 'd'],
];

$pageTitle = 'Admin Dashboard';
$pageDescription = 'Live counts for users, NGOs, campaigns, giving, and moderation.';
require_once dirname(__DIR__) . '/includes/dashboard_layout_start.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>
<div class="dn-admin-dashboard">
  <header class="dn-welcome-panel dn-admin-dash-welcome">
    <div class="dn-admin-dash-welcome__text">
      <h2 class="dn-admin-dash-welcome__title">Welcome back, <?= htmlspecialchars((string) ($authUser['full_name'] ?? 'Admin'), ENT_QUOTES, 'UTF-8') ?></h2>
      <p class="dn-admin-dash-welcome__lead">Prioritize verification, review open reports, and keep donation flows healthy across the platform.</p>
    </div>
    <div class="dn-admin-dash-welcome__actions">
      <a class="outline-button" href="<?= htmlspecialchars(APP_URL . '/admin/analytics.php', ENT_QUOTES, 'UTF-8') ?>">Analytics</a>
      <a class="gradient-button" href="<?= htmlspecialchars(APP_URL . '/admin/all_donations.php', ENT_QUOTES, 'UTF-8') ?>">Transactions</a>
    </div>
  </header>

  <section class="dn-admin-dash-spotlights" aria-label="Headline metrics">
    <?php foreach ($spotlights as $s): ?>
      <a class="dn-admin-dash-spotlight dn-admin-dash-spotlight--<?= htmlspecialchars($s['variant'], ENT_QUOTES, 'UTF-8') ?>" href="<?= htmlspecialchars($s['href'], ENT_QUOTES, 'UTF-8') ?>">
        <span class="dn-admin-dash-spotlight__icon" aria-hidden="true"><?= dn_nav_icon($s['icon']) ?></span>
        <span class="dn-admin-dash-spotlight__label"><?= htmlspecialchars($s['label'], ENT_QUOTES, 'UTF-8') ?></span>
        <span class="dn-admin-dash-spotlight__value"><?= htmlspecialchars($s['value'], ENT_QUOTES, 'UTF-8') ?></span>
        <span class="dn-admin-dash-spotlight__hint"><?= htmlspecialchars($s['hint'], ENT_QUOTES, 'UTF-8') ?> →</span>
      </a>
    <?php endforeach; ?>
  </section>

  <section class="dn-admin-dash-section" aria-labelledby="dash-section-community">
    <h2 id="dash-section-community" class="dn-admin-dash-section__title">Community &amp; access</h2>
    <div class="dn-admin-dash-metric-grid">
      <?php foreach ($communityTiles as $t): ?>
        <div class="dn-stat-card dn-stat-card--tone-<?= htmlspecialchars($t['tone'], ENT_QUOTES, 'UTF-8') ?>">
          <div class="dn-stat-icon" aria-hidden="true"><?= dn_nav_icon($t['icon']) ?></div>
          <span class="dn-stat-label"><?= htmlspecialchars($t['label'], ENT_QUOTES, 'UTF-8') ?></span>
          <p class="dn-stat-value"><?= (int) $t['value'] ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="dn-admin-dash-section" aria-labelledby="dash-section-ops">
    <h2 id="dash-section-ops" class="dn-admin-dash-section__title">Campaigns &amp; giving</h2>
    <div class="dn-admin-dash-metric-grid">
      <?php foreach ($operationsTiles as $t): ?>
        <div class="dn-stat-card dn-stat-card--tone-<?= htmlspecialchars($t['tone'], ENT_QUOTES, 'UTF-8') ?>">
          <div class="dn-stat-icon" aria-hidden="true"><?= dn_nav_icon($t['icon']) ?></div>
          <span class="dn-stat-label"><?= htmlspecialchars($t['label'], ENT_QUOTES, 'UTF-8') ?></span>
          <p class="dn-stat-value"><?= (int) $t['value'] ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <div class="dn-admin-dash-split">
    <div class="data-panel table-wrapper">
      <h3>Recent donations</h3>
      <table>
        <thead>
          <tr>
            <th>Donor</th>
            <th>NGO</th>
            <th>Campaign</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($recentDonations as $d): ?>
          <tr>
            <td><?= htmlspecialchars((string) $d['donor_name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string) $d['ngo_name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string) $d['campaign_title'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= number_format((float) $d['amount'], 2) ?></td>
            <td><?= dash_status_badge((string) $d['status']) ?></td>
            <td><?= htmlspecialchars((string) $d['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$recentDonations): ?>
          <tr><td colspan="6" class="empty-state">No recent donations.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
    <aside class="glass-card dn-admin-dash-aside">
      <h3 class="dn-admin-dash-aside__title">Quick actions</h3>
      <p class="dn-admin-dash-aside__lead">Jump to the workflows admins use most often.</p>
      <div class="dn-quick-actions">
        <a class="gradient-button" href="<?= htmlspecialchars(APP_URL . '/admin/verify_ngos.php', ENT_QUOTES, 'UTF-8') ?>">Verify NGOs</a>
        <a class="gradient-button" href="<?= htmlspecialchars(APP_URL . '/admin/reports.php', ENT_QUOTES, 'UTF-8') ?>">Review reports</a>
        <a class="outline-button" href="<?= htmlspecialchars(APP_URL . '/admin/all_donations.php', ENT_QUOTES, 'UTF-8') ?>">View transactions</a>
        <a class="outline-button" href="<?= htmlspecialchars(APP_URL . '/admin/manage_users.php', ENT_QUOTES, 'UTF-8') ?>">Manage users</a>
      </div>
    </aside>
  </div>

  <div class="dn-admin-dash-triple">
    <div class="data-panel table-wrapper">
      <h3>Pending NGO verification</h3>
      <table><tbody>
      <?php if (!$pendingNgos): ?><tr><td class="empty-state">No pending NGOs</td></tr><?php endif; ?>
      <?php foreach ($pendingNgos as $n): ?>
        <tr>
          <td>
            <strong><?= htmlspecialchars((string) $n['ngo_name'], ENT_QUOTES, 'UTF-8') ?></strong><br>
            <small><?= htmlspecialchars((string) $n['email'], ENT_QUOTES, 'UTF-8') ?></small>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody></table>
    </div>
    <div class="data-panel table-wrapper">
      <h3>Pending campaigns</h3>
      <table><tbody>
      <?php if (!$pendingCampaigns): ?><tr><td class="empty-state">No pending campaigns</td></tr><?php endif; ?>
      <?php foreach ($pendingCampaigns as $c): ?>
        <tr>
          <td>
            <strong><?= htmlspecialchars((string) $c['title'], ENT_QUOTES, 'UTF-8') ?></strong><br>
            <small><?= htmlspecialchars((string) $c['ngo_name'], ENT_QUOTES, 'UTF-8') ?></small>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody></table>
    </div>
    <div class="data-panel table-wrapper">
      <h3>Open reports</h3>
      <table><tbody>
      <?php if (!$openReports): ?><tr><td class="empty-state">No open reports</td></tr><?php endif; ?>
      <?php foreach ($openReports as $r): ?>
        <tr>
          <td>
            <strong><?= htmlspecialchars((string) $r['subject'], ENT_QUOTES, 'UTF-8') ?></strong><br>
            <small><?= htmlspecialchars((string) $r['report_type'], ENT_QUOTES, 'UTF-8') ?> by <?= htmlspecialchars((string) $r['reporter_name'], ENT_QUOTES, 'UTF-8') ?></small>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody></table>
    </div>
  </div>
</div>
<?php require_once dirname(__DIR__) . '/includes/dashboard_layout_end.php'; ?>
