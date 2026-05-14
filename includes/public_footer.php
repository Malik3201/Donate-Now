<?php $isLandingPage = $isLandingPage ?? false; ?>
<?php
  $landingJsPath = dirname(__DIR__) . '/assets/js/landing.js';
  $landingJsVersion = is_file($landingJsPath) ? (string) filemtime($landingJsPath) : (string) time();
?>
<footer class="footer landing-footer">
  <div class="container footer-grid">
    <div class="footer-brand-col">
      <a class="footer-brand" href="<?= APP_URL ?>/index.php">
        <span class="brand-mark" aria-hidden="true"></span>
        <span>Donate Now</span>
      </a>
      <p>Donate Now connects donors, verified NGOs, and volunteers through transparent proof-based giving built for real local impact.</p>
      <div class="footer-social" aria-label="Social links">
        <a href="#" aria-label="Visit Donate Now on Facebook">Fb</a>
        <a href="#" aria-label="Visit Donate Now on Instagram">Ig</a>
        <a href="#" aria-label="Visit Donate Now on LinkedIn">In</a>
      </div>
    </div>
    <div>
      <h4>Platform</h4>
      <p><a href="<?= APP_URL ?>/public/campaigns.php">Campaigns</a></p>
      <p><a href="<?= APP_URL ?>/pages/how-it-works.php">How It Works</a></p>
      <p><a href="<?= APP_URL ?>/pages/about.php">NGOs</a></p>
      <p><a href="<?= APP_URL ?>/auth/register.php?role=volunteer">Volunteer</a></p>
    </div>
    <div>
      <h4>Support</h4>
      <p><a href="<?= APP_URL ?>/pages/contact.php">Contact</a></p>
      <p><a href="<?= APP_URL ?>/pages/faqs.php">FAQs</a></p>
      <p><a href="<?= APP_URL ?>/reports/create_report.php">Reports</a></p>
    </div>
    <div>
      <h4>Legal</h4>
      <p><a href="<?= APP_URL ?>/pages/privacy-policy.php">Privacy Policy</a></p>
      <p><a href="<?= APP_URL ?>/pages/terms.php">Terms</a></p>
      <h4 class="footer-contact-title">Contact</h4>
      <p>support@donatenow.org</p>
      <p>+92 300 0000000</p>
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
<?php if ($isLandingPage): ?>
  <script src="<?= asset_url('assets/js/landing.js') ?>?v=<?= urlencode($landingJsVersion) ?>"></script>
<?php endif; ?>
</body>
</html>
