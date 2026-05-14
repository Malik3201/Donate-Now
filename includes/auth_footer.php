<?php
declare(strict_types=1);
$authJsPath = dirname(__DIR__) . '/assets/js/auth-pages.js';
$authJsV = is_file($authJsPath) ? (string) filemtime($authJsPath) : (string) time();
?>
<script src="<?= asset_url('assets/js/forms.js') ?>"></script>
<script src="<?= asset_url('assets/js/auth-pages.js') ?>?v=<?= urlencode($authJsV) ?>"></script>
</body>
</html>
