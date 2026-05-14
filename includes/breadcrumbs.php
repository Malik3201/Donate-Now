<?php
declare(strict_types=1);

$path = trim((string) (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? ''), '/');
$segments = $path !== '' ? array_values(array_filter(explode('/', $path))) : [];
?>
<nav class="dn-breadcrumbs" aria-label="Breadcrumb">
  <a href="<?= APP_URL ?>/index.php">Home</a>
  <?php foreach ($segments as $seg): ?>
    <span class="sep">/</span>
    <span><?= htmlspecialchars(ucwords(str_replace(['-', '_', '.php'], [' ', ' ', ''], $seg)), ENT_QUOTES, 'UTF-8') ?></span>
  <?php endforeach; ?>
</nav>
