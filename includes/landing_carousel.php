<?php
declare(strict_types=1);

/**
 * Home page card carousels (index.php only).
 * Mobile (≤992px): auto-scroll; desktop: CSS grid (see landing.css).
 * Duplicate items in PHP (array_merge) for infinite loop; JS marks half as aria-hidden.
 */

/** Opens carousel markup; $modifier becomes dn-auto-carousel--{modifier} */
function landing_carousel_open(string $ariaLabel, string $modifier = '', int $speedPxPerSec = 42): void
{
    $modifierClass = $modifier !== '' ? ' dn-auto-carousel--' . preg_replace('/[^a-z0-9_-]/i', '', $modifier) : '';
    $label = htmlspecialchars($ariaLabel, ENT_QUOTES, 'UTF-8');
    $speed = max(20, min(120, $speedPxPerSec));
    echo '<div class="dn-auto-carousel' . $modifierClass . '" aria-roledescription="carousel" aria-label="' . $label . '" data-speed="' . $speed . '">';
    echo '<div class="dn-auto-carousel__viewport"><div class="dn-auto-carousel__track">';
}

function landing_carousel_close(): void
{
    echo '</div></div></div>';
}

/**
 * @param array<string, mixed> $campaign
 */
function landing_render_featured_campaign_card(array $campaign): void
{
    $target = max(0.0, (float)($campaign['target_amount'] ?? 0));
    $collected = max(0.0, (float)($campaign['collected_amount'] ?? 0));
    $progress = $target > 0 ? min(100.0, ($collected / $target) * 100) : 0.0;
    $title = sanitize((string)($campaign['title'] ?? 'Campaign'));
    $ngoName = sanitize((string)($campaign['ngo_name'] ?? 'Verified NGO'));
    $category = sanitize((string)($campaign['category_name'] ?? 'General'));
    $description = sanitize((string)($campaign['description'] ?? 'Support this campaign to help local communities through transparent giving.'));
    $imageUrl = sanitize(image_or_placeholder((string)($campaign['image_url'] ?? ''), 'campaign'));
    $statusLabel = sanitize((string)($campaign['status'] ?? 'active'));
    ?>
    <article class="campaign-card">
      <div class="campaign-media">
        <img src="<?= $imageUrl ?>" alt="<?= $title ?> campaign image" loading="lazy">
        <span class="campaign-badge"><?= $category ?></span>
      </div>
      <div class="campaign-body">
        <div class="campaign-head">
          <h3><?= $title ?></h3>
          <span class="campaign-status"><?= $statusLabel ?></span>
        </div>
        <p class="campaign-ngo">By <?= $ngoName ?></p>
        <p class="campaign-desc"><?= $description ?></p>
        <div class="campaign-amounts">
          <p>Target <strong>PKR <?= number_format($target, 2) ?></strong></p>
          <p>Collected <strong>PKR <?= number_format($collected, 2) ?></strong></p>
        </div>
        <div class="progress-wrap" aria-label="Campaign progress">
          <div class="progress-bar" style="width: <?= number_format($progress, 2) ?>%"></div>
        </div>
        <div class="campaign-foot">
          <span><?= number_format($progress, 2) ?>% funded</span>
          <a class="btn btn-primary btn-sm" href="<?= APP_URL ?>/public/campaign_detail.php?id=<?= (int)$campaign['id'] ?>">View & Donate</a>
        </div>
      </div>
    </article>
    <?php
}
