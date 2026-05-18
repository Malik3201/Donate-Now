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
 * Compact featured campaign card for the landing page (title, NGO, progress only).
 *
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
    $imageUrl = sanitize(image_or_placeholder((string)($campaign['image_url'] ?? ''), 'campaign'));
    $detailUrl = app_url('public/campaign_detail.php?id=' . (int)($campaign['id'] ?? 0));
    ?>
    <article class="campaign-card campaign-card--landing">
      <a class="campaign-card__link" href="<?= htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') ?>">
        <div class="campaign-media">
          <img src="<?= $imageUrl ?>" alt="<?= $title ?> campaign image" loading="lazy">
          <span class="campaign-badge"><?= $category ?></span>
        </div>
        <div class="campaign-body">
          <h3 class="campaign-card__title"><?= $title ?></h3>
          <p class="campaign-ngo"><?= $ngoName ?></p>
          <div class="campaign-card__progress" aria-label="Campaign progress <?= number_format($progress, 0) ?> percent">
            <div class="progress-wrap">
              <div class="progress-bar" style="width: <?= number_format($progress, 2) ?>%"></div>
            </div>
            <span class="campaign-card__pct"><?= number_format($progress, 0) ?>% funded</span>
          </div>
        </div>
      </a>
    </article>
    <?php
}
