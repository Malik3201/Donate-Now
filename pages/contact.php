<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/static_pages.php';
static_page_begin('Contact Us | Donate Now', '/pages/contact.php');
?>
<main class="sp-page landing-main" id="main-content">

  <section class="sp-hero sp-hero--photo" style="--sp-hero-image: url('<?= static_img('contact') ?>');">
    <div class="sp-hero__overlay" aria-hidden="true"></div>
    <div class="sp-hero__grain" aria-hidden="true"></div>
    <div class="container sp-hero__inner reveal reveal-up">
      <nav class="sp-hero__breadcrumb" aria-label="Breadcrumb">
        <a href="<?= APP_URL ?>/index.php">Home</a>
        <span aria-hidden="true">/</span>
        <span>Contact</span>
      </nav>
      <p class="sp-kicker sp-kicker--light">Get in touch</p>
      <h1>We are here to help</h1>
      <p class="sp-hero__lead">Questions about donations, NGO verification, volunteer requests, or your account? Reach out anytime.</p>
    </div>
  </section>

  <section class="sp-section sp-section--cream sp-contact-methods">
    <div class="container">
      <div class="sp-card-grid sp-card-grid--4 stagger-group">
        <article class="sp-card reveal reveal-up">
          <span class="sp-card__icon" aria-hidden="true">@</span>
          <h3>General support</h3>
          <p><a href="mailto:support@donatenow.org">support@donatenow.org</a> for platform questions and account help.</p>
        </article>
        <article class="sp-card reveal reveal-up">
          <span class="sp-card__icon" aria-hidden="true">◆</span>
          <h3>NGO assistance</h3>
          <p>Verification, campaigns, and payment methods—our team guides verified organizations.</p>
        </article>
        <article class="sp-card reveal reveal-up">
          <span class="sp-card__icon" aria-hidden="true">◎</span>
          <h3>Donor help</h3>
          <p>Proof uploads, donation status, and campaign browsing—we help you give with confidence.</p>
        </article>
        <article class="sp-card reveal reveal-up">
          <span class="sp-card__icon" aria-hidden="true">!</span>
          <h3>Safety reports</h3>
          <p>For fraud or abuse, use in-app reporting when logged in for fastest review.</p>
        </article>
      </div>
    </div>
  </section>

  <section class="sp-section sp-section--surface">
    <div class="container sp-contact-layout">
      <div class="sp-form-panel reveal reveal-up">
        <h2>Send a message</h2>
        <p>We typically respond within one business day. This form is for display—messages are not stored until backend integration is added.</p>
        <form method="post" action="#" onsubmit="return false;" novalidate>
          <div class="sp-form-grid">
            <div class="sp-field">
              <label for="contact_name">Full name</label>
              <input id="contact_name" name="name" type="text" required placeholder="Your name" autocomplete="name">
            </div>
            <div class="sp-field">
              <label for="contact_email">Email</label>
              <input id="contact_email" name="email" type="email" required placeholder="you@example.com" autocomplete="email">
            </div>
          </div>
          <div class="sp-field">
            <label for="contact_subject">Subject</label>
            <input id="contact_subject" name="subject" type="text" required placeholder="How can we help?">
          </div>
          <div class="sp-field">
            <label for="contact_message">Message</label>
            <textarea id="contact_message" name="message" rows="5" required placeholder="Write your message…"></textarea>
          </div>
          <button type="submit" class="btn btn-primary">Send message</button>
          <p class="sp-form-note">For safety reports about campaigns or users, use the in-app report feature when logged in.</p>
        </form>
      </div>
      <aside class="sp-aside-stack reveal reveal-up">
        <div class="sp-aside-card">
          <strong>Email</strong>
          <p><a href="mailto:support@donatenow.org">support@donatenow.org</a></p>
        </div>
        <div class="sp-aside-card">
          <strong>Phone</strong>
          <p><a href="tel:+923000000000">+92 300 000 0000</a></p>
        </div>
        <div class="sp-aside-card">
          <strong>Office</strong>
          <p>Lahore, Pakistan</p>
        </div>
        <div class="sp-notice">
          <strong>Display-only form</strong>
          Submissions are not saved to the database yet. Use email or phone for urgent matters.
        </div>
      </aside>
    </div>
  </section>

  <section class="sp-section sp-section--cream">
    <div class="container">
      <header class="sp-head reveal reveal-up">
        <p class="sp-kicker">Quick answers</p>
        <h2>Common questions before you reach out</h2>
      </header>
      <div class="sp-faq-strip reveal reveal-up">
        <details>
          <summary>How do I verify my donation was received?</summary>
          <p>After payment, upload your screenshot and transaction ID. The NGO reviews and updates status to confirmed or rejected—you can track this in your donor history.</p>
        </details>
        <details>
          <summary>How does an NGO get verified?</summary>
          <p>NGOs register and complete profile review. Admin verification must be approved before full campaign participation.</p>
        </details>
        <details>
          <summary>Can I report a suspicious campaign?</summary>
          <p>Yes. Log in and use the report feature for fake NGOs, campaigns, payments, fraud, or abuse.</p>
        </details>
      </div>
    </div>
  </section>

  <section class="sp-section sp-section--surface">
    <div class="container">
      <div class="sp-cta sp-cta--light reveal reveal-up">
        <h2>Ready to explore campaigns?</h2>
        <p>Browse verified causes and support local impact today.</p>
        <div class="sp-cta__actions">
          <a class="btn btn-primary" href="<?= APP_URL ?>/public/campaigns.php">Browse campaigns</a>
          <a class="btn btn-ghost" href="<?= APP_URL ?>/auth/login.php">Member login</a>
        </div>
      </div>
    </div>
  </section>

</main>
<?php static_page_end(); ?>
