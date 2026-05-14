<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/role_check.php';
require_once dirname(__DIR__) . '/includes/payment_method_helpers.php';
require_role(['ngo']);
$pdo = db();
$stmt = $pdo->prepare('SELECT id, verification_status FROM ngo_profiles WHERE user_id = :u LIMIT 1');
$stmt->execute(['u' => (int) $authUser['id']]);
$ngo = $stmt->fetch();
if (!$ngo || $ngo['verification_status'] !== 'verified') {
    exit('Only verified NGOs can manage payment methods.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        exit('Invalid CSRF token.');
    }
    $id = (int) ($_POST['id'] ?? 0);
    $status = trim((string) ($_POST['status'] ?? ''));
    if ($id > 0 && in_array($status, ['active', 'inactive'], true)) {
        $stmt = $pdo->prepare('UPDATE ngo_payment_methods SET status = :s, updated_at = NOW() WHERE id = :id AND ngo_id = :n');
        $stmt->execute(['s' => $status, 'id' => $id, 'n' => (int) $ngo['id']]);
    }
    redirect('ngo/payment_methods.php');
}

$stmt = $pdo->prepare('SELECT * FROM ngo_payment_methods WHERE ngo_id = :n ORDER BY created_at DESC');
$stmt->execute(['n' => (int) $ngo['id']]);
$methods = $stmt->fetchAll();

$pageTitle = 'Payment Methods';
require_once dirname(__DIR__) . '/includes/dashboard_layout_start.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>
<div class="dn-payment-page">
  <div class="dn-payment-hero">
    <div class="dn-payment-hero__icon" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
    </div>
    <div>
      <p class="dn-payment-hero__title">Payout &amp; receiving accounts</p>
      <p class="dn-payment-hero__text">These details are shown to donors only when they submit a donation for your campaigns. Keep titles clear (for example “Meezan — Title”) and double-check account numbers.</p>
    </div>
  </div>

  <div class="dn-payment-toolbar">
    <p class="help-text" style="margin:0;max-width:36rem;">Use accurate titles and instructions so donors can complete transfers without errors.</p>
    <a class="gradient-button" href="<?= APP_URL ?>/ngo/create_payment_method.php">Add payment method</a>
  </div>

  <div class="dn-pm-grid">
    <?php if (!$methods): ?>
    <div class="dn-pm-empty">
        <strong>No payout methods yet</strong>
        <p style="margin:0.5rem 0 0;font-size:0.92rem;line-height:1.5;">Add at least one active method so donors can send you proofs of payment.</p>
      </div>
    <?php endif; ?>
    <?php foreach ($methods as $m):
        $typeKey = payment_method_type_key((string) $m['method_type']);
        $themeClass = 'dn-pm-theme--' . $typeKey;
        $bank = trim((string) ($m['bank_name'] ?? ''));
        $instr = trim((string) ($m['instructions'] ?? ''));
        $isActive = ($m['status'] ?? '') === 'active';
        ?>
      <article class="dn-pm-card <?= sanitize($themeClass) ?>">
        <div class="dn-pm-card__head">
          <div class="dn-pm-mono" aria-hidden="true"><?= sanitize(payment_method_type_short((string) $m['method_type'])) ?></div>
          <div class="dn-pm-card__titles">
            <h2 class="dn-pm-card__title"><?= sanitize((string) $m['method_title']) ?></h2>
            <span class="dn-pm-card__type-pill"><?= sanitize(payment_method_type_label((string) $m['method_type'])) ?></span>
          </div>
        </div>
        <div class="dn-pm-card__body">
          <div class="dn-pm-kv">
            <span class="dn-pm-kv__k">Account name</span>
            <span class="dn-pm-kv__v"><?= sanitize((string) $m['account_name']) ?></span>
          </div>
          <div class="dn-pm-kv">
            <span class="dn-pm-kv__k">Account number</span>
            <span class="dn-pm-kv__v dn-pm-kv__v--mono"><?= sanitize((string) $m['account_number']) ?></span>
          </div>
          <?php if ($bank !== ''): ?>
            <div class="dn-pm-kv">
              <span class="dn-pm-kv__k">Bank</span>
              <span class="dn-pm-kv__v"><?= sanitize($bank) ?></span>
            </div>
          <?php endif; ?>
          <?php if ($instr !== ''): ?>
            <div class="dn-pm-kv">
              <span class="dn-pm-kv__k">Instructions</span>
              <span class="dn-pm-kv__v"><?= nl2br(sanitize($instr)) ?></span>
            </div>
          <?php endif; ?>
        </div>
        <div class="dn-pm-card__foot">
          <div class="dn-pm-status-wrap">
            <small>Availability</small>
            <form method="post" class="dn-pm-segment" aria-label="Toggle method availability">
              <input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()) ?>">
              <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
              <label>
                <input type="radio" name="status" value="active" <?= $isActive ? 'checked' : '' ?> onchange="this.form.requestSubmit()">
                <span>Active</span>
              </label>
              <label>
                <input type="radio" name="status" value="inactive" <?= !$isActive ? 'checked' : '' ?> onchange="this.form.requestSubmit()">
                <span>Paused</span>
              </label>
            </form>
          </div>
          <div class="dn-pm-card__actions">
            <a class="outline-button" href="<?= APP_URL ?>/ngo/edit_payment_method.php?id=<?= (int) $m['id'] ?>">Edit details</a>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</div>
<?php require_once dirname(__DIR__) . '/includes/dashboard_layout_end.php'; ?>
