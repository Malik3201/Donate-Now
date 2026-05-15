<?php
declare(strict_types=1);

/**
 * Dashboard breadcrumb trail. Optional: set $breadcrumbCurrent before including
 * to override the last segment label (e.g. "My Profile" on view_profile.php).
 */

$path = trim((string) (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? ''), '/');
$segments = $path !== '' ? array_values(array_filter(explode('/', $path))) : [];

$segmentLabels = [
    'view_profile' => 'My Profile',
    'update_profile' => 'Edit Profile',
    'change_password' => 'Change Password',
    'upload_profile_photo' => 'Upload Photo',
    'profile' => 'Profile',
    'donor' => 'Donor',
    'ngo' => 'NGO',
    'volunteer' => 'Volunteer',
    'admin' => 'Admin',
];

$normalizeKey = static function (string $segment): string {
    return strtolower(str_replace(['-', '.php'], ['_', ''], $segment));
};

$crumbs = [];
foreach ($segments as $segment) {
    $key = $normalizeKey($segment);
    if ($key === 'donate_now' || $key === 'donatenow') {
        continue;
    }
    $crumbs[] = [
        'key' => $key,
        'label' => $segmentLabels[$key] ?? ucwords(str_replace('_', ' ', $key)),
    ];
}

if (count($crumbs) >= 2 && $crumbs[count($crumbs) - 1]['key'] === 'view_profile' && $crumbs[count($crumbs) - 2]['key'] === 'profile') {
    array_pop($crumbs);
}

$breadcrumbCurrent = $breadcrumbCurrent ?? null;
if (is_string($breadcrumbCurrent) && $breadcrumbCurrent !== '') {
    if ($crumbs !== []) {
        $crumbs[count($crumbs) - 1]['label'] = $breadcrumbCurrent;
    } else {
        $crumbs[] = ['key' => 'current', 'label' => $breadcrumbCurrent];
    }
}
?>
<nav class="dn-breadcrumbs" aria-label="Breadcrumb">
  <a href="<?= APP_URL ?>/index.php">Home</a>
  <?php foreach ($crumbs as $index => $crumb): ?>
    <span class="sep" aria-hidden="true">/</span>
    <?php if ($index === count($crumbs) - 1): ?>
      <span aria-current="page"><?= htmlspecialchars($crumb['label'], ENT_QUOTES, 'UTF-8') ?></span>
    <?php else: ?>
      <span><?= htmlspecialchars($crumb['label'], ENT_QUOTES, 'UTF-8') ?></span>
    <?php endif; ?>
  <?php endforeach; ?>
</nav>
