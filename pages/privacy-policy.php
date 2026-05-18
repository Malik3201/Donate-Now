<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/static_pages.php';
static_page_begin('Privacy Policy | Donate Now', '/pages/privacy-policy.php');
?>
<main class="sp-page landing-main" id="main-content">

  <section class="sp-hero sp-hero--warm">
    <div class="sp-hero__grain" aria-hidden="true"></div>
    <div class="container sp-hero__inner reveal reveal-up">
      <nav class="sp-hero__breadcrumb" aria-label="Breadcrumb">
        <a href="<?= APP_URL ?>/index.php">Home</a>
        <span aria-hidden="true">/</span>
        <span>Privacy Policy</span>
      </nav>
      <p class="sp-kicker">Your data</p>
      <h1>Privacy Policy</h1>
      <p class="sp-hero__lead">How Donate Now collects, uses, and protects information on our platform.</p>
    </div>
  </section>

  <section class="sp-section sp-section--surface">
    <div class="container sp-legal">
      <nav class="sp-legal__nav reveal reveal-left" aria-label="Policy sections">
        <h2>On this page</h2>
        <ul>
          <li><a href="#information">Information we collect</a></li>
          <li><a href="#use">How we use information</a></li>
          <li><a href="#sharing">Sharing</a></li>
          <li><a href="#retention">Retention &amp; security</a></li>
          <li><a href="#choices">Your choices</a></li>
          <li><a href="#contact-privacy">Contact</a></li>
        </ul>
      </nav>

      <article class="sp-legal__main reveal reveal-up">
        <div class="sp-legal__meta">
          <p><strong>Last updated:</strong> <?= date('F j, Y') ?></p>
          <p>Applies to donors, NGOs, volunteers, and visitors</p>
        </div>

        <div class="sp-highlight">
          <strong>Summary</strong>
          We collect only what is needed to run proof-based giving, verify donations, and keep the platform safe. We do not sell your personal data.
        </div>

        <div class="sp-prose">
          <h2 id="information">Information we collect</h2>
          <p>We collect account details (name, email, phone), profile information for donors, NGOs, and volunteers, donation records including payment proof uploads, campaign content, and safety reports submitted through the platform.</p>

          <h2 id="use">How we use information</h2>
          <p>Data is used to operate the platform, verify donations, moderate campaigns and reports, send transactional notifications, maintain audit logs, and prevent abuse or fraud.</p>
          <ul>
            <li>Account authentication and role-based access</li>
            <li>Donation verification between donors and NGOs</li>
            <li>Admin oversight, analytics, and compliance</li>
            <li>Email alerts related to your account activity</li>
          </ul>

          <h2 id="sharing">Sharing</h2>
          <p>We do not sell personal data. Limited information may be shared with verified NGOs regarding donations you make to their campaigns, or with administrators when investigating reports.</p>

          <h2 id="retention">Retention &amp; security</h2>
          <p>Records are retained as needed for verification, legal compliance, and platform integrity. We apply access controls, activity logging, and secure hosting practices; no system is 100% secure.</p>

          <div class="sp-highlight">
            <strong>Payment proof</strong>
            Screenshots and transaction IDs you upload are visible to the receiving NGO and authorized admins for verification purposes only.
          </div>

          <h2 id="choices">Your choices</h2>
          <p>You may update profile details in your dashboard. Contact us to request access or correction of your data where applicable.</p>

          <h2 id="contact-privacy">Contact</h2>
          <p>Privacy questions: <a href="mailto:support@donatenow.org">support@donatenow.org</a> or our <a href="<?= APP_URL ?>/pages/contact.php">Contact Us</a> page.</p>
        </div>
      </article>
    </div>
  </section>

  <section class="sp-section sp-section--cream">
    <div class="container">
      <div class="sp-cta sp-cta--light reveal reveal-up">
        <h2>Questions about your data?</h2>
        <p>Our team can help with access requests and privacy concerns.</p>
        <div class="sp-cta__actions">
          <a class="btn btn-primary" href="<?= APP_URL ?>/pages/contact.php">Contact support</a>
          <a class="btn btn-ghost" href="<?= APP_URL ?>/pages/terms.php">Read terms</a>
        </div>
      </div>
    </div>
  </section>

</main>
<?php static_page_end(); ?>
