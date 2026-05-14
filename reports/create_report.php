<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/upload_helper.php';
require_once dirname(__DIR__) . '/includes/report_functions.php';

$pdo = db();
$types = ['fake_payment', 'fake_ngo', 'fake_campaign', 'abuse', 'fraud', 'technical_issue', 'other'];
$typeLabels = [
    'fake_payment' => 'Fake / suspicious payment',
    'fake_ngo' => 'Fake NGO',
    'fake_campaign' => 'Fake campaign',
    'abuse' => 'Abuse / harassment',
    'fraud' => 'Fraud',
    'technical_issue' => 'Technical issue',
    'other' => 'Other',
];

$role = strtolower((string) ($authUser['role'] ?? ''));
$userId = (int) $authUser['id'];

/** @var list<array{id:int,title:string}> */
$campaignChoices = [];
/** @var array<string, array{volunteer: list<array{user_id:int,label:string}>, donor: list<array{user_id:int,label:string}>}> */
$ngoPeopleByCampaign = [];
/** @var array<string, list<array{id:int,label:string}>> */
$donorDonationsByCampaign = [];

if ($role === 'donor') {
    $stmt = $pdo->prepare(
        'SELECT DISTINCT c.id, c.title FROM donations d
         INNER JOIN campaigns c ON c.id = d.campaign_id
         INNER JOIN donor_profiles dp ON dp.id = d.donor_id
         WHERE dp.user_id = :u
         ORDER BY c.title ASC'
    );
    $stmt->execute(['u' => $userId]);
    $campaignChoices = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    if ($campaignChoices === []) {
        $stmt = $pdo->query(
            "SELECT id, title FROM campaigns WHERE status IN ('pending','approved','active') ORDER BY created_at DESC LIMIT 120"
        );
        $campaignChoices = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    $stmt = $pdo->prepare(
        'SELECT d.campaign_id, d.id, d.amount, d.status, d.created_at
         FROM donations d
         INNER JOIN donor_profiles dp ON dp.id = d.donor_id
         WHERE dp.user_id = :u
         ORDER BY d.created_at DESC'
    );
    $stmt->execute(['u' => $userId]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $cid = (string) (int) $row['campaign_id'];
        if (!isset($donorDonationsByCampaign[$cid])) {
            $donorDonationsByCampaign[$cid] = [];
        }
        $donorDonationsByCampaign[$cid][] = [
            'id' => (int) $row['id'],
            'label' => 'Donation PKR ' . number_format((float) $row['amount'], 0) . ' · ' . $row['status'] . ' · ' . substr((string) $row['created_at'], 0, 10),
        ];
    }
} elseif ($role === 'volunteer') {
    $stmt = $pdo->prepare(
        'SELECT DISTINCT c.id, c.title FROM volunteer_campaigns vc
         INNER JOIN campaigns c ON c.id = vc.campaign_id
         INNER JOIN volunteer_profiles vp ON vp.id = vc.volunteer_id
         WHERE vp.user_id = :u AND vc.status IN (\'pending\',\'accepted\')
         ORDER BY c.title ASC'
    );
    $stmt->execute(['u' => $userId]);
    $campaignChoices = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    if ($campaignChoices === []) {
        $stmt = $pdo->query(
            "SELECT id, title FROM campaigns WHERE status IN ('approved','active') ORDER BY created_at DESC LIMIT 120"
        );
        $campaignChoices = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
} elseif ($role === 'ngo') {
    $stmt = $pdo->prepare(
        'SELECT c.id, c.title FROM campaigns c
         INNER JOIN ngo_profiles np ON np.id = c.ngo_id
         WHERE np.user_id = :u
         ORDER BY c.title ASC'
    );
    $stmt->execute(['u' => $userId]);
    $campaignChoices = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $stmt = $pdo->prepare('SELECT id FROM ngo_profiles WHERE user_id = :u LIMIT 1');
    $stmt->execute(['u' => $userId]);
    $ngoId = (int) ($stmt->fetchColumn() ?: 0);

    if ($ngoId > 0) {
        $stmt = $pdo->prepare(
            'SELECT c.id AS campaign_id, u.id AS user_id, u.full_name, u.email
             FROM volunteer_campaigns vc
             INNER JOIN campaigns c ON c.id = vc.campaign_id
             INNER JOIN volunteer_profiles vp ON vp.id = vc.volunteer_id
             INNER JOIN users u ON u.id = vp.user_id
             WHERE vc.ngo_id = :n AND vc.status IN (\'pending\',\'accepted\')
             ORDER BY c.id, u.full_name'
        );
        $stmt->execute(['n' => $ngoId]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cid = (string) (int) $row['campaign_id'];
            if (!isset($ngoPeopleByCampaign[$cid])) {
                $ngoPeopleByCampaign[$cid] = ['volunteer' => [], 'donor' => []];
            }
            $ngoPeopleByCampaign[$cid]['volunteer'][] = [
                'user_id' => (int) $row['user_id'],
                'label' => 'Volunteer: ' . $row['full_name'] . ' (' . $row['email'] . ')',
            ];
        }

        $stmt = $pdo->prepare(
            'SELECT c.id AS campaign_id, u.id AS user_id, u.full_name, u.email
             FROM donations d
             INNER JOIN campaigns c ON c.id = d.campaign_id
             INNER JOIN donor_profiles dp ON dp.id = d.donor_id
             INNER JOIN users u ON u.id = dp.user_id
             WHERE d.ngo_id = :n
             ORDER BY c.id, u.full_name'
        );
        $stmt->execute(['n' => $ngoId]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cid = (string) (int) $row['campaign_id'];
            if (!isset($ngoPeopleByCampaign[$cid])) {
                $ngoPeopleByCampaign[$cid] = ['volunteer' => [], 'donor' => []];
            }
            $ngoPeopleByCampaign[$cid]['donor'][] = [
                'user_id' => (int) $row['user_id'],
                'label' => 'Donor: ' . $row['full_name'] . ' (' . $row['email'] . ')',
            ];
        }

        foreach ($ngoPeopleByCampaign as $cid => $groups) {
            $ngoPeopleByCampaign[$cid]['volunteer'] = array_values(
                array_reduce(
                    $groups['volunteer'],
                    static function (array $acc, array $item): array {
                        $acc[(string) $item['user_id']] = $item;
                        return $acc;
                    },
                    []
                )
            );
            $ngoPeopleByCampaign[$cid]['donor'] = array_values(
                array_reduce(
                    $groups['donor'],
                    static function (array $acc, array $item): array {
                        $acc[(string) $item['user_id']] = $item;
                        return $acc;
                    },
                    []
                )
            );
        }
    }
} else {
    $stmt = $pdo->query(
        "SELECT id, title FROM campaigns WHERE status IN ('pending','approved','active','completed') ORDER BY created_at DESC LIMIT 200"
    );
    $campaignChoices = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

$allowedCampaignIds = [];
foreach ($campaignChoices as $c) {
    $allowedCampaignIds[(int) $c['id']] = true;
}

$prefillCampaignId = (int) ($_GET['campaign_id'] ?? 0);
$prefillDonationId = (int) ($_GET['donation_id'] ?? 0);
if ($prefillDonationId > 0 && $role === 'donor') {
    $stmt = $pdo->prepare(
        'SELECT d.id, d.campaign_id FROM donations d
         INNER JOIN donor_profiles dp ON dp.id = d.donor_id
         WHERE d.id = :did AND dp.user_id = :u LIMIT 1'
    );
    $stmt->execute(['did' => $prefillDonationId, 'u' => $userId]);
    $drow = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($drow) {
        $prefillCampaignId = (int) $drow['campaign_id'];
        $prefillDonationId = (int) $drow['id'];
    } else {
        $prefillDonationId = 0;
    }
}
if ($prefillCampaignId > 0 && !isset($allowedCampaignIds[$prefillCampaignId])) {
    $prefillCampaignId = 0;
    $prefillDonationId = 0;
}

$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        exit('Invalid CSRF token');
    }
    $reportType = trim((string) ($_POST['report_type'] ?? ''));
    $subject = trim((string) ($_POST['subject'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $reportedCampaignId = (int) ($_POST['campaign_id'] ?? 0);
    $reportedDonationId = (int) ($_POST['donation_id'] ?? 0);
    $reportedUserId = (int) ($_POST['reported_user_id'] ?? 0);
    $ngoReportKind = trim((string) ($_POST['ngo_report_kind'] ?? ''));

    if (!in_array($reportType, $types, true)) {
        $errors[] = 'Invalid report type';
    }
    if ($subject === '') {
        $errors[] = 'Subject is required';
    }
    if ($description === '') {
        $errors[] = 'Description is required';
    }

    if ($reportedCampaignId > 0 && !isset($allowedCampaignIds[$reportedCampaignId])) {
        $errors[] = 'Please choose a campaign from the list.';
    }

    if ($role === 'ngo') {
        if ($ngoReportKind !== '' && !in_array($ngoReportKind, ['volunteer', 'donor'], true)) {
            $errors[] = 'Invalid report target type.';
        }
        if ($ngoReportKind !== '' && $reportedCampaignId <= 0) {
            $errors[] = 'Select a campaign before choosing a volunteer or donor.';
        }
        if ($ngoReportKind === '' && $reportedUserId > 0) {
            $reportedUserId = 0;
        }
        if ($ngoReportKind !== '' && $reportedCampaignId > 0 && $reportedUserId > 0) {
            $cid = (string) $reportedCampaignId;
            $allowedUsers = $ngoPeopleByCampaign[$cid][$ngoReportKind] ?? [];
            $ok = false;
            foreach ($allowedUsers as $u) {
                if ((int) $u['user_id'] === $reportedUserId) {
                    $ok = true;
                    break;
                }
            }
            if (!$ok) {
                $errors[] = 'The selected person is not valid for this campaign.';
            }
        }
        if ($ngoReportKind === '') {
            $reportedUserId = 0;
        }
    } elseif ($role === 'donor') {
        if ($reportedDonationId > 0) {
            $stmt = $pdo->prepare(
                'SELECT d.id, d.campaign_id FROM donations d
                 INNER JOIN donor_profiles dp ON dp.id = d.donor_id
                 WHERE d.id = :id AND dp.user_id = :u LIMIT 1'
            );
            $stmt->execute(['id' => $reportedDonationId, 'u' => $userId]);
            $dcheck = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$dcheck) {
                $errors[] = 'Invalid donation selection.';
            } elseif ($reportedCampaignId > 0 && (int) $dcheck['campaign_id'] !== $reportedCampaignId) {
                $errors[] = 'Donation does not match the selected campaign.';
            } elseif ($reportedCampaignId <= 0) {
                $reportedCampaignId = (int) $dcheck['campaign_id'];
            }
        }
        if ($reportedUserId > 0) {
            $reportedUserId = 0;
        }
        if ($reportedCampaignId > 0 && $errors === []) {
            $stmt = $pdo->prepare(
                'SELECT np.user_id FROM campaigns c
                 INNER JOIN ngo_profiles np ON np.id = c.ngo_id
                 WHERE c.id = :id LIMIT 1'
            );
            $stmt->execute(['id' => $reportedCampaignId]);
            $ngoUser = (int) ($stmt->fetchColumn() ?: 0);
            if ($ngoUser > 0) {
                $reportedUserId = $ngoUser;
            }
        }
    } elseif ($role === 'volunteer') {
        if ($reportedUserId > 0) {
            $reportedUserId = 0;
        }
        if ($reportedCampaignId > 0 && $errors === []) {
            $stmt = $pdo->prepare(
                'SELECT np.user_id FROM campaigns c
                 INNER JOIN ngo_profiles np ON np.id = c.ngo_id
                 WHERE c.id = :id LIMIT 1'
            );
            $stmt->execute(['id' => $reportedCampaignId]);
            $ngoUser = (int) ($stmt->fetchColumn() ?: 0);
            if ($ngoUser > 0) {
                $reportedUserId = $ngoUser;
            }
        }
        $reportedDonationId = 0;
    } else {
        if ($reportedUserId > 0) {
            $stmt = $pdo->prepare('SELECT 1 FROM users WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $reportedUserId]);
            if (!$stmt->fetchColumn()) {
                $errors[] = 'Invalid user selection.';
                $reportedUserId = 0;
            }
        }
        if ($reportedDonationId > 0) {
            $stmt = $pdo->prepare('SELECT 1 FROM donations WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $reportedDonationId]);
            if (!$stmt->fetchColumn()) {
                $errors[] = 'Invalid donation selection.';
                $reportedDonationId = 0;
            }
        }
    }

    if ($reportedCampaignId > 0 && $errors === []) {
        $stmt = $pdo->prepare('SELECT 1 FROM campaigns WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $reportedCampaignId]);
        if (!$stmt->fetchColumn()) {
            $errors[] = 'Campaign not found.';
        }
    }

    $attachmentUrl = null;
    $attachmentFileId = null;
    if (!$errors && !empty($_FILES['attachment']['name'])) {
        $up = upload_report_attachment_to_imagekit($_FILES['attachment'], 'reports');
        if (empty($up['success'])) {
            $errors[] = (string) ($up['message'] ?? 'Attachment upload failed');
        } else {
            $attachmentUrl = $up['url'];
            $attachmentFileId = $up['fileId'];
        }
    }
    if (!$errors) {
        $reportId = create_report_record($pdo, [
            'reporter_user_id' => $userId,
            'reported_user_id' => $reportedUserId,
            'reported_campaign_id' => $reportedCampaignId,
            'reported_donation_id' => $reportedDonationId,
            'report_type' => $reportType,
            'subject' => $subject,
            'description' => $description,
            'attachment_url' => $attachmentUrl,
            'attachment_imagekit_file_id' => $attachmentFileId,
        ]);
        notify_admins_for_report($pdo, $reportId, $subject, $reportType, (string) $authUser['full_name']);
        send_report_received_email(['id' => $userId, 'email' => (string) $authUser['email']], $subject);
        log_activity($pdo, $userId, 'report_created', 'reports', $reportId, 'User submitted report');
        $successMessage = 'Your report has been submitted. Admin will review it.';
    }
}

$pageTitle = 'Report an Issue';
require_once dirname(__DIR__) . '/includes/dashboard_layout_start.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';

$ngoJson = json_encode($ngoPeopleByCampaign, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '{}';
$donorDonJson = json_encode($donorDonationsByCampaign, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '{}';

?>
<h1 class="section-title">Report an Issue</h1>
<p class="section-subtitle">You can report fake payment, fake NGO, fake campaign, abuse, fraud, technical issues, or other suspicious activity. Choose a campaign from the list when the report relates to one.</p>
<?php if ($errors): ?><div class="toast error"><?= sanitize(implode(' | ', $errors)) ?></div><?php endif; ?>
<?php if ($successMessage): ?><div class="toast success"><?= sanitize($successMessage) ?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="form-card" data-loading-button>
  <input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()) ?>">
  <div class="form-grid">
    <div class="form-group">
      <label for="report_type">Report type</label>
      <select id="report_type" name="report_type" required>
        <option value="">Select type</option>
        <?php foreach ($types as $t): ?>
          <option value="<?= sanitize($t) ?>"><?= sanitize($typeLabels[$t] ?? $t) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label for="reportCampaignId">Related campaign (optional)</label>
      <select id="reportCampaignId" name="campaign_id">
        <option value="0">Not tied to a specific campaign</option>
        <?php foreach ($campaignChoices as $c): ?>
          <?php
            $cid = (int) $c['id'];
            $sel = ($prefillCampaignId === $cid) ? ' selected' : '';
            ?>
          <option value="<?= $cid ?>"<?= $sel ?>><?= sanitize((string) $c['title']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <?php if ($role === 'ngo'): ?>
    <div class="form-grid">
      <div class="form-group">
        <label for="ngoReportKind">Report about (optional)</label>
        <select id="ngoReportKind" name="ngo_report_kind">
          <option value="">No specific volunteer or donor</option>
          <option value="volunteer">A volunteer on the selected campaign</option>
          <option value="donor">A donor to the selected campaign</option>
        </select>
      </div>
      <div class="form-group">
        <label for="ngoReportedUserId">Person</label>
        <select id="ngoReportedUserId" name="reported_user_id">
          <option value="0">Select a campaign and type first</option>
        </select>
      </div>
    </div>
  <?php endif; ?>
  <?php if ($role === 'donor' && $donorDonationsByCampaign !== []): ?>
    <div class="form-group">
      <label for="donorDonationSelect">Your donation on this campaign (optional)</label>
      <select id="donorDonationSelect" name="donation_id">
        <option value="0">None</option>
      </select>
      <small class="help-text">Shown when a campaign is selected; links this report to one of your donations.</small>
    </div>
  <?php endif; ?>
  <div class="form-grid">
    <div class="form-group">
      <label for="report_subject">Subject</label>
      <input id="report_subject" name="subject" required maxlength="180" placeholder="Short summary">
    </div>
  </div>
  <div class="form-group">
    <label for="report_description">Description</label>
    <textarea id="report_description" name="description" rows="5" required placeholder="What happened? Include dates or proof if you can."></textarea>
  </div>
  <div class="form-group">
    <label for="attachment">Attachment (optional)</label>
    <input id="attachment" type="file" name="attachment" accept="image/*,.pdf">
    <small id="attachmentLabel" class="help-text">JPG/JPEG/PNG/WEBP/PDF up to 5MB</small>
    <img id="attachmentPreview" class="hidden" alt="Attachment preview" style="margin-top:.5rem;max-height:160px;">
  </div>
  <button class="gradient-button" type="submit">Submit Report</button>
</form>
<?php if ($role === 'ngo'): ?>
<script>
(function () {
  var data = <?= $ngoJson ?>;
  var camp = document.getElementById('reportCampaignId');
  var kind = document.getElementById('ngoReportKind');
  var userSel = document.getElementById('ngoReportedUserId');
  if (!camp || !kind || !userSel) return;
  function rebuild() {
    var cid = String(camp.value || '0');
    var k = kind.value;
    userSel.innerHTML = '';
    var o0 = document.createElement('option');
    o0.value = '0';
    o0.textContent = (!cid || cid === '0') ? 'Select a campaign first' : (!k ? 'Choose volunteer or donor above' : 'Select a person');
    userSel.appendChild(o0);
    if (!cid || cid === '0' || !k || !data[cid] || !data[cid][k]) return;
    data[cid][k].forEach(function (row) {
      var o = document.createElement('option');
      o.value = String(row.user_id);
      o.textContent = row.label;
      userSel.appendChild(o);
    });
  }
  camp.addEventListener('change', rebuild);
  kind.addEventListener('change', rebuild);
  rebuild();
})();
</script>
<?php endif; ?>
<?php if ($role === 'donor' && $donorDonationsByCampaign !== []): ?>
<script>
(function () {
  var byCamp = <?= $donorDonJson ?>;
  var camp = document.getElementById('reportCampaignId');
  var don = document.getElementById('donorDonationSelect');
  var preD = <?= (int) $prefillDonationId ?>;
  if (!camp || !don) return;
  function rebuildDon() {
    var cid = String(camp.value || '0');
    don.innerHTML = '';
    var o0 = document.createElement('option');
    o0.value = '0';
    o0.textContent = 'None';
    don.appendChild(o0);
    if (!byCamp[cid]) return;
    byCamp[cid].forEach(function (row) {
      var o = document.createElement('option');
      o.value = String(row.id);
      o.textContent = row.label;
      if (preD && row.id === preD) o.selected = true;
      don.appendChild(o);
    });
  }
  camp.addEventListener('change', rebuildDon);
  rebuildDon();
})();
</script>
<?php endif; ?>
<?php require_once dirname(__DIR__) . '/includes/dashboard_layout_end.php'; ?>
