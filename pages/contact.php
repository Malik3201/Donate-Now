<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/static_pages.php';
static_page_begin('Contact Us | Donate Now', '/pages/contact.php');
?>
<main class="static-page-main landing-main">
  <section class="static-hero" style="--static-hero-image: url('<?= static_img('contact') ?>');">
    <div class="static-hero__overlay" aria-hidden="true"></div>
    <div class="static-hero__grain" aria-hidden="true"></div>
    <div class="container static-hero__inner reveal reveal-up">
      <span class="static-hero__kicker">Get in touch</span>
      <h1>Contact Us</h1>
      <p class="static-hero__lead">Questions about donations, NGO verification, or your account? We are here to help.</p>
    </div>
  </section>

  <section class="section section-photo-bg section-photo-bg--dark static-section" style="--section-bg-image: url('<?= static_img('contact') ?>');">
    <div class="section-photo-overlay" aria-hidden="true"></div>
    <div class="section-photo-grain" aria-hidden="true"></div>
    <div class="container static-contact-grid">
      <div class="static-contact-form reveal reveal-up">
        <h2 style="margin:0 0 0.35rem;font-size:1.5rem;color:var(--color-dark,#2b211b);">Send a message</h2>
        <p style="margin:0 0 1.25rem;color:var(--color-muted,#75685e);">We typically respond within one business day.</p>
        <form method="post" action="#" onsubmit="return false;">
          <div class="form-grid">
            <div class="form-group">
              <label for="contact_name">Full name</label>
              <input id="contact_name" name="name" type="text" required placeholder="Your name" autocomplete="name">
            </div>
            <div class="form-group">
              <label for="contact_email">Email</label>
              <input id="contact_email" name="email" type="email" required placeholder="you@example.com" autocomplete="email">
            </div>
          </div>
          <div class="form-group">
            <label for="contact_subject">Subject</label>
            <input id="contact_subject" name="subject" type="text" required placeholder="How can we help?">
          </div>
          <div class="form-group">
            <label for="contact_message">Message</label>
            <textarea id="contact_message" name="message" rows="5" required placeholder="Write your message…"></textarea>
          </div>
          <button type="submit" class="gradient-button">Send message</button>
          <p class="help-text" style="margin:0.75rem 0 0;">For safety reports about campaigns or users, use the in-app report feature when logged in.</p>
        </form>
      </div>
      <div class="static-info-stack reveal reveal-up">
        <div class="static-info-item">
          <strong>Email</strong>
          <p><a href="mailto:support@donatenow.org">support@donatenow.org</a></p>
        </div>
        <div class="static-info-item">
          <strong>Phone</strong>
          <p><a href="tel:+923000000000">+92 300 000 0000</a></p>
        </div>
        <div class="static-info-item">
          <strong>Office</strong>
          <p>Lahore, Pakistan</p>
        </div>
      </div>
    </div>
  </section>

  <section class="static-section static-section--cream">
    <div class="container">
      <div class="static-cta-band reveal reveal-up" style="color:var(--color-dark,#2b211b);">
        <h2 style="color:var(--color-dark,#2b211b);">Ready to explore campaigns?</h2>
        <p style="color:var(--color-muted,#75685e);">Browse verified causes and support local impact today.</p>
        <div class="hero-cta">
          <a class="btn btn-primary" href="<?= APP_URL ?>/public/campaigns.php">Browse campaigns</a>
          <a class="btn btn-ghost" href="<?= APP_URL ?>/auth/login.php">Member login</a>
        </div>
      </div>
    </div>
  </section>
</main>
<?php static_page_end(); ?>
