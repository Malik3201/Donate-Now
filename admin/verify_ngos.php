<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/role_check.php';
require_once dirname(__DIR__) . '/includes/mail_helper.php';
require_once dirname(__DIR__) . '/includes/notification_helper.php';
require_role(['admin']);

$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) exit('Invalid CSRF token');
    $ngoUserId = intval($_POST['user_id'] ?? 0);
    $action = trim((string)($_POST['action'] ?? ''));
    $reason = trim((string)($_POST['reason'] ?? ''));
    $allowed = ['verify', 'reject', 'temporary_hold'];
    if (!in_array($action, $allowed, true)) exit('Invalid action');
    if (in_array($action, ['reject', 'temporary_hold'], true) && $reason === '') exit('Reason required');

    $status = $action === 'verify' ? 'verified' : ($action === 'reject' ? 'rejected' : 'temporary_hold');
    $stmt = $pdo->prepare('UPDATE ngo_profiles SET verification_status = :status, verification_notes = :reason, updated_at = NOW() WHERE user_id = :user_id');
    $stmt->execute(['status' => $status, 'reason' => $reason ?: null, 'user_id' => $ngoUserId]);

    $stmt = $pdo->prepare('SELECT id, email, full_name FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $ngoUserId]);
    $ngoUser = $stmt->fetch();
    if ($ngoUser) {
        create_notification($pdo, (int)$ngoUser['id'], 'NGO Verification Update', 'Verification status updated to ' . $status, 'ngo_verification');
        if ($status === 'verified') {
            send_ngo_verified_email($ngoUser);
        } else {
            send_ngo_rejected_email($ngoUser, $reason ?: 'Status updated by admin');
        }
    }

    log_activity($pdo, (int)$authUser['id'], 'ngo_verification_update', 'ngo_profiles', $ngoUserId, 'Updated NGO verification to ' . $status);
}

$filterStatus = trim((string)($_GET['status'] ?? ''));
$sql = 'SELECT u.id, u.full_name, u.email, n.ngo_name, n.verification_status, n.verification_notes FROM users u INNER JOIN ngo_profiles n ON n.user_id = u.id';
$params = [];
if ($filterStatus !== '' && in_array($filterStatus, ['pending', 'verified', 'rejected', 'temporary_hold'], true)) {
    $sql .= ' WHERE n.verification_status = :status';
    $params['status'] = $filterStatus;
}
$sql .= ' ORDER BY u.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ngos = $stmt->fetchAll();

$pageTitle = 'Verify NGOs';
$pageDescription = 'Review NGO registrations, approve verified partners, or place accounts on hold with a clear audit trail.';
require_once dirname(__DIR__) . '/includes/dashboard_layout_start.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>
<div class="dn-page-head">
  <h1 class="dn-page-title">Verify NGOs</h1>
  <p class="dn-page-lead">Filter by verification status, then use the action form for each row. Reject and temporary hold require a reason.</p>
</div>
<div class="data-panel" style="margin-bottom:1rem;padding:1rem 1.15rem;">
  <form method="get" class="form-grid" style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:flex-end;">
    <div>
      <label for="statusFilter">Status</label><br>
      <select id="statusFilter" name="status">
        <option value="">All</option>
        <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
        <option value="verified" <?= $filterStatus === 'verified' ? 'selected' : '' ?>>Verified</option>
        <option value="rejected" <?= $filterStatus === 'rejected' ? 'selected' : '' ?>>Rejected</option>
        <option value="temporary_hold" <?= $filterStatus === 'temporary_hold' ? 'selected' : '' ?>>Temporary hold</option>
      </select>
    </div>
    <button type="submit" class="gradient-button">Apply filter</button>
  </form>
</div>
<div class="data-panel table-wrapper">
  <h3>NGO queue</h3>
  <table>
    <thead>
      <tr>
        <th>User</th>
        <th>Email</th>
        <th>NGO</th>
        <th>Status</th>
        <th>Notes</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($ngos as $ngo): ?>
      <tr>
        <td><?= htmlspecialchars((string) $ngo['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) $ngo['email'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) $ngo['ngo_name'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= dash_status_badge((string) $ngo['verification_status']) ?></td>
        <td><?= htmlspecialchars((string) ($ngo['verification_notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
        <td>
          <form method="post" style="display:flex;flex-direction:column;gap:0.4rem;min-width:200px;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="user_id" value="<?= (int) $ngo['id'] ?>">
            <select name="action" required>
              <option value="verify">Verify</option>
              <option value="reject">Reject</option>
              <option value="temporary_hold">Temporary hold</option>
            </select>
            <input name="reason" placeholder="Reason (required for reject/hold)">
            <button type="submit" class="outline-button">Update</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$ngos): ?>
      <tr><td colspan="6" class="empty-state">No NGOs match this filter.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php require_once dirname(__DIR__) . '/includes/dashboard_layout_end.php'; ?>
