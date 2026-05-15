<?php
$isLandingPage = $isLandingPage ?? false;
$isStaticPage = $isStaticPage ?? ($GLOBALS['isStaticPage'] ?? false);
?>
<?php
  $landingJsPath = dirname(__DIR__) . '/assets/js/landing.js';
  $landingJsVersion = is_file($landingJsPath) ? (string) filemtime($landingJsPath) : (string) time();
?>
<footer class="footer landing-footer">
  <div class="container footer-grid">
    <div class="footer-col footer-brand-col">
      <a class="footer-brand" href="<?= APP_URL ?>/index.php">
        <?= app_logo_img('app-logo brand-mark', 42, 42) ?>
        <span>Donate Now</span>
      </a>
      <p>Donate Now connects donors, verified NGOs, and volunteers through transparent proof-based giving built for real local impact.</p>
      <div class="footer-social" aria-label="Social links">
        <a href="#" aria-label="Visit Donate Now on Facebook">Fb</a>
        <a href="#" aria-label="Visit Donate Now on Instagram">Ig</a>
        <a href="#" aria-label="Visit Donate Now on LinkedIn">In</a>
      </div>
    </div>
    <div class="footer-col">
      <h4>Company</h4>
      <p><a href="<?= !empty($isLandingPage) ? APP_URL . '/index.php#about' : APP_URL . '/pages/about.php' ?>">About Us</a></p>
      <p><a href="<?= APP_URL ?>/pages/contact.php">Contact Us</a></p>
    </div>
    <div class="footer-col">
      <h4>Legal</h4>
      <p><a href="<?= APP_URL ?>/pages/privacy-policy.php">Privacy Policy</a></p>
      <p><a href="<?= APP_URL ?>/pages/terms.php">Terms</a></p>
    </div>
    <div class="footer-col">
      <h4>Contact</h4>
      <p><a href="mailto:support@donatenow.org">support@donatenow.org</a></p>
      <p><a href="tel:+923000000000">+92 300 0000000</a></p>
      <p>Lahore, Pakistan</p>
    </div>
  </div>
  <div class="container footer-bottom">
    <p>&copy; <?= date('Y') ?> Donate Now. All rights reserved.</p>
    <a href="<?= APP_URL ?>/auth/login.php">Member Login</a>
  </div>
</footer>
<button id="backToTop" class="back-to-top" aria-label="Back to top">Top</button>
<script src="<?= asset_url('assets/js/navbar.js') ?>"></script>
<script src="<?= asset_url('assets/js/main.js') ?>"></script>
<?php if ($isLandingPage || $isStaticPage): ?>
  <script src="<?= asset_url('assets/js/landing.js') ?>?v=<?= urlencode($landingJsVersion) ?>"></script>
<?php endif; ?>
</body>
</html>
