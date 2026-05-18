<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/role_check.php';
require_once dirname(__DIR__) . '/includes/upload_helper.php';
require_once dirname(__DIR__) . '/includes/notification_helper.php';
require_once dirname(__DIR__) . '/includes/mail_helper.php';
require_role(['donor']);
require_once dirname(__DIR__) . '/includes/payment_method_helpers.php';
$pdo = db();
$campaignId = intval($_GET['campaign_id'] ?? $_POST['campaign_id'] ?? 0);
$stmt = $pdo->prepare('SELECT id, user_id FROM donor_profiles WHERE user_id = :user_id LIMIT 1');$stmt->execute(['user_id'=>(int)$authUser['id']]);$donorProfile=$stmt->fetch(); if(!$donorProfile) exit('Donor profile not found.');
$stmt = $pdo->prepare("SELECT c.*, np.ngo_name, u.id AS ngo_user_id, u.email AS ngo_email, u.full_name AS ngo_user_name FROM campaigns c INNER JOIN ngo_profiles np ON np.id = c.ngo_id INNER JOIN users u ON u.id = np.user_id WHERE c.id = :id AND c.status IN ('approved','active') LIMIT 1");$stmt->execute(['id'=>$campaignId]);$campaign=$stmt->fetch(); if(!$campaign) exit('Campaign unavailable for donation.');
$stmt=$pdo->prepare("SELECT * FROM ngo_payment_methods WHERE ngo_id=:ngo_id AND status='active' ORDER BY created_at DESC");$stmt->execute(['ngo_id'=>(int)$campaign['ngo_id']]);$methods=$stmt->fetchAll();
$errors = [];
$highlightPaymentMethod = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
     exit('Invalid CSRF token.');
 }
 if (!$methods) {
     $errors[] = 'No active payment methods are available for this campaign.';
 }
 $paymentMethodId = (int) ($_POST['payment_method_id'] ?? 0);
 $amount = (float) ($_POST['amount'] ?? 0);
 $tid = trim((string) ($_POST['transaction_reference'] ?? ''));
 $message = trim((string) ($_POST['donor_message'] ?? ''));
 if ($methods && $paymentMethodId <= 0) {
     $errors[] = 'Please select the NGO payment method you used for this donation.';
     $highlightPaymentMethod = true;
 } elseif ($paymentMethodId > 0) {
     $stmt = $pdo->prepare('SELECT id FROM ngo_payment_methods WHERE id=:id AND ngo_id=:ngo_id AND status=:status LIMIT 1');
     $stmt->execute(['id' => $paymentMethodId, 'ngo_id' => (int) $campaign['ngo_id'], 'status' => 'active']);
     if (!$stmt->fetch()) {
         $errors[] = 'The selected payment method is no longer available. Please choose another option.';
         $highlightPaymentMethod = true;
     }
 }
 if ($amount <= 0) {
     $errors[] = 'Amount must be greater than 0.';
 }
 if ($tid === '') {
     $errors[] = 'Transaction reference is required.';
 }
 if (empty($_FILES['proof_image']['name'])) {
     $errors[] = 'Proof image is required.';
 }
 $proofUrl=null;$proofFileId=null; if(!$errors){$upload=upload_to_imagekit($_FILES['proof_image'],'donation-proofs'); if(empty($upload['success'])){$errors[]=(string)($upload['message']??'Failed to upload proof image.');} else {$proofUrl=$upload['url'];$proofFileId=$upload['fileId'];}}
 if(!$errors){
  $stmt=$pdo->prepare('INSERT INTO donations (donor_id, ngo_id, campaign_id, payment_method_id, amount, transaction_reference, proof_image_url, proof_imagekit_file_id, donor_message, status) VALUES (:donor_id,:ngo_id,:campaign_id,:payment_method_id,:amount,:transaction_reference,:proof_image_url,:proof_imagekit_file_id,:donor_message,:status)');
  $stmt->execute(['donor_id'=>(int)$donorProfile['id'],'ngo_id'=>(int)$campaign['ngo_id'],'campaign_id'=>(int)$campaign['id'],'payment_method_id'=>$paymentMethodId,'amount'=>$amount,'transaction_reference'=>$tid,'proof_image_url'=>$proofUrl,'proof_imagekit_file_id'=>$proofFileId,'donor_message'=>$message?:null,'status'=>'pending']);
  $donationId=(int)$pdo->lastInsertId();
  $stmt=$pdo->prepare('INSERT INTO donation_status_history (donation_id, changed_by_user_id, old_status, new_status, note) VALUES (:donation_id,:changed_by_user_id,:old_status,:new_status,:note)');$stmt->execute(['donation_id'=>$donationId,'changed_by_user_id'=>(int)$authUser['id'],'old_status'=>null,'new_status'=>'pending','note'=>'Donation proof submitted by donor.']);
  create_notification($pdo,(int)$campaign['ngo_user_id'],'New Donation Proof Submitted','A donor submitted donation proof for campaign: '.$campaign['title'],'donation');
  send_new_donation_proof_email(['id'=>(int)$campaign['ngo_user_id'],'email'=>$campaign['ngo_email']],['donor_name'=>$authUser['full_name'],'campaign_title'=>$campaign['title'],'amount'=>(string)$amount,'transaction_reference'=>$tid]);
  send_donation_pending_email(['id'=>(int)$authUser['id'],'email'=>$authUser['email']],['campaign_title'=>$campaign['title'],'amount'=>(string)$amount,'transaction_reference'=>$tid]);
  log_activity($pdo,(int)$authUser['id'],'donation_submitted','donations',$donationId,'Manual donation proof submitted.');
  redirect('donor/my_donations.php');
 }
}

$selectedPaymentMethodId = (int) ($_POST['payment_method_id'] ?? 0);
if ($methods) {
    $validMethodIds = array_map(static fn(array $m): int => (int) $m['id'], $methods);
    $hasValidSelection = $selectedPaymentMethodId > 0
        && in_array($selectedPaymentMethodId, $validMethodIds, true);
    if (!$hasValidSelection && !$highlightPaymentMethod) {
        $selectedPaymentMethodId = (int) $methods[0]['id'];
    }
}

$progress=((float)$campaign['target_amount']>0)?min(100,((float)$campaign['collected_amount']/(float)$campaign['target_amount']*100)):0;
$pageTitle='Donate';
require_once dirname(__DIR__) . '/includes/dashboard_layout_start.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>
<h1 class="section-title">Donate to Campaign</h1>
<?php require dirname(__DIR__) . '/includes/flash_messages.php'; ?>
<?php if ($errors): ?>
<div class="toast error" role="alert">
  <strong>Please fix the following:</strong>
  <ul class="dn-form-error-list">
    <?php foreach ($errors as $err): ?>
      <li><?= sanitize($err) ?></li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>
<div class="grid" style="grid-template-columns:1fr;gap:1rem;">
<div class="glass-card" style="padding:1rem;"><h3>Step 1: Campaign Summary</h3><div class="grid" style="grid-template-columns:120px 1fr;align-items:center;"><img src="<?= sanitize(image_or_placeholder((string)($campaign['image_url']??''),'campaign')) ?>" alt="Campaign image" style="width:120px;height:90px;object-fit:cover;"><div><strong><?= sanitize($campaign['title']) ?></strong><p>NGO: <?= sanitize($campaign['ngo_name']) ?></p><p>Target: PKR <?= number_format((float)$campaign['target_amount'],2) ?> | Collected: PKR <?= number_format((float)$campaign['collected_amount'],2) ?></p><div class="progress-wrap"><div class="progress-bar" style="width:<?= number_format($progress,2) ?>%"></div></div></div></div></div>
<form method="post" enctype="multipart/form-data" class="form-card" id="donateForm" data-loading-button><input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()) ?>"><input type="hidden" name="campaign_id" value="<?= (int)$campaign['id'] ?>">
<div class="dn-pm-form-section<?= $highlightPaymentMethod ? ' dn-pm-form-section--error' : '' ?>" id="dnPmMethodSection">
  <h3>Step 2: Select NGO payment method</h3>
  <p class="help-text">Choose the account you sent money to. Copy the number into your banking app if needed.</p>
  <p id="dnPmMethodError" class="dn-field-error" role="alert"<?= $highlightPaymentMethod ? '' : ' hidden' ?>>Please select the NGO payment method you paid into before continuing.</p>
  <?php if (!$methods): ?>
    <div class="dn-pm-empty">
    <strong>No active payout methods</strong>
    <p style="margin:0.5rem 0 0;font-size:0.92rem;line-height:1.5;">This campaign does not have any active payment options yet. Please contact the NGO or try again later.</p>
  </div>
  <?php else: ?>
    <div class="dn-pm-select-grid">
      <?php foreach ($methods as $method):
          $tk = payment_method_type_key((string) $method['method_type']);
          $theme = 'dn-pm-theme--' . $tk;
          $instr = trim((string) ($method['instructions'] ?? ''));
          $isSelected = $selectedPaymentMethodId === (int) $method['id'];
          ?>
        <label class="dn-pm-select <?= sanitize($theme) ?><?= $isSelected ? ' is-selected' : '' ?>">
          <input type="radio" name="payment_method_id" value="<?= (int) $method['id'] ?>"<?= $isSelected ? ' checked' : '' ?>>
          <span class="dn-pm-select__face">
            <span class="dn-pm-select__selected-badge">Selected</span>
            <div class="dn-pm-select__top">
              <div class="dn-pm-mono" aria-hidden="true"><?= sanitize(payment_method_type_short((string) $method['method_type'])) ?></div>
              <div>
                <div class="dn-pm-select__name"><?= sanitize((string) $method['method_title']) ?></div>
                <p class="dn-pm-select__hint"><?= sanitize(payment_method_type_label((string) $method['method_type'])) ?></p>
              </div>
            </div>
            <div class="dn-pm-select__acct">
              <span><?= sanitize((string) $method['account_name']) ?></span>
              <code><?= sanitize((string) $method['account_number']) ?></code>
              <button type="button" class="outline-button dn-pm-copy" data-copy="<?= sanitize((string) $method['account_number']) ?>">Copy</button>
            </div>
            <?php if ($instr !== ''): ?>
              <p class="dn-pm-select__instr"><?= nl2br(sanitize($instr)) ?></p>
            <?php endif; ?>
          </span>
        </label>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<h3 style="margin-top:1.25rem;">Step 3: Enter donation proof</h3><div class="form-grid"><div class="form-group"><label>Amount</label><input name="amount" type="number" step="0.01" min="1" required></div><div class="form-group"><label>Transaction Reference / TID</label><input name="transaction_reference" required></div></div><div class="form-group"><label>Donor Message (optional)</label><textarea name="donor_message" rows="3"></textarea></div><div class="form-group"><label>Upload Payment Screenshot</label><input name="proof_image" id="proof_image" type="file" accept="image/*" required><img id="proofPreview" class="hidden" alt="Proof preview" style="margin-top:.75rem;max-height:180px;"></div>
<h3>Step 4: Submit</h3><p class="help-text">Your donation proof will be submitted and remain pending until NGO verifies payment.</p><button class="gradient-button" type="submit"<?= !$methods ? ' disabled' : '' ?>>Submit Donation Proof</button>
</form>
</div>
<div id="dnCopyToast" class="dn-copy-toast" role="status" aria-live="polite">Copied to clipboard</div>
<script>
(function () {
  var toast = document.getElementById('dnCopyToast');
  var t;
  function showToast() {
    if (!toast) return;
    toast.classList.add('is-visible');
    clearTimeout(t);
    t = setTimeout(function () { toast.classList.remove('is-visible'); }, 2200);
  }
  document.querySelectorAll('[data-copy]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      var v = btn.getAttribute('data-copy') || '';
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(v).then(showToast).catch(function () { window.prompt('Copy:', v); });
      } else {
        window.prompt('Copy account number:', v);
      }
    });
  });
  var fi = document.getElementById('proof_image');
  var pr = document.getElementById('proofPreview');
  if (fi && pr) {
    fi.addEventListener('change', function () {
      var f = fi.files && fi.files[0];
      if (!f) return;
      pr.src = URL.createObjectURL(f);
      pr.classList.remove('hidden');
    });
  }

  var donateForm = document.getElementById('donateForm');
  var pmSection = document.getElementById('dnPmMethodSection');
  var pmError = document.getElementById('dnPmMethodError');
  var pmRadios = document.querySelectorAll('input[name="payment_method_id"]');

  function syncPaymentMethodUi() {
    pmRadios.forEach(function (radio) {
      var label = radio.closest('.dn-pm-select');
      if (label) {
        label.classList.toggle('is-selected', radio.checked);
      }
    });
  }

  function clearPaymentMethodError() {
    if (pmSection) {
      pmSection.classList.remove('dn-pm-form-section--error');
    }
    if (pmError) {
      pmError.hidden = true;
    }
  }

  function showPaymentMethodError() {
    if (pmSection) {
      pmSection.classList.add('dn-pm-form-section--error');
      pmSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    if (pmError) {
      pmError.hidden = false;
    }
  }

  if (donateForm && pmRadios.length) {
    var checked = donateForm.querySelector('input[name="payment_method_id"]:checked');
    if (!checked) {
      pmRadios[0].checked = true;
    }
    syncPaymentMethodUi();
    clearPaymentMethodError();

    pmRadios.forEach(function (radio) {
      radio.addEventListener('change', function () {
        syncPaymentMethodUi();
        clearPaymentMethodError();
      });
    });

    donateForm.addEventListener('submit', function (e) {
      var selected = donateForm.querySelector('input[name="payment_method_id"]:checked');
      if (!selected) {
        e.preventDefault();
        showPaymentMethodError();
      }
    });
  }
})();
</script>
<?php require_once dirname(__DIR__) . '/includes/dashboard_layout_end.php'; ?>
