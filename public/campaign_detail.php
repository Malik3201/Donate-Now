<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/public_header.php';
$pdo = db();
$campaignId = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT c.*, cc.name AS category_name, np.ngo_name, np.description AS ngo_description, np.verification_status FROM campaigns c LEFT JOIN campaign_categories cc ON cc.id = c.category_id INNER JOIN ngo_profiles np ON np.id = c.ngo_id WHERE c.id = :id AND c.status IN ('pending','approved','active') LIMIT 1");
$stmt->execute(['id' => $campaignId]);
$campaign = $stmt->fetch();
if (!$campaign) {
    exit('Campaign not found.');
}
$canDonate = in_array((string) $campaign['status'], ['approved', 'active'], true);
$stmt = $pdo->prepare('SELECT update_title, update_description, created_at FROM campaign_updates WHERE campaign_id = :id ORDER BY created_at DESC');
$stmt->execute(['id' => $campaignId]);
$updates = $stmt->fetchAll();
$donateUrl = is_logged_in() ? APP_URL . '/donor/donate.php?campaign_id=' . $campaignId : APP_URL . '/auth/login.php';
$progress = ((float) $campaign['target_amount'] > 0) ? min(100, ((float) $campaign['collected_amount'] / (float) $campaign['target_amount'] * 100)) : 0;
?>
<main>
<section class="section">
  <div class="container">
    <div class="grid" style="grid-template-columns:1.2fr .8fr;align-items:start;">
      <div class="glass-card" style="padding:1.2rem;">
        <img src="<?= sanitize(image_or_placeholder((string) ($campaign['image_url'] ?? ''), 'campaign')) ?>" alt="Campaign image">
        <div style="margin-top:.8rem;display:flex;gap:.5rem;flex-wrap:wrap;">
          <span class="badge"><?= sanitize((string) ($campaign['category_name'] ?? '')) ?></span>
          <span class="status-badge"><?= sanitize((string) $campaign['status']) ?></span>
        </div>
        <h1 style="margin-top:.6rem;"><?= sanitize($campaign['title']) ?></h1>
        <p style="color:var(--text-soft)">By <?= sanitize($campaign['ngo_name']) ?> (<?= sanitize((string) $campaign['verification_status']) ?>)</p>
        <?php if (!$canDonate): ?>
          <p class="help-text" style="margin:0.75rem 0;padding:0.75rem 1rem;border-radius:12px;border:1px solid rgba(244,183,64,0.45);background:rgba(244,183,64,0.12);">This campaign is still <strong>pending review</strong>. Donations open after an administrator marks it approved or active.</p>
        <?php endif; ?>
        <p><?= nl2br(sanitize((string) $campaign['description'])) ?></p>
        <p>Start: <?= sanitize((string) $campaign['start_date']) ?> | End: <?= sanitize((string) $campaign['end_date']) ?></p>
        <h3 style="margin-top:1rem;">Campaign Updates</h3>
        <?php if (!$updates): ?><div class="empty-state">No updates yet.</div><?php endif; ?>
        <?php foreach ($updates as $u): ?>
          <div class="glass-card" style="padding:.8rem;margin-bottom:.6rem;">
            <strong><?= sanitize($u['update_title']) ?></strong><br>
            <small><?= sanitize((string) $u['created_at']) ?></small>
            <p><?= nl2br(sanitize($u['update_description'])) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
      <aside class="glass-card" style="padding:1.1rem;position:sticky;top:1rem;">
        <h3>Donation Summary</h3>
        <p>Target: PKR <?= number_format((float) $campaign['target_amount'], 2) ?></p>
        <p>Collected: PKR <?= number_format((float) $campaign['collected_amount'], 2) ?></p>
        <div class="progress-wrap"><div class="progress-bar" style="width:<?= number_format($progress, 2) ?>%;"></div></div>
        <p><?= number_format($progress, 2) ?>% funded</p>
        <?php if ($canDonate): ?>
          <a class="gradient-button" href="<?= sanitize($donateUrl) ?>">Donate Now</a>
          <?php if (!is_logged_in()): ?><p class="help-text">Please login to submit donation proof.</p><?php endif; ?>
        <?php else: ?>
          <p class="help-text" style="margin:0;">Donating is not available until this campaign is approved.</p>
        <?php endif; ?>
      </aside>
    </div>
  </div>
</section>
</main>
<?php require_once dirname(__DIR__) . '/includes/public_footer.php'; ?>
