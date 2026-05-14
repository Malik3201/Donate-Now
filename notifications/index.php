<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/notification_helper.php';
$pdo=db();
$stmt=$pdo->prepare('SELECT id,title,message,type,is_read,created_at FROM notifications WHERE user_id=:u ORDER BY id DESC');$stmt->execute(['u'=>(int)$authUser['id']]);$notifications=$stmt->fetchAll();
$today=[];$week=[];$earlier=[];$now=new DateTime('now');
foreach($notifications as $n){$dt=new DateTime((string)$n['created_at']);$diff=(int)$now->diff($dt)->format('%a');if($diff===0)$today[]=$n;elseif($diff<=7)$week[]=$n;else$earlier[]=$n;}
$pageTitle='Notifications';
$pageDescription='Stay on top of verification updates, donation movement, and NGO messages.';
require_once dirname(__DIR__) . '/includes/dashboard_layout_start.php';require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>
<div class="dn-page-head">
  <h1 class="dn-page-title">Notification center</h1>
</div>
<form method="post" action="<?= APP_URL ?>/notifications/mark_all_read.php" style="margin-bottom:1rem;">
  <input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()) ?>">
  <button class="gradient-button" type="submit">Mark all read</button>
</form>
<?php foreach (['Today' => $today, 'This Week' => $week, 'Earlier' => $earlier] as $group => $items): ?>
<section class="dn-notify-group">
  <h3><?= htmlspecialchars((string) $group, ENT_QUOTES, 'UTF-8') ?></h3>
  <?php if (!$items): ?><p class="dn-page-lead" style="margin:0;">No notifications in this group.</p><?php endif; ?>
  <?php foreach ($items as $n): ?>
    <article class="dn-notify-item<?= !(int) $n['is_read'] ? ' is-unread' : '' ?>">
      <div style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;flex-wrap:wrap;">
        <div>
          <strong><?= sanitize($n['title']) ?></strong> <span class="badge"><?= sanitize((string) $n['type']) ?></span>
          <p><?= sanitize($n['message']) ?></p>
          <small><?= sanitize((string) $n['created_at']) ?></small>
        </div>
        <div>
          <?php if (!(int) $n['is_read']): ?>
            <form method="post" action="<?= APP_URL ?>/notifications/mark_read.php">
              <input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()) ?>">
              <input type="hidden" name="notification_id" value="<?= (int) $n['id'] ?>">
              <button class="outline-button" type="submit">Mark read</button>
            </form>
          <?php else: ?>
            <span class="dn-badge" data-status="confirmed">Read</span>
          <?php endif; ?>
        </div>
      </div>
    </article>
  <?php endforeach; ?>
</section>
<?php endforeach; ?>
<?php if (!$notifications): ?><div class="empty-state glass-card">You are all caught up — no notifications yet.</div><?php endif; ?>
<?php require_once dirname(__DIR__) . '/includes/dashboard_layout_end.php'; ?>
