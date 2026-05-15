<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/static_pages.php';
static_page_begin('Privacy Policy | Donate Now', '/pages/privacy-policy.php');
?>
<main class="static-page-main landing-main">
  <section class="static-hero" style="--static-hero-image: url('<?= static_img('privacy') ?>');">
    <div class="static-hero__overlay" aria-hidden="true"></div>
    <div class="static-hero__grain" aria-hidden="true"></div>
    <div class="container static-hero__inner reveal reveal-up">
      <span class="static-hero__kicker">Your data</span>
      <h1>Privacy Policy</h1>
      <p class="static-hero__lead">How Donate Now collects, uses, and protects information on our platform.</p>
    </div>
  </section>

  <section class="section section-photo-bg section-photo-bg--dark static-section" style="--section-bg-image: url('<?= static_img('privacy') ?>');">
    <div class="section-photo-overlay" aria-hidden="true"></div>
    <div class="section-photo-grain section-photo-grain--subtle" aria-hidden="true"></div>
    <div class="container">
      <div class="glass-card static-prose reveal reveal-up" style="padding:clamp(1.5rem,3vw,2.25rem);background:rgba(255,249,242,0.94);">
        <p><strong>Last updated:</strong> <?= date('F j, Y') ?></p>
        <h2>Information we collect</h2>
        <p>We collect account details (name, email, phone), profile information for donors, NGOs, and volunteers, donation records including payment proof uploads, campaign content, and safety reports submitted through the platform.</p>
        <h2>How we use information</h2>
        <p>Data is used to operate the platform, verify donations, moderate campaigns and reports, send transactional notifications, maintain audit logs, and prevent abuse or fraud.</p>
        <ul>
          <li>Account authentication and role-based access</li>
          <li>Donation verification between donors and NGOs</li>
          <li>Admin oversight, analytics, and compliance</li>
          <li>Email alerts related to your account activity</li>
        </ul>
        <h2>Sharing</h2>
        <p>We do not sell personal data. Limited information may be shared with verified NGOs regarding donations you make to their campaigns, or with administrators when investigating reports.</p>
        <h2>Retention &amp; security</h2>
        <p>Records are retained as needed for verification, legal compliance, and platform integrity. We apply access controls, activity logging, and secure hosting practices; no system is 100% secure.</p>
        <h2>Your choices</h2>
        <p>You may update profile details in your dashboard. Contact us to request access or correction of your data where applicable.</p>
        <h2>Contact</h2>
        <p>Privacy questions: <a href="mailto:support@donatenow.org">support@donatenow.org</a> or our <a href="<?= APP_URL ?>/pages/contact.php">Contact Us</a> page.</p>
      </div>
    </div>
  </section>
</main>
<?php static_page_end(); ?>
