<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/role_check.php';
require_role(['donor']);
$pdo = db();

$category = intval($_GET['category'] ?? 0);
$ngo = intval($_GET['ngo'] ?? 0);
$keyword = trim((string) ($_GET['keyword'] ?? ''));

$sql = "SELECT c.id, c.title, c.description, c.target_amount, c.collected_amount, c.status, c.created_at, c.image_url,
        cc.name AS category_name, np.ngo_name
        FROM campaigns c
        LEFT JOIN campaign_categories cc ON cc.id = c.category_id
        INNER JOIN ngo_profiles np ON np.id = c.ngo_id
        WHERE c.status IN ('pending','approved','active')";
$params = [];
if ($category > 0) {
    $sql .= ' AND c.category_id = :category';
    $params['category'] = $category;
}
if ($ngo > 0) {
    $sql .= ' AND c.ngo_id = :ngo';
    $params['ngo'] = $ngo;
}
if ($keyword !== '') {
    $sql .= ' AND c.title LIKE :keyword';
    $params['keyword'] = '%' . $keyword . '%';
}
$sql .= ' ORDER BY c.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$campaigns = $stmt->fetchAll();
$categories = $pdo->query("SELECT id,name FROM campaign_categories WHERE status='active' ORDER BY name ASC")->fetchAll();
$ngos = $pdo->query("SELECT id,ngo_name FROM ngo_profiles WHERE verification_status='verified' ORDER BY ngo_name ASC")->fetchAll();
$pageTitle = 'Browse Campaigns';
$pageDescription = 'Discover campaigns with rich previews. Donate when status is approved or active.';
require_once dirname(__DIR__) . '/includes/dashboard_layout_start.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>
<div class="dn-page-head">
  <h1 class="dn-page-title">Browse campaigns</h1>
  <p class="dn-page-lead">Explore causes with full previews. <strong>Pending</strong> campaigns await approval; <strong>Donate</strong> is available only for <strong>approved</strong> or <strong>active</strong> campaigns.</p>
</div>

<div class="dn-browse-toolbar glass-card">
  <form method="get" class="dn-browse-filters">
    <div class="dn-browse-filters__field">
      <label for="cat">Category</label>
      <select id="cat" name="category">
        <option value="0">All categories</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= (int) $c['id'] ?>" <?= $category === (int) $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $c['name'], ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="dn-browse-filters__field">
      <label for="ngo">NGO</label>
      <select id="ngo" name="ngo">
        <option value="0">All NGOs</option>
        <?php foreach ($ngos as $n): ?>
          <option value="<?= (int) $n['id'] ?>" <?= $ngo === (int) $n['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $n['ngo_name'], ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="dn-browse-filters__field dn-browse-filters__field--grow">
      <label for="kw">Search</label>
      <input id="kw" name="keyword" type="search" value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search by title…" autocomplete="off">
    </div>
    <div class="dn-browse-filters__actions">
      <button type="submit" class="gradient-button">Apply filters</button>
    </div>
  </form>
</div>

<section class="dn-browse-results" aria-labelledby="browse-results-heading">
  <div class="dn-browse-results__head">
    <h2 id="browse-results-heading" class="dn-browse-results__title"><?= count($campaigns) ?> campaign<?= count($campaigns) === 1 ? '' : 's' ?></h2>
    <?php if (!$campaigns): ?>
      <p class="dn-browse-results__hint">Try clearing filters or searching with another keyword.</p>
    <?php endif; ?>
  </div>

  <div class="dn-donor-campaign-grid">
    <?php foreach ($campaigns as $campaign):
        $target = (float) $campaign['target_amount'];
        $collected = (float) $campaign['collected_amount'];
        $progress = $target > 0 ? min(100, ($collected / $target) * 100) : 0;
        $canDonate = in_array((string) $campaign['status'], ['approved', 'active'], true);
        $desc = trim((string) ($campaign['description'] ?? ''));
        if ($desc !== '') {
            if (function_exists('mb_strlen') && function_exists('mb_substr')) {
                $excerpt = mb_strlen($desc) > 130 ? mb_substr($desc, 0, 130) . '…' : $desc;
            } else {
                $excerpt = strlen($desc) > 130 ? substr($desc, 0, 130) . '…' : $desc;
            }
        } else {
            $excerpt = 'Support this campaign and help the NGO reach its goal.';
        }
        $catLabel = trim((string) ($campaign['category_name'] ?? ''));
        $detailUrl = APP_URL . '/donor/campaign_detail.php?id=' . (int) $campaign['id'];
        $img = sanitize(image_or_placeholder((string) ($campaign['image_url'] ?? ''), 'campaign'));
        ?>
      <article class="dn-donor-campaign-card">
        <a class="dn-donor-campaign-card__media" href="<?= htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') ?>">
          <img src="<?= $img ?>" alt="<?= htmlspecialchars((string) $campaign['title'], ENT_QUOTES, 'UTF-8') ?> campaign image" loading="lazy" width="640" height="400">
          <div class="dn-donor-campaign-card__media-shade" aria-hidden="true"></div>
          <div class="dn-donor-campaign-card__media-top">
            <?php if ($catLabel !== ''): ?>
              <span class="dn-donor-campaign-card__chip"><?= htmlspecialchars($catLabel, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
            <span class="dn-donor-campaign-card__status"><?= dash_status_badge((string) $campaign['status']) ?></span>
          </div>
        </a>
        <div class="dn-donor-campaign-card__body">
          <p class="dn-donor-campaign-card__ngo"><?= htmlspecialchars((string) $campaign['ngo_name'], ENT_QUOTES, 'UTF-8') ?></p>
          <h3 class="dn-donor-campaign-card__title">
            <a href="<?= htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $campaign['title'], ENT_QUOTES, 'UTF-8') ?></a>
          </h3>
          <p class="dn-donor-campaign-card__excerpt"><?= htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8') ?></p>
          <div class="dn-donor-campaign-card__figures">
            <div>
              <span class="dn-donor-campaign-card__fig-k">Raised</span>
              <span class="dn-donor-campaign-card__fig-v">PKR <?= number_format($collected, 0) ?></span>
            </div>
            <div>
              <span class="dn-donor-campaign-card__fig-k">Goal</span>
              <span class="dn-donor-campaign-card__fig-v">PKR <?= number_format($target, 0) ?></span>
            </div>
            <div class="dn-donor-campaign-card__fig-pct">
              <span class="dn-donor-campaign-card__fig-k">Progress</span>
              <span class="dn-donor-campaign-card__fig-v"><?= number_format($progress, 1) ?>%</span>
            </div>
          </div>
          <div class="dn-donor-campaign-card__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) round($progress) ?>">
            <span style="width:<?= number_format($progress, 2) ?>%;"></span>
          </div>
          <div class="dn-donor-campaign-card__actions">
            <a class="outline-button dn-donor-campaign-card__btn" href="<?= htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') ?>">View details</a>
            <?php if ($canDonate): ?>
              <a class="gradient-button dn-donor-campaign-card__btn dn-donor-campaign-card__btn--primary" href="<?= htmlspecialchars(APP_URL . '/donor/donate.php?campaign_id=' . (int) $campaign['id'], ENT_QUOTES, 'UTF-8') ?>">Donate</a>
            <?php else: ?>
              <span class="dn-donor-campaign-card__locked" title="Available after approval">Awaiting approval</span>
            <?php endif; ?>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <?php if (!$campaigns): ?>
    <div class="dn-browse-empty glass-card">
      <h3>No campaigns match</h3>
      <p>Relax filters or check back soon for new listings.</p>
      <a class="gradient-button" href="<?= APP_URL ?>/donor/browse_campaigns.php">Reset filters</a>
    </div>
  <?php endif; ?>
</section>
<?php require_once dirname(__DIR__) . '/includes/dashboard_layout_end.php'; ?>
