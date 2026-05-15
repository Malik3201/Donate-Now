<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/static_pages.php';
static_page_begin('Terms & Conditions | Donate Now', '/pages/terms.php');
?>
<main class="static-page-main landing-main">
  <section class="static-hero" style="--static-hero-image: url('<?= static_img('terms') ?>');">
    <div class="static-hero__overlay" aria-hidden="true"></div>
    <div class="static-hero__grain" aria-hidden="true"></div>
    <div class="container static-hero__inner reveal reveal-up">
      <span class="static-hero__kicker">Platform rules</span>
      <h1>Terms &amp; Conditions</h1>
      <p class="static-hero__lead">The terms that govern use of Donate Now by donors, NGOs, volunteers, and visitors.</p>
    </div>
  </section>

  <section class="section section-photo-bg section-photo-bg--dark static-section" style="--section-bg-image: url('<?= static_img('terms') ?>');">
    <div class="section-photo-overlay" aria-hidden="true"></div>
    <div class="section-photo-grain section-photo-grain--subtle" aria-hidden="true"></div>
    <div class="container">
      <div class="glass-card static-prose reveal reveal-up" style="padding:clamp(1.5rem,3vw,2.25rem);background:rgba(255,249,242,0.94);">
        <p><strong>Last updated:</strong> <?= date('F j, Y') ?></p>
        <h2>Acceptance</h2>
        <p>By creating an account or using Donate Now, you agree to these terms and our <a href="<?= APP_URL ?>/pages/privacy-policy.php">Privacy Policy</a>.</p>
        <h2>Roles &amp; responsibilities</h2>
        <ul>
          <li><strong>Donors</strong> must submit accurate payment proof and transaction references.</li>
          <li><strong>NGOs</strong> must provide correct payment methods and verify donations in good faith.</li>
          <li><strong>Volunteers</strong> must represent themselves honestly when joining campaigns.</li>
          <li><strong>Admins</strong> may moderate content, suspend accounts, and resolve reports.</li>
        </ul>
        <h2>Donations</h2>
        <p>Donate Now facilitates proof-based giving but does not process card payments directly. NGOs confirm or reject donations after reviewing submitted proof. Disputes should be raised through platform reports.</p>
        <h2>Campaigns &amp; content</h2>
        <p>NGOs are responsible for campaign accuracy. Admin may approve, reject, or pause campaigns that violate guidelines or mislead donors.</p>
        <h2>Prohibited conduct</h2>
        <p>Fraud, impersonation, harassment, false payment proof, or misuse of reports may result in account suspension or removal.</p>
        <h2>Limitation of liability</h2>
        <p>The platform is provided as-is. We are not liable for offline payment disputes between donors and NGOs beyond reasonable moderation efforts.</p>
        <h2>Changes</h2>
        <p>We may update these terms. Continued use after changes constitutes acceptance.</p>
        <h2>Contact</h2>
        <p>Questions: <a href="<?= APP_URL ?>/pages/contact.php">Contact Us</a>.</p>
      </div>
    </div>
  </section>
</main>
<?php static_page_end(); ?>
