<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/role_check.php';
require_once dirname(__DIR__) . '/includes/ngo_detail_public.php';
require_role(['donor']);

$pdo = db();
$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT np.*, u.profile_photo_url FROM ngo_profiles np INNER JOIN users u ON u.id = np.user_id WHERE np.id = :id AND np.verification_status = :status LIMIT 1');
$stmt->execute(['id' => $id, 'status' => 'verified']);
$ngo = $stmt->fetch();
if (!$ngo) {
    http_response_code(404);
    exit('NGO not found.');
}

$pageTitle = (string) $ngo['ngo_name'];
$pageDescription = 'NGO profile and headquarters location.';
require_once dirname(__DIR__) . '/includes/dashboard_layout_start.php';
require dirname(__DIR__) . '/includes/breadcrumbs.php';
?>
<p style="margin:0 0 1rem;"><a class="outline-button" href="<?= htmlspecialchars(APP_URL . '/donor/browse_campaigns.php', ENT_QUOTES, 'UTF-8') ?>">← Browse campaigns</a></p>
<?php render_ngo_public_detail($ngo); ?>
<?php require_once dirname(__DIR__) . '/includes/dashboard_layout_end.php'; ?>
