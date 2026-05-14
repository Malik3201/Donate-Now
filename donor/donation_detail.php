<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/role_check.php';
require_role(['donor']);
require_once dirname(__DIR__) . '/includes/payment_method_helpers.php';
$pdo = db();$id=intval($_GET['id']??0);
$stmt=$pdo->prepare('SELECT id FROM donor_profiles WHERE user_id=:u LIMIT 1');$stmt->execute(['u'=>(int)$authUser['id']]);$donor=$stmt->fetch(); if(!$donor) exit('Donor profile not found.');
$stmt=$pdo->prepare("SELECT d.*, c.title AS campaign_title, n.ngo_name, m.method_type,m.method_title,m.account_name,m.account_number,m.bank_name,m.instructions FROM donations d INNER JOIN campaigns c ON c.id=d.campaign_id INNER JOIN ngo_profiles n ON n.id=d.ngo_id INNER JOIN ngo_payment_methods m ON m.id=d.payment_method_id WHERE d.id=:id AND d.donor_id=:d LIMIT 1");$stmt->execute(['id'=>$id,'d'=>(int)$donor['id']]);$donation=$stmt->fetch(); if(!$donation) exit('Donation not found or unauthorized.');
$stmt=$pdo->prepare('SELECT * FROM donation_status_history WHERE donation_id=:id ORDER BY created_at ASC');$stmt->execute(['id'=>$id]);$timeline=$stmt->fetchAll();
$pageTitle='Donation Detail'; require_once dirname(__DIR__) . '/includes/dashboard_layout_start.php'; require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>
<h1 class="section-title">Donation Detail</h1>
<div class="grid" style="grid-template-columns:1fr 1fr;">
<div class="glass-card" style="padding:1rem;"><h3>Donation Info</h3><p>Campaign: <?= sanitize($donation['campaign_title']) ?></p><p>NGO: <?= sanitize($donation['ngo_name']) ?></p><p>Amount: PKR <?= number_format((float)$donation['amount'],2) ?></p><p>TID: <?= sanitize($donation['transaction_reference']) ?></p><p>Status: <span class="status-badge"><?= sanitize($donation['status']) ?></span></p><p>NGO Note: <?= sanitize((string)($donation['ngo_verification_note'] ?? '')) ?></p><p><a class="outline-button" href="<?= APP_URL ?>/reports/create_report.php?donation_id=<?= (int)$donation['id'] ?>">Report</a></p></div>
<div class="glass-card" style="padding:1rem;"><h3>Payment method</h3>
      <?php
      $dtk = payment_method_type_key((string) $donation['method_type']);
      $dtheme = 'dn-pm-theme--' . $dtk;
      $dbank = trim((string) ($donation['bank_name'] ?? ''));
      $dinstr = trim((string) ($donation['instructions'] ?? ''));
      ?>
      <div class="dn-pm-readonly <?= sanitize($dtheme) ?>">
        <div class="dn-pm-readonly__accent" aria-hidden="true"></div>
        <div class="dn-pm-card__head">
          <div class="dn-pm-mono" aria-hidden="true"><?= sanitize(payment_method_type_short((string) $donation['method_type'])) ?></div>
          <div class="dn-pm-card__titles">
            <h2 class="dn-pm-card__title" style="font-size:1.05rem;"><?= sanitize((string) $donation['method_title']) ?></h2>
            <span class="dn-pm-card__type-pill"><?= sanitize(payment_method_type_label((string) $donation['method_type'])) ?></span>
          </div>
        </div>
        <div class="dn-pm-card__body">
          <div class="dn-pm-kv">
            <span class="dn-pm-kv__k">Account</span>
            <span class="dn-pm-kv__v"><?= sanitize((string) $donation['account_name']) ?> · <span class="dn-pm-kv__v--mono"><?= sanitize((string) $donation['account_number']) ?></span></span>
          </div>
          <?php if ($dbank !== ''): ?>
            <div class="dn-pm-kv">
              <span class="dn-pm-kv__k">Bank</span>
              <span class="dn-pm-kv__v"><?= sanitize($dbank) ?></span>
            </div>
          <?php endif; ?>
          <?php if ($dinstr !== ''): ?>
            <div class="dn-pm-kv">
              <span class="dn-pm-kv__k">Instructions</span>
              <span class="dn-pm-kv__v"><?= nl2br(sanitize($dinstr)) ?></span>
            </div>
          <?php endif; ?>
        </div>
        <p style="margin:0.75rem 0 0;font-size:0.88rem;"><a href="<?= sanitize($donation['proof_image_url']) ?>" target="_blank" rel="noopener">View payment screenshot</a></p>
      </div>
    </div>
</div>
<div class="glass-card" style="padding:1rem;margin-top:1rem;"><h3>Status Timeline</h3><?php if(!$timeline): ?><p>No timeline available.</p><?php endif; ?><?php foreach($timeline as $t): ?><div style="padding:.5rem 0;border-bottom:1px solid var(--border-muted);"><strong><?= sanitize((string)$t['new_status']) ?></strong> <small><?= sanitize((string)$t['created_at']) ?></small><p><?= sanitize((string)$t['note']) ?></p></div><?php endforeach; ?></div>
<?php require_once dirname(__DIR__) . '/includes/dashboard_layout_end.php'; ?>
