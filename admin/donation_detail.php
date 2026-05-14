<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/role_check.php';
require_once dirname(__DIR__) . '/includes/payment_method_helpers.php';
require_role(['admin']);

$pdo = db();
$id = (int) ($_GET['id'] ?? 0);

$sql = 'SELECT d.*,
        c.id AS campaign_id,
        c.title AS campaign_title,
        du.id AS donor_user_id,
        du.full_name AS donor_name,
        du.email AS donor_email,
        du.phone AS donor_phone,
        uu.id AS ngo_user_id,
        np.ngo_name,
        uu.full_name AS ngo_user_name,
        uu.email AS ngo_email,
        uu.phone AS ngo_phone,
        pm.method_title,
        pm.method_type,
        pm.account_name,
        pm.account_number,
        pm.bank_name,
        pm.instructions
        FROM donations d
        INNER JOIN campaigns c ON c.id = d.campaign_id
        INNER JOIN donor_profiles dp ON dp.id = d.donor_id
        INNER JOIN users du ON du.id = dp.user_id
        INNER JOIN ngo_profiles np ON np.id = d.ngo_id
        INNER JOIN users uu ON uu.id = np.user_id
        INNER JOIN ngo_payment_methods pm ON pm.id = d.payment_method_id
        WHERE d.id = :id LIMIT 1';

$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $id]);
$donation = $stmt->fetch();
if (!$donation) {
    exit('Donation not found.');
}

$stmt = $pdo->prepare(
    'SELECT h.*, u.full_name FROM donation_status_history h
     INNER JOIN users u ON u.id = h.changed_by_user_id
     WHERE h.donation_id = :id
     ORDER BY h.created_at DESC'
);
$stmt->execute(['id' => $id]);
$history = $stmt->fetchAll();

$atk = payment_method_type_key((string) $donation['method_type']);
$atheme = 'dn-pm-theme--' . $atk;
$abank = trim((string) ($donation['bank_name'] ?? ''));
$ainstr = trim((string) ($donation['instructions'] ?? ''));
$donorMsg = trim((string) ($donation['donor_message'] ?? ''));
$ngoNote = trim((string) ($donation['ngo_verification_note'] ?? ''));

$pageTitle = 'Donation Detail';
require_once dirname(__DIR__) . '/includes/dashboard_layout_start.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';

$campaignId = (int) ($donation['campaign_id'] ?? 0);
$donorUserId = (int) ($donation['donor_user_id'] ?? 0);
$ngoUserId = (int) ($donation['ngo_user_id'] ?? 0);
?>

<div class="dn-admin-donation-detail">
  <div class="dn-admin-donation-detail__head">
    <h1 class="section-title">Donation detail</h1>
    <div class="dn-admin-donation-detail__head-actions">
      <a class="outline-button" href="<?= sanitize(APP_URL . '/admin/all_donations.php') ?>">All donations</a>
      <?php if ($campaignId > 0): ?>
        <a class="outline-button" href="<?= sanitize(APP_URL . '/admin/campaign_detail.php?id=' . $campaignId) ?>">Campaign</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="dn-admin-donation-detail__grid">
    <section class="dn-admin-donation-detail__card glass-card" aria-labelledby="parties-heading">
      <h2 id="parties-heading" class="dn-admin-donation-detail__card-title">Donor &amp; NGO</h2>

      <div class="dn-admin-donation-detail__party dn-admin-donation-detail__party--donor">
        <h3 class="dn-admin-donation-detail__party-label">Sender (donor)</h3>
        <dl class="dn-admin-donation-detail__dl">
          <div class="dn-admin-donation-detail__dl-row"><dt>Name</dt><dd><?= sanitize((string) $donation['donor_name']) ?></dd></div>
          <div class="dn-admin-donation-detail__dl-row"><dt>Email</dt><dd><?= sanitize((string) $donation['donor_email']) ?></dd></div>
          <div class="dn-admin-donation-detail__dl-row"><dt>Phone</dt><dd><?= sanitize((string) ($donation['donor_phone'] ?? '—')) ?></dd></div>
        </dl>
        <?php if ($donorUserId > 0): ?>
          <p class="dn-admin-donation-detail__party-link"><a class="outline-button" href="<?= sanitize(APP_URL . '/admin/user_detail.php?id=' . $donorUserId) ?>">Open donor user</a></p>
        <?php endif; ?>
      </div>

      <div class="dn-admin-donation-detail__party dn-admin-donation-detail__party--ngo">
        <h3 class="dn-admin-donation-detail__party-label">Receiver (NGO)</h3>
        <dl class="dn-admin-donation-detail__dl">
          <div class="dn-admin-donation-detail__dl-row"><dt>Organization</dt><dd><?= sanitize((string) $donation['ngo_name']) ?></dd></div>
          <div class="dn-admin-donation-detail__dl-row"><dt>Contact name</dt><dd><?= sanitize((string) ($donation['ngo_user_name'] ?? '—')) ?></dd></div>
          <div class="dn-admin-donation-detail__dl-row"><dt>Email</dt><dd><?= sanitize((string) $donation['ngo_email']) ?></dd></div>
          <div class="dn-admin-donation-detail__dl-row"><dt>Phone</dt><dd><?= sanitize((string) ($donation['ngo_phone'] ?? '—')) ?></dd></div>
        </dl>
        <?php if ($ngoUserId > 0): ?>
          <p class="dn-admin-donation-detail__party-link"><a class="outline-button" href="<?= sanitize(APP_URL . '/admin/user_detail.php?id=' . $ngoUserId) ?>">Open NGO account</a></p>
        <?php endif; ?>
      </div>
    </section>

    <section class="dn-admin-donation-detail__card glass-card" aria-labelledby="txn-heading">
      <h2 id="txn-heading" class="dn-admin-donation-detail__card-title">Transaction</h2>

      <p class="dn-admin-donation-detail__campaign">
        <span class="dn-admin-donation-detail__muted">Campaign</span><br>
        <strong><?= sanitize((string) $donation['campaign_title']) ?></strong>
      </p>

      <div class="dn-admin-donation-detail__amount-block">
        <span class="dn-admin-donation-detail__muted">Amount</span>
        <p class="dn-admin-donation-detail__amount">PKR <?= number_format((float) $donation['amount'], 2) ?></p>
      </div>

      <dl class="dn-admin-donation-detail__dl dn-admin-donation-detail__dl--compact">
        <div class="dn-admin-donation-detail__dl-row"><dt>Transaction ID</dt><dd class="dn-admin-donation-detail__mono"><?= sanitize((string) $donation['transaction_reference']) ?></dd></div>
        <div class="dn-admin-donation-detail__dl-row"><dt>Status</dt><dd><?= dash_status_badge((string) ($donation['status'] ?? '')) ?></dd></div>
        <div class="dn-admin-donation-detail__dl-row"><dt>Submitted</dt><dd><?= sanitize((string) ($donation['created_at'] ?? '—')) ?></dd></div>
      </dl>

      <?php if ($donorMsg !== ''): ?>
        <div class="dn-admin-donation-detail__notebox">
          <span class="dn-admin-donation-detail__muted">Donor message</span>
          <p><?= nl2br(sanitize($donorMsg)) ?></p>
        </div>
      <?php endif; ?>

      <?php if ($ngoNote !== ''): ?>
        <div class="dn-admin-donation-detail__notebox dn-admin-donation-detail__notebox--ngo">
          <span class="dn-admin-donation-detail__muted">NGO verification note</span>
          <p><?= nl2br(sanitize($ngoNote)) ?></p>
        </div>
      <?php endif; ?>

      <h3 class="dn-admin-donation-detail__subhead">Payout destination</h3>
      <div class="dn-pm-readonly <?= sanitize($atheme) ?>">
        <div class="dn-pm-readonly__accent" aria-hidden="true"></div>
        <div class="dn-pm-card__head">
          <div class="dn-pm-mono" aria-hidden="true"><?= sanitize(payment_method_type_short((string) $donation['method_type'])) ?></div>
          <div class="dn-pm-card__titles">
            <h4 class="dn-pm-card__title" style="font-size:1.05rem;margin:0;"><?= sanitize((string) $donation['method_title']) ?></h4>
            <span class="dn-pm-card__type-pill"><?= sanitize(payment_method_type_label((string) $donation['method_type'])) ?></span>
          </div>
        </div>
        <div class="dn-pm-card__body">
          <div class="dn-pm-kv">
            <span class="dn-pm-kv__k">Account</span>
            <span class="dn-pm-kv__v"><?= sanitize((string) $donation['account_name']) ?> · <span class="dn-pm-kv__v--mono"><?= sanitize((string) $donation['account_number']) ?></span></span>
          </div>
          <?php if ($abank !== ''): ?>
            <div class="dn-pm-kv">
              <span class="dn-pm-kv__k">Bank</span>
              <span class="dn-pm-kv__v"><?= sanitize($abank) ?></span>
            </div>
          <?php endif; ?>
          <?php if ($ainstr !== ''): ?>
            <div class="dn-pm-kv">
              <span class="dn-pm-kv__k">Instructions</span>
              <span class="dn-pm-kv__v"><?= nl2br(sanitize($ainstr)) ?></span>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <p class="dn-admin-donation-detail__proof">
        <a class="gradient-button" target="_blank" rel="noopener" href="<?= sanitize((string) $donation['proof_image_url']) ?>">View payment screenshot</a>
      </p>
    </section>
  </div>

  <section class="dn-admin-donation-detail__card glass-card dn-admin-donation-detail__history-wrap" aria-labelledby="history-heading">
    <h2 id="history-heading" class="dn-admin-donation-detail__card-title">Status history</h2>
    <?php if (!$history): ?>
      <p class="dn-admin-donation-detail__empty">No history rows yet.</p>
    <?php else: ?>
      <ol class="dn-admin-donation-detail__timeline">
        <?php foreach ($history as $h): ?>
          <li class="dn-admin-donation-detail__timeline-item">
            <div class="dn-admin-donation-detail__timeline-body">
              <div class="dn-admin-donation-detail__timeline-head">
                <?= dash_status_badge((string) ($h['new_status'] ?? '')) ?>
                <time class="dn-admin-donation-detail__timeline-time"><?= sanitize((string) ($h['created_at'] ?? '')) ?></time>
              </div>
              <p class="dn-admin-donation-detail__timeline-by">by <?= sanitize((string) ($h['full_name'] ?? '')) ?></p>
              <?php if (trim((string) ($h['note'] ?? '')) !== ''): ?>
                <p class="dn-admin-donation-detail__timeline-note"><?= nl2br(sanitize((string) $h['note'])) ?></p>
              <?php endif; ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ol>
    <?php endif; ?>
  </section>
</div>

<?php require_once dirname(__DIR__) . '/includes/dashboard_layout_end.php'; ?>
