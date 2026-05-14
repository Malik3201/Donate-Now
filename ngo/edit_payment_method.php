<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/role_check.php';
require_once dirname(__DIR__) . '/includes/payment_method_helpers.php';
require_role(['ngo']);
$pdo = db();
$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT id FROM ngo_profiles WHERE user_id = :u LIMIT 1');
$stmt->execute(['u' => (int) $authUser['id']]);
$ngo = $stmt->fetch();
if (!$ngo) {
    exit('NGO profile not found.');
}
$stmt = $pdo->prepare('SELECT * FROM ngo_payment_methods WHERE id = :id AND ngo_id = :n LIMIT 1');
$stmt->execute(['id' => $id, 'n' => (int) $ngo['id']]);
$method = $stmt->fetch();
if (!$method) {
    exit('Payment method not found or unauthorized.');
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
    $status = trim((string) ($_POST['status'] ?? 'active'));
    if (!in_array($methodType, ['easypaisa', 'jazzcash', 'bank', 'other'], true)) {
        $errors[] = 'Invalid method type.';
    }
    if ($methodTitle === '' || $accountName === '' || $accountNumber === '') {
        $errors[] = 'Required fields missing.';
    }
    if (!in_array($status, ['active', 'inactive'], true)) {
        $errors[] = 'Invalid status.';
    }
    if (!$errors) {
        $stmt = $pdo->prepare('UPDATE ngo_payment_methods SET method_type = :t, method_title = :mt, account_name = :an, account_number = :acc, bank_name = :bn, instructions = :ins, status = :s, updated_at = NOW() WHERE id = :id AND ngo_id = :n');
        $stmt->execute([
            't' => $methodType,
            'mt' => $methodTitle,
            'an' => $accountName,
            'acc' => $accountNumber,
            'bn' => $bankName ?: null,
            'ins' => $instructions ?: null,
            's' => $status,
            'id' => $id,
            'n' => (int) $ngo['id'],
        ]);
        redirect('ngo/payment_methods.php');
    }
}

$pageTitle = 'Edit Payment Method';
require_once dirname(__DIR__) . '/includes/dashboard_layout_start.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
$types = payment_method_types_meta();
$currentType = payment_method_type_key((string) $method['method_type']);
$isActive = ($method['status'] ?? '') === 'active';
?>
<div class="dn-payment-page">
  <h1 class="section-title">Edit payment method</h1>
  <?php if ($errors): ?><div class="toast error"><?= sanitize(implode(' | ', $errors)) ?></div><?php endif; ?>

  <form method="post" class="form-card">
    <input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()) ?>">

    <div class="dn-pm-form-section">
      <h2>Channel type</h2>
      <div class="dn-pm-type-grid">
        <?php foreach ($types as $opt):
            $tid = 'pm_type_' . $opt['value'];
            $theme = 'dn-pm-theme--' . $opt['value'];
            $checked = $currentType === $opt['value'];
            ?>
          <label class="dn-pm-type-option <?= sanitize($theme) ?>">
            <input type="radio" name="method_type" value="<?= sanitize($opt['value']) ?>" id="<?= sanitize($tid) ?>" <?= $checked ? 'checked' : '' ?>>
            <span class="dn-pm-type-option__face">
              <span class="dn-pm-mono" aria-hidden="true"><?= sanitize($opt['short']) ?></span>
              <span class="dn-pm-type-option__label"><?= sanitize($opt['label']) ?></span>
              <span class="dn-pm-type-option__hint"><?= sanitize($opt['hint']) ?></span>
            </span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="dn-pm-form-section">
      <h2>Display title</h2>
      <div class="form-group">
        <label for="method_title">Method title</label>
        <input id="method_title" name="method_title" value="<?= sanitize((string) $method['method_title']) ?>" required>
      </div>
    </div>

    <div class="dn-pm-form-section">
      <h2>Receiver details</h2>
      <div class="form-grid">
        <div class="form-group">
          <label for="account_name">Account title</label>
          <input id="account_name" name="account_name" value="<?= sanitize((string) $method['account_name']) ?>" required>
        </div>
        <div class="form-group">
          <label for="account_number">Account / wallet number</label>
          <input id="account_number" name="account_number" value="<?= sanitize((string) $method['account_number']) ?>" required inputmode="numeric" autocomplete="off">
        </div>
      </div>
      <div class="form-group">
        <label for="bank_name">Bank name</label>
        <input id="bank_name" name="bank_name" value="<?= sanitize((string) ($method['bank_name'] ?? '')) ?>">
      </div>
      <div class="form-group">
        <label for="instructions">Instructions</label>
        <textarea id="instructions" name="instructions" rows="4"><?= sanitize((string) ($method['instructions'] ?? '')) ?></textarea>
      </div>
    </div>

    <div class="dn-pm-form-section">
      <h2>Availability</h2>
      <p class="help-text">Paused methods are hidden from the donation form.</p>
      <div class="dn-pm-segment" role="radiogroup" aria-label="Method availability">
        <label>
          <input type="radio" name="status" value="active" <?= $isActive ? 'checked' : '' ?>>
          <span>Active</span>
        </label>
        <label>
          <input type="radio" name="status" value="inactive" <?= !$isActive ? 'checked' : '' ?>>
          <span>Paused</span>
        </label>
      </div>
    </div>

    <button class="gradient-button" type="submit">Save changes</button>
  </form>
</div>
<?php require_once dirname(__DIR__) . '/includes/dashboard_layout_end.php'; ?>
