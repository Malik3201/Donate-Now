<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/ui_helpers.php';

$sessionRole = (string) ($_SESSION['role'] ?? 'guest');
$currentPath = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '');

$menus = [
    'admin' => [
        ['label' => 'Dashboard', 'path' => '/admin/dashboard.php', 'icon' => 'home'],
        ['label' => 'Manage Users', 'path' => '/admin/manage_users.php', 'icon' => 'users'],
        ['label' => 'Verify NGOs', 'path' => '/admin/verify_ngos.php', 'icon' => 'shield'],
        ['label' => 'Manage Campaigns', 'path' => '/admin/manage_campaigns.php', 'icon' => 'megaphone'],
        ['label' => 'All Donations', 'path' => '/admin/all_donations.php', 'icon' => 'currency'],
        ['label' => 'Reports', 'path' => '/admin/reports.php', 'icon' => 'flag'],
        ['label' => 'Analytics', 'path' => '/admin/analytics.php', 'icon' => 'chart'],
        ['label' => 'Email test', 'path' => '/admin/email_test.php', 'icon' => 'mail'],
        ['label' => 'Admin Notes', 'path' => '/admin/admin_notes.php', 'icon' => 'file-text'],
        ['label' => 'Notifications', 'path' => '/notifications/index.php', 'icon' => 'bell'],
        ['label' => 'Profile', 'path' => '/profile/view_profile.php', 'icon' => 'user'],
        ['label' => 'Logout', 'path' => '/auth/logout.php', 'icon' => 'logout', 'logout' => true],
    ],
    'ngo' => [
        ['label' => 'Dashboard', 'path' => '/ngo/dashboard.php', 'icon' => 'home'],
        ['label' => 'My Campaigns', 'path' => '/ngo/my_campaigns.php', 'icon' => 'folder'],
        ['label' => 'Create Campaign', 'path' => '/ngo/create_campaign.php', 'icon' => 'plus'],
        ['label' => 'Payment Methods', 'path' => '/ngo/payment_methods.php', 'icon' => 'wallet'],
        ['label' => 'Donation Dashboard', 'path' => '/ngo/donation_dashboard.php', 'icon' => 'clipboard'],
        ['label' => 'Volunteer Requests', 'path' => '/ngo/volunteer_requests.php', 'icon' => 'handshake'],
        ['label' => 'Reports', 'path' => '/ngo/reports.php', 'icon' => 'flag'],
        ['label' => 'Donation Reports', 'path' => '/ngo/donation_reports.php', 'icon' => 'chart'],
        ['label' => 'Notifications', 'path' => '/notifications/index.php', 'icon' => 'bell'],
        ['label' => 'Profile', 'path' => '/profile/view_profile.php', 'icon' => 'user'],
        ['label' => 'Logout', 'path' => '/auth/logout.php', 'icon' => 'logout', 'logout' => true],
    ],
    'donor' => [
        ['label' => 'Dashboard', 'path' => '/donor/dashboard.php', 'icon' => 'home'],
        ['label' => 'Browse Campaigns', 'path' => '/donor/browse_campaigns.php', 'icon' => 'compass'],
        ['label' => 'My Donations', 'path' => '/donor/my_donations.php', 'icon' => 'heart'],
        ['label' => 'Reports', 'path' => '/donor/reports.php', 'icon' => 'flag'],
        ['label' => 'Donation Reports', 'path' => '/donor/donation_reports.php', 'icon' => 'chart'],
        ['label' => 'Notifications', 'path' => '/notifications/index.php', 'icon' => 'bell'],
        ['label' => 'Profile', 'path' => '/profile/view_profile.php', 'icon' => 'user'],
        ['label' => 'Logout', 'path' => '/auth/logout.php', 'icon' => 'logout', 'logout' => true],
    ],
    'volunteer' => [
        ['label' => 'Dashboard', 'path' => '/volunteer/dashboard.php', 'icon' => 'home'],
        ['label' => 'Browse Campaigns', 'path' => '/volunteer/browse_campaigns.php', 'icon' => 'compass'],
        ['label' => 'My Campaigns', 'path' => '/volunteer/my_campaigns.php', 'icon' => 'folder'],
        ['label' => 'Reports', 'path' => '/volunteer/reports.php', 'icon' => 'flag'],
        ['label' => 'Notifications', 'path' => '/notifications/index.php', 'icon' => 'bell'],
        ['label' => 'Profile', 'path' => '/profile/view_profile.php', 'icon' => 'user'],
        ['label' => 'Logout', 'path' => '/auth/logout.php', 'icon' => 'logout', 'logout' => true],
    ],
];

$items = $menus[$sessionRole] ?? [];
$roleLabel = $sessionRole === 'ngo' ? 'NGO' : ucfirst($sessionRole);
?>
<aside id="dashboardSidebar" class="dashboard-sidebar" aria-label="Primary navigation">
  <button type="button" class="dashboard-sidebar-close" id="sidebarClose" aria-label="Close menu"><?= dn_nav_icon('close') ?></button>
  <a class="dashboard-sidebar-brand" href="<?= app_url('index.php') ?>">
    <?= app_logo_img('app-logo dashboard-sidebar-brand-mark', 40, 40) ?>
    <span>Donate Now</span>
  </a>
  <span class="dashboard-sidebar-role"><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?></span>
  <div class="dashboard-sidebar-nav-title">Menu</div>
  <nav class="dashboard-sidebar-nav">
    <?php foreach ($items as $item):
        $path = (string) $item['path'];
        $needle = $path;
        $active = $currentPath !== '' && str_contains($currentPath, $needle);
        $isLogout = !empty($item['logout']);
        $classes = 'dashboard-nav-link' . ($active ? ' is-active' : '') . ($isLogout ? ' dashboard-nav-link--logout' : '');
        $href = app_url(ltrim($path, '/'));
        ?>
      <a class="<?= $classes ?>" href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>">
        <?= dn_nav_icon((string) $item['icon']) ?>
        <span><?= htmlspecialchars((string) $item['label'], ENT_QUOTES, 'UTF-8') ?></span>
      </a>
    <?php endforeach; ?>
  </nav>
</aside>
