<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/public_header.php';
$pdo = db();
$category = intval($_GET['category'] ?? 0);
$ngo = intval($_GET['ngo'] ?? 0);
$keyword = trim((string)($_GET['keyword'] ?? ''));
$sql = "SELECT c.id, c.title, c.description, c.target_amount, c.collected_amount, c.status, c.image_url, cc.name AS category_name, np.ngo_name FROM campaigns c LEFT JOIN campaign_categories cc ON cc.id = c.category_id INNER JOIN ngo_profiles np ON np.id = c.ngo_id WHERE c.status IN ('pending','approved','active')";
$params=[]; if($category>0){$sql.=' AND c.category_id=:category';$params['category']=$category;} if($ngo>0){$sql.=' AND c.ngo_id=:ngo';$params['ngo']=$ngo;} if($keyword!==''){$sql.=' AND c.title LIKE :keyword';$params['keyword']='%'.$keyword.'%';}
$sql.=' ORDER BY c.created_at DESC';
$stmt=$pdo->prepare($sql);$stmt->execute($params);$campaigns=$stmt->fetchAll();
$categories=$pdo->query("SELECT id,name FROM campaign_categories WHERE status='active' ORDER BY name ASC")->fetchAll();
$ngos=$pdo->query("SELECT id,ngo_name FROM ngo_profiles WHERE verification_status='verified' ORDER BY ngo_name ASC")->fetchAll();
?>
<main>
<section class="section"><div class="container"><div class="glass-card" style="padding:1.4rem;"><h1 class="section-title">Explore Campaigns</h1><p class="section-subtitle">Support transparent local causes with proof-based donation flow.</p><form class="form-grid"><input name="keyword" value="<?= sanitize($keyword) ?>" placeholder="Search campaign keyword"><select name="category"><option value="0">All Categories</option><?php foreach($categories as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $category===(int)$c['id']?'selected':'' ?>><?= sanitize($c['name']) ?></option><?php endforeach; ?></select><select name="ngo"><option value="0">All NGOs</option><?php foreach($ngos as $n): ?><option value="<?= (int)$n['id'] ?>" <?= $ngo===(int)$n['id']?'selected':'' ?>><?= sanitize($n['ngo_name']) ?></option><?php endforeach; ?></select><button class="gradient-button" type="submit">Filter</button></form></div></div></section>
<section class="section" style="padding-top:0;"><div class="container"><div class="featured-grid"><?php if(!$campaigns): ?><div class="empty-state glass-card">No campaigns found for selected filters.</div><?php endif; ?><?php foreach($campaigns as $campaign): $progress=((float)$campaign['target_amount']>0)?min(100,(((float)$campaign['collected_amount']/(float)$campaign['target_amount'])*100)):0; ?><article class="campaign-card"><img src="<?= sanitize(image_or_placeholder((string)($campaign['image_url']??''),'campaign')) ?>" alt="Campaign image"><div style="margin-top:.6rem;display:flex;justify-content:space-between;"><span class="badge"><?= sanitize((string)($campaign['category_name'] ?? 'General')) ?></span><span class="status-badge"><?= sanitize($campaign['status']) ?></span></div><h3 style="margin-top:.55rem;"><?= sanitize($campaign['title']) ?></h3><p style="color:var(--text-soft)"><?= sanitize($campaign['ngo_name']) ?></p><p><?= sanitize(substr((string)$campaign['description'],0,110)) ?>...</p><p>Target: PKR <?= number_format((float)$campaign['target_amount'],2) ?> | Collected: PKR <?= number_format((float)$campaign['collected_amount'],2) ?></p><div class="progress-wrap"><div class="progress-bar" style="width:<?= number_format($progress,2) ?>%"></div></div><small><?= number_format($progress,2) ?>%</small><div style="margin-top:.6rem;"><a class="gradient-button" href="<?= APP_URL ?>/public/campaign_detail.php?id=<?= (int)$campaign['id'] ?>">View Details</a></div></article><?php endforeach; ?></div></div></section>
</main>
<?php require_once dirname(__DIR__) . '/includes/public_footer.php'; ?>
