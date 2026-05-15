<?php
declare(strict_types=1);

/**
 * @param array<string, mixed> $ngo
 */
function render_ngo_public_detail(array $ngo): void
{
    require_once __DIR__ . '/location_helpers.php';

    $ngoId = (int) ($ngo['id'] ?? 0);
    $ngoName = (string) ($ngo['ngo_name'] ?? '');
    $logo = image_or_placeholder((string) ($ngo['logo_url'] ?? $ngo['profile_photo_url'] ?? ''), 'profile');
    ?>
<div class="glass-card" style="padding:1.15rem;margin-bottom:1rem;">
  <div style="display:flex;gap:1rem;align-items:flex-start;flex-wrap:wrap;">
    <img src="<?= htmlspecialchars(sanitize($logo), ENT_QUOTES, 'UTF-8') ?>" alt="" width="72" height="72" style="border-radius:50%;object-fit:cover;">
    <div>
      <h1 class="section-title" style="margin:0 0 0.35rem;"><?= htmlspecialchars($ngoName, ENT_QUOTES, 'UTF-8') ?></h1>
      <?php if (!empty($ngo['verification_status'])): ?>
        <p style="margin:0 0 0.5rem;">Verification: <span class="status-badge"><?= htmlspecialchars((string) $ngo['verification_status'], ENT_QUOTES, 'UTF-8') ?></span></p>
      <?php endif; ?>
      <?php if (!empty($ngo['description'])): ?>
        <div class="dn-campaign-prose dn-campaign-prose--compact"><?= nl2br(htmlspecialchars(trim((string) $ngo['description']), ENT_QUOTES, 'UTF-8')) ?></div>
      <?php endif; ?>
      <?php if (!empty($ngo['address'])): ?>
        <p class="help-text" style="margin:0.75rem 0 0;"><?= htmlspecialchars((string) $ngo['address'], ENT_QUOTES, 'UTF-8') ?></p>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php
    echo render_location_map_display(
        $ngo['latitude'] ?? null,
        $ngo['longitude'] ?? null,
        'Headquarters',
        isset($ngo['address']) ? (string) $ngo['address'] : null,
        'dn-map-ngo-detail-' . $ngoId
    );
}
