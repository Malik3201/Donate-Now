    </div>
    <?php require dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
  </div>
</div>
<script src="<?= asset_url('assets/js/navbar.js') ?>"></script>
<script src="<?= asset_url('assets/js/forms.js') ?>"></script>
<script src="<?= asset_url('assets/js/modals.js') ?>"></script>
<?php
$dashJsPath = dirname(__DIR__) . '/assets/js/dashboard.js';
$dashJsV = is_file($dashJsPath) ? (string) filemtime($dashJsPath) : (string) time();
?>
<script src="<?= asset_url('assets/js/dashboard.js') ?>?v=<?= urlencode($dashJsV) ?>"></script>
</body>
</html>
