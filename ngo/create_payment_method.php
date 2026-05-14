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
    exit('Only verified NGOs can add payment methods.');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        exit('Invalid CSRF token.');
    }
    $methodType = trim((string) ($_POST['method_type'] ?? ''));
    $methodTitle = trim((string) ($_POST['method_title'] ?? ''));
    $accountName = trim((string) ($_POST['account_name'] ?? ''));
    $accountNumber = trim((string) ($_POST['account_number'] ?? ''));
    $bankName = trim((string) ($_POST['bank_name'] ?? ''));
    $instructions = trim((string) ($_POST['instructions'] ?? ''));
    if (!in_array($methodType, ['easypaisa', 'jazzcash', 'bank', 'other'], true)) {
        $errors[] = 'Invalid method type.';
    }
    if ($methodTitle === '' || $accountName === '' || $accountNumber === '') {
        $errors[] = 'Required fields are missing.';
    }
    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO ngo_payment_methods (ngo_id, method_type, method_title, account_name, account_number, bank_name, instructions, status) VALUES (:n, :t, :mt, :an, :acc, :bn, :ins, :s)');
        $stmt->execute([
            'n' => (int) $ngo['id'],
            't' => $methodType,
            'mt' => $methodTitle,
            'an' => $accountName,
            'acc' => $accountNumber,
            'bn' => $bankName ?: null,
            'ins' => $instructions ?: null,
            's' => 'active',
        ]);
        log_activity($pdo, (int) $authUser['id'], 'payment_method_created', 'ngo_payment_methods', (int) $pdo->lastInsertId(), 'NGO payment method created');
        redirect('ngo/payment_methods.php');
    }
}

$pageTitle = 'Create Payment Method';
require_once dirname(__DIR__) . '/includes/dashboard_layout_start.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
$types = payment_method_types_meta();
$first = true;
?>
<div class="dn-payment-page">
  <h1 class="section-title">Add payment method</h1>
  <?php if ($errors): ?><div class="toast error"><?= sanitize(implode(' | ', $errors)) ?></div><?php endif; ?>

  <div class="dn-payment-hero" style="margin-bottom:1.25rem;">
    <div class="dn-payment-hero__icon" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
    </div>
    <div>
      <p class="dn-payment-hero__title">What donors will use to pay you</p>
      <p class="dn-payment-hero__text">Pick the channel that matches this account, then add the exact details you want copied into their banking or wallet app.</p>
    </div>
  </div>

  <form method="post" class="form-card">
    <input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()) ?>">

    <div class="dn-pm-form-section">
      <h2>1. Channel type</h2>
      <p class="help-text">This controls the color label donors see; it does not change how money moves.</p>
      <div class="dn-pm-type-grid">
        <?php foreach ($types as $opt):
            $tid = 'pm_type_' . $opt['value'];
            $theme = 'dn-pm-theme--' . $opt['value'];
            ?>
          <label class="dn-pm-type-option <?= sanitize($theme) ?>">
            <input type="radio" name="method_type" value="<?= sanitize($opt['value']) ?>" id="<?= sanitize($tid) ?>" required <?= $first ? 'checked' : '' ?>>
            <span class="dn-pm-type-option__face">
              <span class="dn-pm-mono" aria-hidden="true"><?= sanitize($opt['short']) ?></span>
              <span class="dn-pm-type-option__label"><?= sanitize($opt['label']) ?></span>
              <span class="dn-pm-type-option__hint"><?= sanitize($opt['hint']) ?></span>
            </span>
          </label>
        <?php $first = false; endforeach; ?>
      </div>
    </div>

    <div class="dn-pm-form-section">
      <h2>2. Display title</h2>
      <p class="help-text">Short name donors recognize, e.g. “Meezan Current” or “Org — JazzCash”.</p>
      <div class="form-group">
        <label for="method_title">Method title</label>
        <input id="method_title" name="method_title" required placeholder="e.g. Meezan Bank — Donations account">
      </div>
    </div>

    <div class="dn-pm-form-section">
      <h2>3. Receiver details</h2>
      <div class="form-grid">
        <div class="form-group">
          <label for="account_name">Account title (as on bank / wallet)</label>
          <input id="account_name" name="account_name" required>
        </div>
        <div class="form-group">
          <label for="account_number">Account / wallet number</label>
          <input id="account_number" name="account_number" required inputmode="numeric" autocomplete="off">
        </div>
      </div>
      <div class="form-group">
        <label for="bank_name">Bank name (optional)</label>
        <input id="bank_name" name="bank_name" placeholder="If applicable">
      </div>
      <div class="form-group">
        <label for="instructions">Extra instructions (optional)</label>
        <textarea id="instructions" name="instructions" rows="4" placeholder="IBAN, branch, reference text, or wallet limits"></textarea>
      </div>
    </div>

    <button class="gradient-button" type="submit">Save payment method</button>
  </form>
</div>
<?php require_once dirname(__DIR__) . '/includes/dashboard_layout_end.php'; ?>
