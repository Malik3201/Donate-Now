<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/static_pages.php';
static_page_begin('About Us | Donate Now', '/pages/about.php');
?>
<main class="sp-page landing-main" id="main-content">

  <section class="sp-hero sp-hero--photo" style="--sp-hero-image: url('<?= static_img('about') ?>');">
    <div class="sp-hero__overlay" aria-hidden="true"></div>
    <div class="sp-hero__grain" aria-hidden="true"></div>
    <div class="container sp-hero__inner reveal reveal-up">
      <nav class="sp-hero__breadcrumb" aria-label="Breadcrumb">
        <a href="<?= APP_URL ?>/index.php">Home</a>
        <span aria-hidden="true">/</span>
        <span>About Us</span>
      </nav>
      <p class="sp-kicker sp-kicker--light">Who we are</p>
      <h1>Building trust into every act of giving</h1>
      <p class="sp-hero__lead">Donate Now connects donors, verified NGOs, and volunteers through transparent, proof-based giving built for real local impact.</p>
    </div>
  </section>

  <section class="sp-section sp-section--surface">
    <div class="container sp-split">
      <figure class="sp-split__media reveal reveal-left">
        <img src="<?= static_img('values') ?>" alt="Community volunteers supporting a local cause" loading="lazy" width="560" height="580">
      </figure>
      <div class="reveal reveal-right">
        <p class="sp-kicker">Why we exist</p>
        <h2 style="font-family:'Fraunces',serif;font-size:clamp(1.65rem,3vw,2.35rem);margin:0 0 1rem;">Trust should be built in—not assumed</h2>
        <div class="sp-lead">
          <p>Traditional donation flows often leave donors guessing whether help reached the right place. NGOs juggle scattered proof. Volunteers want to contribute but rarely see one accountable path.</p>
          <p>Donate Now unifies that journey: verified NGOs, proof-backed donations, and admin oversight so every record stays traceable from first click to confirmed impact.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="sp-section sp-section--cream">
    <div class="container">
      <header class="sp-head reveal reveal-up">
        <p class="sp-kicker">Mission · Vision · Values</p>
        <h2>What guides every campaign on the platform</h2>
      </header>
      <div class="sp-card-grid sp-card-grid--3 stagger-group">
        <article class="sp-card reveal reveal-up">
          <span class="sp-card__icon" aria-hidden="true">◎</span>
          <h3>Our mission</h3>
          <p>Make local giving transparent, verifiable, and accessible—so donors, NGOs, and volunteers can focus on impact instead of uncertainty.</p>
        </article>
        <article class="sp-card reveal reveal-up">
          <span class="sp-card__icon" aria-hidden="true">◇</span>
          <h3>Our vision</h3>
          <p>A culture of accountable community support where every donation has a clear trail and every NGO earns trust through proof—not promises.</p>
        </article>
        <article class="sp-card reveal reveal-up">
          <span class="sp-card__icon" aria-hidden="true">✦</span>
          <h3>Our values</h3>
          <p>Transparency, accountability, and local impact—the same principles behind every verification step, report, and record on Donate Now.</p>
        </article>
      </div>
    </div>
  </section>

  <section class="sp-section sp-photo-band">
    <div class="sp-photo-band__bg" style="--sp-band-image: url('<?= static_img('hero') ?>');" aria-hidden="true"></div>
    <div class="sp-photo-band__overlay" aria-hidden="true"></div>
    <div class="container">
      <header class="sp-head reveal reveal-up" style="max-width:640px;">
        <p class="sp-kicker sp-kicker--light">Transparency &amp; trust</p>
        <h2 style="color:#fff;">Proof-based giving, not guesswork</h2>
        <p style="color:rgba(253,250,245,0.82);">Donors upload payment proof. NGOs verify. Admins monitor. Every step leaves a reviewable audit trail.</p>
      </header>
      <div class="sp-card-grid sp-card-grid--3 stagger-group">
        <article class="sp-card sp-card--glass reveal reveal-up">
          <span class="sp-card__icon" aria-hidden="true">↑</span>
          <h3>Manual proof flow</h3>
          <p>Screenshot and transaction ID uploaded after payment—verified by the receiving NGO before confirmation.</p>
        </article>
        <article class="sp-card sp-card--glass reveal reveal-up">
          <span class="sp-card__icon" aria-hidden="true">✓</span>
          <h3>Verified NGOs</h3>
          <p>Organizations are reviewed before full participation so donors know who they are supporting.</p>
        </article>
        <article class="sp-card sp-card--glass reveal reveal-up">
          <span class="sp-card__icon" aria-hidden="true">◉</span>
          <h3>Admin oversight</h3>
          <p>Reports, moderation, and transaction visibility keep the ecosystem accountable for everyone.</p>
        </article>
      </div>
    </div>
  </section>

  <section class="sp-section sp-section--surface">
    <div class="container">
      <header class="sp-head reveal reveal-up">
        <p class="sp-kicker">How we connect</p>
        <h2>Donors, NGOs, and volunteers—one accountable platform</h2>
      </header>
      <div class="sp-role-strip stagger-group">
        <article class="sp-role-item reveal reveal-up">
          <h3>Donors</h3>
          <p>Explore campaigns, pay via NGO methods, upload proof, and track status from pending to confirmed in your history.</p>
        </article>
        <article class="sp-role-item reveal reveal-up">
          <h3>Verified NGOs</h3>
          <p>Create campaigns, manage payment channels, verify proofs, and coordinate volunteers with complete records.</p>
        </article>
        <article class="sp-role-item reveal reveal-up">
          <h3>Volunteers</h3>
          <p>Join campaigns with your time and skills—request to participate and follow clear accept or decline responses.</p>
        </article>
      </div>
    </div>
  </section>

  <section class="sp-section sp-section--cream">
    <div class="container sp-split">
      <div class="reveal reveal-left">
        <p class="sp-kicker">Community impact</p>
        <h2 style="font-family:'Fraunces',serif;font-size:clamp(1.65rem,3vw,2.35rem);margin:0 0 1rem;">Local causes, visible outcomes</h2>
        <div class="sp-lead">
          <p>We focus on community-led initiatives where donors can see tangible results in their own regions—not distant, opaque transactions.</p>
          <p>Every campaign shows progress, NGO details, and verification status so giving feels personal and meaningful.</p>
        </div>
      </div>
      <figure class="sp-split__media reveal reveal-right">
        <img src="<?= static_img('cta') ?>" alt="Hands joining together in community support" loading="lazy" width="560" height="580">
      </figure>
    </div>
  </section>

  <section class="sp-section sp-section--dark">
    <div class="container">
      <header class="sp-head reveal reveal-up">
        <p class="sp-kicker sp-kicker--light">Safety &amp; verification</p>
        <h2>Protection built into every action</h2>
        <p>Report suspicious activity, rely on admin moderation, and keep permanent donation records for full traceability.</p>
      </header>
      <div class="sp-card-grid sp-card-grid--2 stagger-group">
        <article class="sp-card sp-card--glass reveal reveal-up">
          <h3>Safety reports</h3>
          <p>Flag fake NGOs, campaigns, payments, fraud, or abuse directly through the platform.</p>
        </article>
        <article class="sp-card sp-card--glass reveal reveal-up">
          <h3>Permanent records</h3>
          <p>Donation status, proof details, and moderation history remain visible for donors, NGOs, and admins.</p>
        </article>
      </div>
    </div>
  </section>

  <section class="sp-section sp-photo-band">
    <div class="sp-photo-band__bg" style="--sp-band-image: url('<?= static_img('cta') ?>');" aria-hidden="true"></div>
    <div class="sp-photo-band__overlay" aria-hidden="true"></div>
    <div class="container">
      <div class="sp-cta sp-cta--dark reveal reveal-scale">
        <h2>Ready to make a difference?</h2>
        <p>Explore active campaigns or join as a donor, NGO, or volunteer.</p>
        <div class="sp-cta__actions">
          <a class="btn btn-primary" href="<?= APP_URL ?>/public/campaigns.php">Explore campaigns</a>
          <a class="btn btn-light" href="<?= APP_URL ?>/auth/register.php">Create account</a>
          <a class="btn btn-outline-light" href="<?= APP_URL ?>/auth/register.php">Register NGO</a>
        </div>
      </div>
    </div>
  </section>

</main>
<?php static_page_end(); ?>
