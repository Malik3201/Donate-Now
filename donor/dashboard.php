<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/role_check.php';
require_once dirname(__DIR__) . '/includes/dashboard_functions.php';
require_role(['donor']);
$pdo = db();
$stats = get_donor_dashboard_stats($pdo, (int)$authUser['id']);
$recent = $stats['recent_donations'];
$recommended = $pdo->query("SELECT c.id, c.title, c.description, c.target_amount, c.collected_amount, c.image_url, c.status, cc.name AS category_name, np.ngo_name FROM campaigns c INNER JOIN ngo_profiles np ON np.id = c.ngo_id LEFT JOIN campaign_categories cc ON cc.id = c.category_id WHERE c.status IN ('pending','approved','active') ORDER BY c.created_at DESC LIMIT 4")->fetchAll();

$pageTitle = 'Donor Dashboard';
require_once dirname(__DIR__) . '/includes/dashboard_layout_start.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>
<div class="dn-welcome-panel">
  <h2>Your giving hub</h2>
  <p>Transparent statuses, clear amounts, and curated campaigns help you give with confidence.</p>
</div>
<div class="dashboard-widgets" style="margin-bottom:1rem;">
  <div class="stat-card dashboard-card"><small>Total donations submitted</small><h3><?= (int)$stats['total_donations_submitted'] ?></h3></div>
  <div class="stat-card dashboard-card"><small>Confirmed count</small><h3><?= (int)$stats['confirmed_donation_count'] ?></h3></div>
  <div class="stat-card dashboard-card"><small>Confirmed amount</small><h3>PKR <?= number_format((float)$stats['confirmed_donation_amount'],2) ?></h3></div>
  <div class="stat-card dashboard-card"><small>Pending</small><h3><?= (int)$stats['pending_donations'] ?></h3></div>
  <div class="stat-card dashboard-card"><small>Rejected</small><h3><?= (int)$stats['rejected_donations'] ?></h3></div>
  <div class="stat-card dashboard-card"><small>Unread notifications</small><h3><?= (int)$stats['unread_notifications'] ?></h3></div>
</div>
<div class="grid" style="grid-template-columns:1.2fr .8fr;">
  <div class="data-panel table-wrapper"><h3>Recent donations</h3><table><thead><tr><th>Campaign</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead><tbody><?php if(!$recent): ?><tr><td colspan="4" class="empty-state">No donation history yet.</td></tr><?php endif; ?><?php foreach($recent as $r): ?><tr><td><?= htmlspecialchars((string)$r['campaign_title'], ENT_QUOTES, 'UTF-8') ?></td><td><?= number_format((float)$r['amount'],2) ?></td><td><?= dash_status_badge((string)$r['status']) ?></td><td><?= htmlspecialchars((string)$r['created_at'], ENT_QUOTES, 'UTF-8') ?></td></tr><?php endforeach; ?></tbody></table></div>
  <div class="glass-card" style="padding:1rem;"><h3>Quick links</h3><div class="dn-quick-actions"><a class="gradient-button" href="<?= APP_URL ?>/donor/browse_campaigns.php">Browse campaigns</a><a class="outline-button" href="<?= APP_URL ?>/donor/my_donations.php">My donations</a><a class="outline-button" href="<?= APP_URL ?>/reports/create_report.php">Report an issue</a></div>
  <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border-light);">
    <h4 style="margin:0 0 0.35rem;">Donation status guide</h4>
    <p style="margin:0;font-size:0.85rem;color:var(--text-muted);line-height:1.5;"><strong>Pending</strong> — proof under NGO review. <strong>Confirmed</strong> — received and verified. <strong>Rejected</strong> — please check notifications for notes.</p>
  </div></div>
</div>
<h2 class="dn-page-title" style="font-size:1.15rem;margin:1.5rem 0 0.75rem;">Campaigns you can explore</h2>
<p class="help-text" style="margin:-0.25rem 0 0.85rem;">Includes new submissions (pending review). Donate only when status is approved or active.</p>
<div class="dn-donor-campaign-grid dn-donor-dashboard-campaigns">
  <?php if (!$recommended): ?>
    <div class="empty-state glass-card" style="grid-column:1/-1;">No campaigns listed yet.</div>
  <?php endif; ?>
  <?php foreach ($recommended as $c):
      $target = (float) $c['target_amount'];
      $collected = (float) $c['collected_amount'];
      $progress = $target > 0 ? min(100, ($collected / $target) * 100) : 0;
      $canDonate = in_array((string) ($c['status'] ?? ''), ['approved', 'active'], true);
      $desc = trim((string) ($c['description'] ?? ''));
      if ($desc !== '') {
          if (function_exists('mb_strlen') && function_exists('mb_substr')) {
              $excerpt = mb_strlen($desc) > 120 ? mb_substr($desc, 0, 120) . '…' : $desc;
          } else {
              $excerpt = strlen($desc) > 120 ? substr($desc, 0, 120) . '…' : $desc;
          }
      } else {
          $excerpt = 'Explore this campaign and see how your support can help.';
      }
      $catLabel = trim((string) ($c['category_name'] ?? ''));
      $detailUrl = APP_URL . '/donor/campaign_detail.php?id=' . (int) $c['id'];
      $img = sanitize(image_or_placeholder((string) ($c['image_url'] ?? ''), 'campaign'));
      ?>
    <article class="dn-donor-campaign-card">
      <a class="dn-donor-campaign-card__media" href="<?= htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') ?>">
        <img src="<?= $img ?>" alt="<?= htmlspecialchars((string) $c['title'], ENT_QUOTES, 'UTF-8') ?> campaign" loading="lazy" width="640" height="400">
        <div class="dn-donor-campaign-card__media-shade" aria-hidden="true"></div>
        <div class="dn-donor-campaign-card__media-top">
          <?php if ($catLabel !== ''): ?>
            <span class="dn-donor-campaign-card__chip"><?= htmlspecialchars($catLabel, ENT_QUOTES, 'UTF-8') ?></span>
          <?php endif; ?>
          <span class="dn-donor-campaign-card__status"><?= dash_status_badge((string) ($c['status'] ?? '')) ?></span>
        </div>
      </a>
      <div class="dn-donor-campaign-card__body">
        <p class="dn-donor-campaign-card__ngo"><?= sanitize((string) $c['ngo_name']) ?></p>
        <h3 class="dn-donor-campaign-card__title">
          <a href="<?= htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') ?>"><?= sanitize((string) $c['title']) ?></a>
        </h3>
        <p class="dn-donor-campaign-card__excerpt"><?= htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8') ?></p>
        <div class="dn-donor-campaign-card__figures">
          <div>
            <span class="dn-donor-campaign-card__fig-k">Raised</span>
            <span class="dn-donor-campaign-card__fig-v">PKR <?= number_format($collected, 0) ?></span>
          </div>
          <div>
            <span class="dn-donor-campaign-card__fig-k">Goal</span>
            <span class="dn-donor-campaign-card__fig-v">PKR <?= number_format($target, 0) ?></span>
          </div>
          <div class="dn-donor-campaign-card__fig-pct">
            <span class="dn-donor-campaign-card__fig-k">Progress</span>
            <span class="dn-donor-campaign-card__fig-v"><?= number_format($progress, 1) ?>%</span>
          </div>
        </div>
        <div class="dn-donor-campaign-card__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) round($progress) ?>">
          <span style="width:<?= number_format($progress, 2) ?>%;"></span>
        </div>
        <div class="dn-donor-campaign-card__actions">
          <a class="outline-button dn-donor-campaign-card__btn" href="<?= htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') ?>">View details</a>
          <?php if ($canDonate): ?>
            <a class="gradient-button dn-donor-campaign-card__btn dn-donor-campaign-card__btn--primary" href="<?= htmlspecialchars(APP_URL . '/donor/donate.php?campaign_id=' . (int) $c['id'], ENT_QUOTES, 'UTF-8') ?>">Donate</a>
          <?php else: ?>
            <span class="dn-donor-campaign-card__locked" title="Available after approval">Awaiting approval</span>
          <?php endif; ?>
        </div>
      </div>
    </article>
  <?php endforeach; ?>
</div>
<?php require_once dirname(__DIR__) . '/includes/dashboard_layout_end.php'; ?>
