<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/static_pages.php';
static_page_begin('Terms & Conditions | Donate Now', '/pages/terms.php');
?>
<main class="sp-page landing-main" id="main-content">

  <section class="sp-hero sp-hero--warm">
    <div class="sp-hero__grain" aria-hidden="true"></div>
    <div class="container sp-hero__inner reveal reveal-up">
      <nav class="sp-hero__breadcrumb" aria-label="Breadcrumb">
        <a href="<?= APP_URL ?>/index.php">Home</a>
        <span aria-hidden="true">/</span>
        <span>Terms</span>
      </nav>
      <p class="sp-kicker">Platform rules</p>
      <h1>Terms &amp; Conditions</h1>
      <p class="sp-hero__lead">The terms that govern use of Donate Now by donors, NGOs, volunteers, and visitors.</p>
    </div>
  </section>

  <section class="sp-section sp-section--surface">
    <div class="container sp-legal">
      <nav class="sp-legal__nav reveal reveal-left" aria-label="Terms sections">
        <h2>On this page</h2>
        <ul>
          <li><a href="#acceptance">Acceptance</a></li>
          <li><a href="#roles">Roles &amp; responsibilities</a></li>
          <li><a href="#donations">Donations</a></li>
          <li><a href="#campaigns">Campaigns &amp; content</a></li>
          <li><a href="#conduct">Prohibited conduct</a></li>
          <li><a href="#liability">Limitation of liability</a></li>
          <li><a href="#changes">Changes</a></li>
          <li><a href="#contact-terms">Contact</a></li>
        </ul>
      </nav>

      <article class="sp-legal__main reveal reveal-up">
        <div class="sp-legal__meta">
          <p><strong>Last updated:</strong> <?= date('F j, Y') ?></p>
          <p>By using Donate Now you agree to these terms</p>
        </div>

        <div class="sp-highlight">
          <strong>Important</strong>
          Donate Now facilitates proof-based giving but does not process card payments directly. NGOs confirm donations after reviewing submitted proof.
        </div>

        <div class="sp-prose">
          <h2 id="acceptance">Acceptance</h2>
          <p>By creating an account or using Donate Now, you agree to these terms and our <a href="<?= APP_URL ?>/pages/privacy-policy.php">Privacy Policy</a>.</p>

          <h2 id="roles">Roles &amp; responsibilities</h2>
          <ul>
            <li><strong>Donors</strong> must submit accurate payment proof and transaction references.</li>
            <li><strong>NGOs</strong> must provide correct payment methods and verify donations in good faith.</li>
            <li><strong>Volunteers</strong> must represent themselves honestly when joining campaigns.</li>
            <li><strong>Admins</strong> may moderate content, suspend accounts, and resolve reports.</li>
          </ul>

          <h2 id="donations">Donations</h2>
          <p>Donate Now facilitates proof-based giving but does not process card payments directly. NGOs confirm or reject donations after reviewing submitted proof. Disputes should be raised through platform reports.</p>

          <h2 id="campaigns">Campaigns &amp; content</h2>
          <p>NGOs are responsible for campaign accuracy. Admin may approve, reject, or pause campaigns that violate guidelines or mislead donors.</p>

          <h2 id="conduct">Prohibited conduct</h2>
          <p>Fraud, impersonation, harassment, false payment proof, or misuse of reports may result in account suspension or removal.</p>

          <div class="sp-highlight">
            <strong>Reporting</strong>
            Use the in-app report feature for fake NGOs, campaigns, payments, fraud, or abuse. Admins review reports and may apply account controls.
          </div>

          <h2 id="liability">Limitation of liability</h2>
          <p>The platform is provided as-is. We are not liable for offline payment disputes between donors and NGOs beyond reasonable moderation efforts.</p>

          <h2 id="changes">Changes</h2>
          <p>We may update these terms. Continued use after changes constitutes acceptance.</p>

          <h2 id="contact-terms">Contact</h2>
          <p>Questions: <a href="<?= APP_URL ?>/pages/contact.php">Contact Us</a>.</p>
        </div>
      </article>
    </div>
  </section>

  <section class="sp-section sp-section--cream">
    <div class="container">
      <div class="sp-cta sp-cta--light reveal reveal-up">
        <h2>Need help understanding these terms?</h2>
        <p>Reach out to our support team—we are happy to clarify platform rules.</p>
        <div class="sp-cta__actions">
          <a class="btn btn-primary" href="<?= APP_URL ?>/pages/contact.php">Contact support</a>
          <a class="btn btn-ghost" href="<?= APP_URL ?>/pages/privacy-policy.php">Privacy policy</a>
        </div>
      </div>
    </div>
  </section>

</main>
<?php static_page_end(); ?>
