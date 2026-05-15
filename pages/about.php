<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/static_pages.php';
static_page_begin('About Us | Donate Now', '/pages/about.php');
?>
<main class="static-page-main landing-main">
  <section class="static-hero" style="--static-hero-image: url('<?= static_img('about') ?>');">
    <div class="static-hero__overlay" aria-hidden="true"></div>
    <div class="static-hero__grain" aria-hidden="true"></div>
    <div class="container static-hero__inner reveal reveal-up">
      <span class="static-hero__kicker">Who we are</span>
      <h1>About Donate Now</h1>
      <p class="static-hero__lead">We connect donors, verified NGOs, and volunteers through transparent, proof-based giving built for real local impact.</p>
    </div>
  </section>

  <section class="section mission-story section-photo-bg section-photo-bg--dark static-section" style="--section-bg-image: url('<?= static_img('values') ?>');">
    <div class="section-photo-overlay" aria-hidden="true"></div>
    <div class="section-photo-grain" aria-hidden="true"></div>
    <div class="container mission-story-inner">
      <div class="mission-text reveal reveal-up">
        <span class="section-kicker section-kicker--on-dark">Our mission</span>
        <h2>Trust should be built in, not assumed.</h2>
        <p>Traditional donation flows often leave donors guessing whether help reached the right place. NGOs juggle scattered proof. Volunteers want to contribute but rarely see one accountable path.</p>
        <p>Donate Now unifies that journey: verified NGOs, proof-backed donations, and admin oversight so every record stays traceable from first click to confirmed impact.</p>
      </div>
    </div>
  </section>

  <section class="static-section static-section--cream">
    <div class="container">
      <div class="section-heading reveal reveal-up">
        <span class="section-kicker">What we stand for</span>
        <h2>Principles behind every campaign.</h2>
      </div>
      <div class="static-card-grid stagger-group">
        <article class="static-card reveal reveal-up">
          <h3>Transparency</h3>
          <p>Donors upload payment proof; NGOs verify; admins monitor. Every step leaves a clear audit trail.</p>
        </article>
        <article class="static-card reveal reveal-up">
          <h3>Accountability</h3>
          <p>Verified NGO profiles, campaign review, and reporting tools keep the platform safe and fair.</p>
        </article>
        <article class="static-card reveal reveal-up">
          <h3>Local impact</h3>
          <p>We focus on community-led causes where donors can see tangible outcomes in their own regions.</p>
        </article>
      </div>
    </div>
  </section>

  <section class="section section-photo-bg section-photo-bg--dark static-section" style="--section-bg-image: url('<?= static_img('cta') ?>');">
    <div class="section-photo-overlay section-photo-overlay--voices" aria-hidden="true"></div>
    <div class="section-photo-grain section-photo-grain--subtle" aria-hidden="true"></div>
    <div class="container">
      <div class="static-cta-band reveal reveal-up">
        <h2>Ready to make a difference?</h2>
        <p>Explore active campaigns or register as a donor, NGO, or volunteer.</p>
        <div class="hero-cta">
          <a class="btn btn-primary" href="<?= APP_URL ?>/public/campaigns.php">Explore campaigns</a>
          <a class="btn btn-light" href="<?= APP_URL ?>/auth/register.php">Create account</a>
        </div>
      </div>
    </div>
  </section>
</main>
<?php static_page_end(); ?>
