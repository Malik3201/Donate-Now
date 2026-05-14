<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/role_check.php';
require_role(['admin']);

$pdo = db();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT r.*, ru.full_name reporter_name, ru.email reporter_email, uu.full_name reported_user_name, c.title campaign_title
     FROM reports r
     INNER JOIN users ru ON ru.id = r.reporter_user_id
     LEFT JOIN users uu ON uu.id = r.reported_user_id
     LEFT JOIN campaigns c ON c.id = r.reported_campaign_id
     WHERE r.id = :id LIMIT 1'
);
$stmt->execute(['id' => $id]);
$r = $stmt->fetch();
if (!$r) {
    exit('Report not found');
}

$stmt = $pdo->prepare(
    'SELECT an.*, u.full_name admin_name FROM admin_notes an
     INNER JOIN users u ON u.id = an.admin_id
     WHERE an.report_id = :id
     ORDER BY an.created_at DESC'
);
$stmt->execute(['id' => $id]);
$notes = $stmt->fetchAll();

$reportTypeLabels = [
    'fake_payment' => 'Fake / suspicious payment',
    'fake_ngo' => 'Fake NGO',
    'fake_campaign' => 'Fake campaign',
    'abuse' => 'Abuse / harassment',
    'fraud' => 'Fraud',
    'technical_issue' => 'Technical issue',
    'other' => 'Other',
];
$typeLabel = $reportTypeLabels[(string) ($r['report_type'] ?? '')] ?? (string) ($r['report_type'] ?? '');
$reportedUserLabel = trim((string) ($r['reported_user_name'] ?? '')) !== '' ? (string) $r['reported_user_name'] : '—';
$campaignLabel = trim((string) ($r['campaign_title'] ?? '')) !== '' ? (string) $r['campaign_title'] : '—';
$donationId = (int) ($r['reported_donation_id'] ?? 0);

$pageTitle = 'Report Detail';
require_once dirname(__DIR__) . '/includes/dashboard_layout_start.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>

<div class="dn-admin-report-detail">
  <h1 class="section-title">Report detail</h1>

  <div class="dn-admin-report-detail__grid">
    <article class="dn-admin-report-detail__main glass-card">
      <header class="dn-admin-report-detail__header">
        <p class="dn-admin-report-detail__eyebrow">Report #<?= (int) $r['id'] ?> · <?= sanitize((string) ($r['created_at'] ?? '')) ?></p>
        <h2 class="dn-admin-report-detail__title"><?= sanitize((string) $r['subject']) ?></h2>
        <div class="dn-admin-report-detail__badges">
          <span class="dn-admin-report-detail__type-pill"><?= sanitize($typeLabel) ?></span>
          <?= dash_status_badge((string) ($r['status'] ?? 'open')) ?>
        </div>
      </header>

      <section class="dn-admin-report-detail__description" aria-label="Report description">
        <?= nl2br(sanitize((string) $r['description'])) ?>
      </section>

      <?php if (!empty($r['attachment_url'])): ?>
        <p class="dn-admin-report-detail__attachment">
          <a class="outline-button" href="<?= sanitize((string) $r['attachment_url']) ?>" target="_blank" rel="noopener">View attachment</a>
        </p>
      <?php endif; ?>

      <dl class="dn-admin-report-detail__dl">
        <div class="dn-admin-report-detail__dl-row">
          <dt>Reporter</dt>
          <dd><?= sanitize((string) $r['reporter_name']) ?> <span class="dn-admin-report-detail__muted">(<?= sanitize((string) $r['reporter_email']) ?>)</span></dd>
        </div>
        <div class="dn-admin-report-detail__dl-row">
          <dt>Reported user</dt>
          <dd><?= sanitize($reportedUserLabel) ?></dd>
        </div>
        <div class="dn-admin-report-detail__dl-row">
          <dt>Campaign</dt>
          <dd><?= sanitize($campaignLabel) ?></dd>
        </div>
        <?php if ($donationId > 0): ?>
          <div class="dn-admin-report-detail__dl-row">
            <dt>Donation</dt>
            <dd><a class="dn-admin-report-detail__inline-link" href="<?= sanitize(APP_URL . '/admin/donation_detail.php?id=' . $donationId) ?>">#<?= $donationId ?> — open donation</a></dd>
          </div>
        <?php endif; ?>
      </dl>
    </article>

    <aside class="dn-admin-report-detail__aside glass-card">
      <h3 class="dn-admin-report-detail__aside-title">Workflow</h3>
      <p class="dn-admin-report-detail__aside-lead">Update status, then use quick links to inspect related records.</p>

      <div class="dn-admin-report-detail__workflow-btns">
        <form method="post" action="<?= sanitize(APP_URL . '/admin/report_action.php') ?>" class="dn-admin-report-detail__inline-form">
          <input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()) ?>">
          <input type="hidden" name="report_id" value="<?= (int) $r['id'] ?>">
          <button class="outline-button dn-admin-report-detail__wf-btn" name="action" value="mark_under_review" type="submit">Mark under review</button>
        </form>
        <button type="button" class="gradient-button dn-admin-report-detail__wf-btn" data-modal-open="resolveModal">Resolve</button>
        <button type="button" class="outline-button dn-admin-report-detail__wf-btn" data-modal-open="rejectModal">Reject</button>
      </div>

      <div class="modal" id="resolveModal">
        <div class="modal-content glass-card">
          <h3>Resolve report</h3>
          <form method="post" action="<?= sanitize(APP_URL . '/admin/report_action.php') ?>">
            <input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()) ?>">
            <input type="hidden" name="report_id" value="<?= (int) $r['id'] ?>">
            <input type="hidden" name="action" value="resolve">
            <div class="form-group">
              <label for="resolve_note">Resolution note</label>
              <textarea id="resolve_note" name="admin_note" required rows="4" placeholder="What did you decide and why?"></textarea>
            </div>
            <div class="dn-admin-report-detail__modal-actions">
              <button class="gradient-button" type="submit">Confirm resolve</button>
              <button type="button" class="outline-button" data-modal-close>Cancel</button>
            </div>
          </form>
        </div>
      </div>

      <div class="modal" id="rejectModal">
        <div class="modal-content glass-card">
          <h3>Reject report</h3>
          <form method="post" action="<?= sanitize(APP_URL . '/admin/report_action.php') ?>">
            <input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()) ?>">
            <input type="hidden" name="report_id" value="<?= (int) $r['id'] ?>">
            <input type="hidden" name="action" value="reject">
            <div class="form-group">
              <label for="reject_note">Rejection reason</label>
              <textarea id="reject_note" name="admin_note" required rows="4" placeholder="Reason shown to audit trail"></textarea>
            </div>
            <div class="dn-admin-report-detail__modal-actions">
              <button class="gradient-button" type="submit">Confirm reject</button>
              <button type="button" class="outline-button" data-modal-close>Cancel</button>
            </div>
          </form>
        </div>
      </div>

      <h4 class="dn-admin-report-detail__links-heading">Quick links</h4>
      <nav class="dn-admin-report-detail__links" aria-label="Related admin pages">
        <a class="outline-button" href="<?= sanitize(APP_URL . '/admin/user_detail.php?id=' . (int) $r['reporter_user_id']) ?>">View reporter</a>
        <?php if ((int) ($r['reported_user_id'] ?? 0) > 0): ?>
          <a class="outline-button" href="<?= sanitize(APP_URL . '/admin/user_detail.php?id=' . (int) $r['reported_user_id']) ?>">View reported user</a>
        <?php endif; ?>
        <?php if ((int) ($r['reported_campaign_id'] ?? 0) > 0): ?>
          <a class="outline-button" href="<?= sanitize(APP_URL . '/admin/campaign_detail.php?id=' . (int) $r['reported_campaign_id']) ?>">View campaign</a>
        <?php endif; ?>
        <?php if ($donationId > 0): ?>
          <a class="outline-button" href="<?= sanitize(APP_URL . '/admin/donation_detail.php?id=' . $donationId) ?>">View donation</a>
        <?php endif; ?>
      </nav>
    </aside>
  </div>

  <section class="dn-admin-report-detail__notes glass-card" aria-labelledby="admin-notes-heading">
    <h3 id="admin-notes-heading" class="dn-admin-report-detail__notes-title">Admin notes</h3>
    <?php if (!$notes): ?>
      <p class="dn-admin-report-detail__muted">No notes yet.</p>
    <?php endif; ?>
    <?php foreach ($notes as $n): ?>
      <div class="dn-admin-report-detail__note">
        <div class="dn-admin-report-detail__note-head">
          <strong><?= sanitize((string) $n['admin_name']) ?></strong>
          <time datetime="<?= htmlspecialchars((string) $n['created_at'], ENT_QUOTES, 'UTF-8') ?>"><?= sanitize((string) $n['created_at']) ?></time>
        </div>
        <div class="dn-admin-report-detail__note-body"><?= nl2br(sanitize((string) $n['note'])) ?></div>
      </div>
    <?php endforeach; ?>
  </section>
</div>

<?php require_once dirname(__DIR__) . '/includes/dashboard_layout_end.php'; ?>
