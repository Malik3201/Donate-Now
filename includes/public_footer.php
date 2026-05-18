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
        <a href="#" class="footer-social__link" aria-label="Visit Donate Now on Facebook">
          <svg class="footer-social__icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M13.5 8.5V6.75A2.25 2.25 0 0 1 15.75 4.5h1.5V2.25h-2.25A4.5 4.5 0 0 0 10.5 6.75V8.5H8v2.75h2.5V20h3V11.25h2.4l.35-2.75H13.5z"/>
          </svg>
        </a>
        <a href="#" class="footer-social__link" aria-label="Visit Donate Now on Instagram">
          <svg class="footer-social__icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M8 3a5 5 0 0 0-5 5v8a5 5 0 0 0 5 5h8a5 5 0 0 0 5-5V8a5 5 0 0 0-5-5H8zm0 2h8a3 3 0 0 1 3 3v8a3 3 0 0 1-3 3H8a3 3 0 0 1-3-3V8a3 3 0 0 1 3-3zm9.75 1.25a1.25 1.25 0 1 0 0 2.5 1.25 1.25 0 0 0 0-2.5zM12 8.5A3.5 3.5 0 1 0 12 15.5 3.5 3.5 0 0 0 12 8.5zm0 2a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3z"/>
          </svg>
        </a>
        <a href="#" class="footer-social__link" aria-label="Visit Donate Now on LinkedIn">
          <svg class="footer-social__icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M4 3a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1V4a1 1 0 0 0-1-1H4zm3.5 4.25A1.75 1.75 0 1 1 7.5 9.75 1.75 1.75 0 0 1 7.5 7.25zM6 18v-6.5h3V18H6zm4.5 0v-3.75c0-1 .75-1.75 1.85-1.75 1.05 0 1.65.7 1.65 1.75V18H16.5v-4c0-2.15-1.25-3.35-3.1-3.35-1.4 0-2.05.75-2.4 1.45V11.5h-3v6.5h3z"/>
          </svg>
        </a>
      </div>
    </div>
    <div class="footer-col">
      <h4>Company</h4>
      <p><a href="<?= app_url('pages/about.php') ?>">About Us</a></p>
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
<button id="backToTop" class="back-to-top" type="button" aria-label="Back to top">
  <svg class="back-to-top__icon" width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <path d="M12 19V5M12 5l-6 6M12 5l6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
  </svg>
</button>
<script src="<?= asset_url('assets/js/navbar.js') ?>"></script>
<script src="<?= asset_url('assets/js/main.js') ?>"></script>
<?php if ($isLandingPage || $isStaticPage): ?>
  <script src="<?= asset_url('assets/js/landing.js') ?>?v=<?= urlencode($landingJsVersion) ?>"></script>
<?php endif; ?>
</body>
</html>
