<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/ui_helpers.php';
require_once dirname(__DIR__) . '/includes/notification_helper.php';

$unreadCount = isset($authUser, $pdo) ? get_unread_notification_count($pdo, (int) $authUser['id']) : 0;
$topbarAvatarUrl = image_or_placeholder((string) ($authUser['profile_photo_url'] ?? ''), 'profile');
$displayName = (string) ($_SESSION['full_name'] ?? 'User');
$roleKey = strtolower((string) ($_SESSION['role'] ?? ''));
$roleChip = $roleKey === 'ngo' ? 'NGO' : ucfirst($roleKey);
?>
<div class="dashboard-topbar-wrap">
  <header class="dashboard-topbar" role="banner">
    <div class="dashboard-topbar-left">
      <button type="button" class="dashboard-menu-btn" id="sidebarToggle" aria-label="Open navigation" aria-expanded="false" aria-controls="dashboardSidebar">
        <?= dn_nav_icon('menu') ?>
      </button>
      <div class="dashboard-search dashboard-search-wrap">
        <?= dn_nav_icon('search') ?>
        <label for="dashboardSearchDummy" class="sr-only">Search dashboard</label>
        <input id="dashboardSearchDummy" type="search" name="" autocomplete="off" placeholder="Search dashboard…">
      </div>
    </div>
    <div class="dashboard-topbar-right">
      <span class="dashboard-chip-role"><?= htmlspecialchars($roleChip, ENT_QUOTES, 'UTF-8') ?></span>
      <a class="dashboard-bell" href="<?= app_url('notifications/index.php') ?>" aria-label="Notifications">
        <?= dn_nav_icon('bell') ?>
        <?php if ($unreadCount > 0): ?>
          <span class="dashboard-bell-count"><?= (int) min(99, $unreadCount) ?></span>
        <?php endif; ?>
      </a>
      <div class="action-dropdown dashboard-profile-dd">
        <button type="button" class="dashboard-profile-btn" data-dropdown-toggle="topbarProfileMenu" aria-haspopup="true" aria-expanded="false">
          <img src="<?= htmlspecialchars($topbarAvatarUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" width="32" height="32">
          <span class="truncate"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></span>
        </button>
        <div id="topbarProfileMenu" class="dashboard-profile-menu" role="menu" style="display:none;">
          <a role="menuitem" href="<?= app_url('profile/view_profile.php') ?>">View Profile</a>
          <a role="menuitem" href="<?= app_url('profile/change_password.php') ?>">Change Password</a>
          <a role="menuitem" href="<?= app_url('auth/logout.php') ?>">Logout</a>
        </div>
      </div>
    </div>
  </header>
</div>
<div class="dashboard-body">
    <?php if (!empty($pageDescription)): ?>
      <p class="dn-page-lead dn-page-lead--global"><?= htmlspecialchars((string) $pageDescription, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
