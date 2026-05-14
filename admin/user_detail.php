<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/role_check.php';
require_role(['admin']);
require_once dirname(__DIR__) . '/includes/ui_helpers.php';

$pdo = db();
$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$user = $stmt->fetch();
if (!$user) {
    exit('User not found');
}

$photo = image_or_placeholder((string) ($user['profile_photo_url'] ?? ''), 'profile');
$role = (string) ($user['role'] ?? '');
$roleData = [];

if ($role === 'donor') {
    $stmt = $pdo->prepare('SELECT * FROM donor_profiles WHERE user_id = :u LIMIT 1');
    $stmt->execute(['u' => $id]);
    $roleData = $stmt->fetch() ?: [];
} elseif ($role === 'ngo') {
    $stmt = $pdo->prepare('SELECT * FROM ngo_profiles WHERE user_id = :u LIMIT 1');
    $stmt->execute(['u' => $id]);
    $roleData = $stmt->fetch() ?: [];
} elseif ($role === 'volunteer') {
    $stmt = $pdo->prepare('SELECT * FROM volunteer_profiles WHERE user_id = :u LIMIT 1');
    $stmt->execute(['u' => $id]);
    $roleData = $stmt->fetch() ?: [];
}

$stmt = $pdo->prepare('SELECT * FROM account_status_history WHERE user_id = :u ORDER BY created_at DESC');
$stmt->execute(['u' => $id]);
$statusHistory = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT id, subject, status, created_at FROM reports WHERE reporter_user_id = :u ORDER BY created_at DESC LIMIT 10');
$stmt->execute(['u' => $id]);
$reportsSubmitted = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT id, subject, status, created_at FROM reports WHERE reported_user_id = :u ORDER BY created_at DESC LIMIT 10');
$stmt->execute(['u' => $id]);
$reportsAgainst = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT * FROM admin_notes WHERE target_user_id = :u ORDER BY created_at DESC LIMIT 20');
$stmt->execute(['u' => $id]);
$notes = $stmt->fetchAll();

$pageTitle = 'User Detail';
require_once dirname(__DIR__) . '/includes/dashboard_layout_start.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';

$roleProfileId = (int) ($roleData['id'] ?? 0);
?>

<div class="dn-admin-user-detail">
  <h1 class="section-title">User detail</h1>

  <header class="dn-admin-user-detail__hero glass-card">
    <img class="dn-admin-user-detail__avatar" src="<?= sanitize($photo) ?>" alt="" width="88" height="88">
    <div class="dn-admin-user-detail__hero-text">
      <h2 class="dn-admin-user-detail__name"><?= sanitize((string) $user['full_name']) ?> <span class="badge"><?= sanitize($role) ?></span></h2>
      <p class="dn-admin-user-detail__contact"><?= sanitize((string) $user['email']) ?> <?php if (trim((string) ($user['phone'] ?? '')) !== ''): ?>· <?= sanitize((string) $user['phone']) ?><?php endif; ?></p>
      <p class="dn-admin-user-detail__status-row">Account: <?= dash_status_badge((string) ($user['account_status'] ?? 'active')) ?></p>
    </div>
    <div class="dn-admin-user-detail__hero-actions">
      <button class="gradient-button" type="button" data-modal-open="statusAction">Account action</button>
    </div>
    <div class="modal" id="statusAction">
      <div class="modal-content glass-card">
        <h3>Update account status</h3>
        <form method="post" action="<?= sanitize(APP_URL . '/admin/user_action.php') ?>">
          <input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()) ?>">
          <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
          <div class="form-group">
            <label for="acct_action">Action</label>
            <select id="acct_action" name="action">
              <option value="activate">activate</option>
              <option value="block">block</option>
              <option value="suspend">suspend</option>
              <option value="temporary_hold">temporary_hold</option>
            </select>
          </div>
          <div class="form-group">
            <label for="acct_reason">Reason</label>
            <textarea id="acct_reason" name="reason" rows="3" placeholder="Reason (optional)"></textarea>
          </div>
          <div class="dn-admin-report-detail__modal-actions">
            <button class="gradient-button" type="submit">Apply</button>
            <button type="button" class="outline-button" data-modal-close>Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </header>

  <div class="dn-admin-user-detail__grid">
    <section class="dn-admin-user-detail__card glass-card" aria-labelledby="role-profile-heading">
      <h3 id="role-profile-heading" class="dn-admin-user-detail__card-title">Role profile</h3>

      <?php if ($role === 'admin'): ?>
        <p class="dn-admin-user-detail__empty">Administrators do not have donor, NGO, or volunteer profile rows.</p>
      <?php elseif ($roleProfileId <= 0): ?>
        <p class="dn-admin-user-detail__empty">No profile row found for this user.</p>
      <?php elseif ($role === 'ngo' && $roleData !== []): ?>
        <div class="dn-admin-user-detail__ngo-brand">
          <?php
            $logoUrl = image_or_placeholder((string) ($roleData['logo_url'] ?? ''), 'profile');
            ?>
          <img class="dn-admin-user-detail__ngo-logo" src="<?= sanitize($logoUrl) ?>" alt="NGO logo" width="72" height="72">
          <div>
            <p class="dn-admin-user-detail__ngo-name"><?= sanitize((string) ($roleData['ngo_name'] ?? '')) ?></p>
            <p class="dn-admin-user-detail__muted">Verification: <?= dash_status_badge((string) ($roleData['verification_status'] ?? 'pending')) ?></p>
          </div>
        </div>
        <dl class="dn-admin-user-detail__dl">
          <div class="dn-admin-user-detail__dl-row"><dt>Registration #</dt><dd><?= sanitize((string) ($roleData['registration_number'] ?? '—')) ?></dd></div>
          <div class="dn-admin-user-detail__dl-row"><dt>Description</dt><dd><?= nl2br(sanitize((string) ($roleData['description'] ?? '—'))) ?></dd></div>
          <div class="dn-admin-user-detail__dl-row"><dt>Address</dt><dd><?= nl2br(sanitize((string) ($roleData['address'] ?? '—'))) ?></dd></div>
          <div class="dn-admin-user-detail__dl-row"><dt>Campaigns</dt><dd><?= (int) ($roleData['total_campaigns'] ?? 0) ?></dd></div>
          <div class="dn-admin-user-detail__dl-row"><dt>Confirmed donations</dt><dd><?= (int) ($roleData['total_confirmed_donations_count'] ?? 0) ?></dd></div>
          <div class="dn-admin-user-detail__dl-row"><dt>Total received</dt><dd>PKR <?= number_format((float) ($roleData['total_received_amount'] ?? 0), 2) ?></dd></div>
          <div class="dn-admin-user-detail__dl-row"><dt>Profile created</dt><dd><?= sanitize((string) ($roleData['created_at'] ?? '')) ?></dd></div>
        </dl>
        <p class="dn-admin-user-detail__card-actions"><a class="outline-button" href="<?= sanitize(APP_URL . '/admin/ngo_detail.php?id=' . $roleProfileId) ?>">Open NGO detail</a></p>
      <?php elseif ($role === 'donor' && $roleData !== []): ?>
        <dl class="dn-admin-user-detail__dl">
          <div class="dn-admin-user-detail__dl-row"><dt>Donor type</dt><dd><?= sanitize((string) ($roleData['donor_type'] ?? '—')) ?></dd></div>
          <div class="dn-admin-user-detail__dl-row"><dt>Address</dt><dd><?= nl2br(sanitize((string) ($roleData['address'] ?? '—'))) ?></dd></div>
          <div class="dn-admin-user-detail__dl-row"><dt>Total donations</dt><dd><?= (int) ($roleData['total_donations_count'] ?? 0) ?></dd></div>
          <div class="dn-admin-user-detail__dl-row"><dt>Total donated</dt><dd>PKR <?= number_format((float) ($roleData['total_donated_amount'] ?? 0), 2) ?></dd></div>
          <div class="dn-admin-user-detail__dl-row"><dt>Profile created</dt><dd><?= sanitize((string) ($roleData['created_at'] ?? '')) ?></dd></div>
        </dl>
        <p class="dn-admin-user-detail__card-actions"><a class="outline-button" href="<?= sanitize(APP_URL . '/admin/donor_detail.php?id=' . $roleProfileId) ?>">Open donor detail</a></p>
      <?php elseif ($role === 'volunteer' && $roleData !== []): ?>
        <dl class="dn-admin-user-detail__dl">
          <div class="dn-admin-user-detail__dl-row"><dt>Skills</dt><dd><?= nl2br(sanitize((string) ($roleData['skills'] ?? '—'))) ?></dd></div>
          <div class="dn-admin-user-detail__dl-row"><dt>Availability</dt><dd><?= sanitize((string) ($roleData['availability'] ?? '—')) ?></dd></div>
          <div class="dn-admin-user-detail__dl-row"><dt>Address</dt><dd><?= nl2br(sanitize((string) ($roleData['address'] ?? '—'))) ?></dd></div>
          <div class="dn-admin-user-detail__dl-row"><dt>Profile created</dt><dd><?= sanitize((string) ($roleData['created_at'] ?? '')) ?></dd></div>
        </dl>
        <p class="dn-admin-user-detail__card-actions"><a class="outline-button" href="<?= sanitize(APP_URL . '/admin/volunteer_detail.php?id=' . $roleProfileId) ?>">Open volunteer detail</a></p>
      <?php endif; ?>
    </section>

    <section class="dn-admin-user-detail__card glass-card" aria-labelledby="status-history-heading">
      <h3 id="status-history-heading" class="dn-admin-user-detail__card-title">Account status history</h3>
      <?php if (!$statusHistory): ?>
        <p class="dn-admin-user-detail__empty">No status history yet.</p>
      <?php endif; ?>
      <?php foreach ($statusHistory as $h): ?>
        <div class="dn-admin-user-detail__history-row">
          <div class="dn-admin-user-detail__history-meta">
            <?= dash_status_badge((string) ($h['new_status'] ?? '')) ?>
            <time><?= sanitize((string) ($h['created_at'] ?? '')) ?></time>
          </div>
          <?php if (trim((string) ($h['reason'] ?? '')) !== ''): ?>
            <p class="dn-admin-user-detail__history-reason"><?= nl2br(sanitize((string) $h['reason'])) ?></p>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </section>
  </div>

  <div class="dn-admin-user-detail__grid dn-admin-user-detail__grid--reports">
    <section class="dn-admin-user-detail__card glass-card" aria-labelledby="reports-out-heading">
      <h3 id="reports-out-heading" class="dn-admin-user-detail__card-title">Reports submitted</h3>
      <?php if (!$reportsSubmitted): ?>
        <p class="dn-admin-user-detail__empty">None yet.</p>
      <?php endif; ?>
      <ul class="dn-admin-user-detail__report-list">
        <?php foreach ($reportsSubmitted as $r): ?>
          <li>
            <a href="<?= sanitize(APP_URL . '/admin/report_detail.php?id=' . (int) $r['id']) ?>">#<?= (int) $r['id'] ?> <?= sanitize((string) $r['subject']) ?></a>
            <?= dash_status_badge((string) ($r['status'] ?? '')) ?>
            <span class="dn-admin-user-detail__muted"><?= sanitize((string) ($r['created_at'] ?? '')) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>

    <section class="dn-admin-user-detail__card glass-card" aria-labelledby="reports-in-heading">
      <h3 id="reports-in-heading" class="dn-admin-user-detail__card-title">Reports against this user</h3>
      <?php if (!$reportsAgainst): ?>
        <p class="dn-admin-user-detail__empty">None yet.</p>
      <?php endif; ?>
      <ul class="dn-admin-user-detail__report-list">
        <?php foreach ($reportsAgainst as $r): ?>
          <li>
            <a href="<?= sanitize(APP_URL . '/admin/report_detail.php?id=' . (int) $r['id']) ?>">#<?= (int) $r['id'] ?> <?= sanitize((string) $r['subject']) ?></a>
            <?= dash_status_badge((string) ($r['status'] ?? '')) ?>
            <span class="dn-admin-user-detail__muted"><?= sanitize((string) ($r['created_at'] ?? '')) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
  </div>

  <section class="dn-admin-user-detail__card glass-card" aria-labelledby="admin-notes-heading">
    <h3 id="admin-notes-heading" class="dn-admin-user-detail__card-title">Admin notes</h3>
    <?php if (!$notes): ?>
      <p class="dn-admin-user-detail__empty">No notes.</p>
    <?php endif; ?>
    <?php foreach ($notes as $n): ?>
      <div class="dn-admin-user-detail__note">
        <span class="dn-admin-user-detail__muted"><?= sanitize((string) ($n['created_at'] ?? '')) ?></span>
        <p><?= nl2br(sanitize((string) ($n['note'] ?? ''))) ?></p>
      </div>
    <?php endforeach; ?>
  </section>
</div>

<?php require_once dirname(__DIR__) . '/includes/dashboard_layout_end.php'; ?>
