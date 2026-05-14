<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/role_check.php';
require_role(['volunteer']);

$pdo = db();
$keyword = trim((string) ($_GET['keyword'] ?? ''));
$category = (int) ($_GET['category'] ?? 0);

$sql = "SELECT c.id, c.title, c.description, c.target_amount, c.collected_amount, c.status, c.start_date, c.end_date, c.image_url,
        cc.name AS category_name, np.ngo_name
        FROM campaigns c
        INNER JOIN ngo_profiles np ON np.id = c.ngo_id
        LEFT JOIN campaign_categories cc ON cc.id = c.category_id
        WHERE c.status IN ('approved','active')";
$params = [];
if ($keyword !== '') {
    $sql .= ' AND c.title LIKE :k';
    $params['k'] = '%' . $keyword . '%';
}
if ($category > 0) {
    $sql .= ' AND c.category_id = :cat';
    $params['cat'] = $category;
}
$sql .= ' ORDER BY c.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$campaigns = $stmt->fetchAll();
$cats = $pdo->query("SELECT id,name FROM campaign_categories WHERE status='active' ORDER BY name ASC")->fetchAll();

/** campaign_id => 'pending'|'accepted' — same rule as join_campaign.php (cannot re-join while active on campaign) */
$volunteerId = 0;
$vcByCampaign = [];
$stmt = $pdo->prepare('SELECT id FROM volunteer_profiles WHERE user_id = :u LIMIT 1');
$stmt->execute(['u' => (int) $authUser['id']]);
$volunteerId = (int) ($stmt->fetchColumn() ?: 0);
if ($volunteerId > 0) {
    $stmt = $pdo->prepare("SELECT campaign_id, status FROM volunteer_campaigns WHERE volunteer_id = :v AND status IN ('pending','accepted')");
    $stmt->execute(['v' => $volunteerId]);
    while ($row = $stmt->fetch()) {
        $vcByCampaign[(int) $row['campaign_id']] = (string) $row['status'];
    }
}

$pageTitle = 'Browse Campaigns';
$pageDescription = 'Find approved or active campaigns and send a join request to the NGO.';
require_once dirname(__DIR__) . '/includes/dashboard_layout_start.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>
<div class="dn-page-head">
  <h1 class="dn-page-title">Volunteer campaigns</h1>
  <p class="dn-page-lead">Only <strong>approved</strong> and <strong>active</strong> campaigns accept volunteer requests. Open a campaign to read more, then join from this list.</p>
</div>

<div class="dn-browse-toolbar glass-card">
  <form method="get" class="dn-browse-filters">
    <div class="dn-browse-filters__field dn-browse-filters__field--grow">
      <label for="vol-browse-kw">Search</label>
      <input id="vol-browse-kw" name="keyword" type="search" value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search by title…" autocomplete="off">
    </div>
    <div class="dn-browse-filters__field">
      <label for="vol-browse-cat">Category</label>
      <select id="vol-browse-cat" name="category">
        <option value="0">All categories</option>
        <?php foreach ($cats as $c): ?>
          <option value="<?= (int) $c['id'] ?>" <?= $category === (int) $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $c['name'], ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="dn-browse-filters__actions">
      <button type="submit" class="gradient-button">Apply filters</button>
    </div>
  </form>
</div>

<section class="dn-browse-results" aria-labelledby="vol-browse-results-heading">
  <div class="dn-browse-results__head">
    <h2 id="vol-browse-results-heading" class="dn-browse-results__title"><?= count($campaigns) ?> campaign<?= count($campaigns) === 1 ? '' : 's' ?></h2>
    <?php if (!$campaigns): ?>
      <p class="dn-browse-results__hint">Try another search or category.</p>
    <?php endif; ?>
  </div>

  <div class="dn-donor-campaign-grid">
    <?php foreach ($campaigns as $campaign):
        $target = (float) $campaign['target_amount'];
        $collected = (float) $campaign['collected_amount'];
        $progress = $target > 0 ? min(100, ($collected / $target) * 100) : 0;
        $desc = trim((string) ($campaign['description'] ?? ''));
        if ($desc !== '') {
            if (function_exists('mb_strlen') && function_exists('mb_substr')) {
                $excerpt = mb_strlen($desc) > 130 ? mb_substr($desc, 0, 130) . '…' : $desc;
            } else {
                $excerpt = strlen($desc) > 130 ? substr($desc, 0, 130) . '…' : $desc;
            }
        } else {
            $excerpt = 'Offer your time and skills to help this campaign succeed.';
        }
        $catLabel = trim((string) ($campaign['category_name'] ?? ''));
        $detailUrl = APP_URL . '/public/campaign_detail.php?id=' . (int) $campaign['id'];
        $joinUrl = APP_URL . '/volunteer/join_campaign.php?campaign_id=' . (int) $campaign['id'];
        $myRequestsUrl = APP_URL . '/volunteer/my_campaigns.php';
        $vcStatus = $vcByCampaign[(int) $campaign['id']] ?? null;
        $img = sanitize(image_or_placeholder((string) ($campaign['image_url'] ?? ''), 'campaign'));
        $start = (string) ($campaign['start_date'] ?? '');
        $end = (string) ($campaign['end_date'] ?? '');
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
          <?php if ($start !== '' || $end !== ''): ?>
            <p class="dn-donor-campaign-card__dates"><?= htmlspecialchars($start, ENT_QUOTES, 'UTF-8') ?> – <?= htmlspecialchars($end, ENT_QUOTES, 'UTF-8') ?></p>
          <?php endif; ?>
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
          <?php if ($vcStatus !== null): ?>
            <p class="dn-volunteer-browse-status-note"><?= $vcStatus === 'pending'
              ? 'You have sent a request — waiting for the NGO.'
              : 'You are on this campaign as a volunteer.' ?></p>
          <?php endif; ?>
          <div class="dn-donor-campaign-card__actions">
            <a class="outline-button dn-donor-campaign-card__btn" href="<?= htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') ?>">View details</a>
            <?php if ($vcStatus === null): ?>
              <a class="gradient-button dn-donor-campaign-card__btn dn-donor-campaign-card__btn--primary" href="<?= htmlspecialchars($joinUrl, ENT_QUOTES, 'UTF-8') ?>">Join as volunteer</a>
            <?php else: ?>
              <a class="gradient-button dn-donor-campaign-card__btn dn-donor-campaign-card__btn--primary" href="<?= htmlspecialchars($myRequestsUrl, ENT_QUOTES, 'UTF-8') ?>">My requests</a>
            <?php endif; ?>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <?php if (!$campaigns): ?>
    <div class="dn-browse-empty glass-card">
      <h3>No campaigns match</h3>
      <p>Clear the search or pick “All categories”, then try again.</p>
      <a class="gradient-button" href="<?= htmlspecialchars(APP_URL . '/volunteer/browse_campaigns.php', ENT_QUOTES, 'UTF-8') ?>">Reset filters</a>
    </div>
  <?php endif; ?>
</section>
<?php require_once dirname(__DIR__) . '/includes/dashboard_layout_end.php'; ?>
