<?php
declare(strict_types=1);

/**
 * Public home page (landing). Marketing sections + featured campaigns from DB.
 * Layout: public_header.php → sections below → public_footer.php
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/ui_helpers.php';
require_once __DIR__ . '/includes/landing_carousel.php';

$isLandingPage = true;
$pageTitle = 'Donate Now | Transparent Local Giving';

$landingImages = [
  'hero' => 'https://www.shutterstock.com/image-photo/volunteer-holding-cardboard-box-donated-600nw-2565173299.jpg',
  'about' => 'https://universidadeuropea.com/resources/media/images/ngo_800.width-640.jpg',
  'featuredSection' => 'https://www.2moda.com/cdn/shop/articles/2Moda-294797-beginner-donations-guide-image1.jpg?v=1720715278',
  'communityVoices' => 'https://static.vecteezy.com/system/resources/thumbnails/071/731/746/small/close-up-of-two-hands-exchanging-a-cardboard-donation-box-with-a-red-heart-symbol-representing-charity-and-giving-photo.jpeg',
  'ngo' => 'https://mcdl.mul.edu.pk/uploads/images/blogs/role-of-non-governmental-organizations-ngos-in-social-development-10.png',
  'donor' => 'https://images.unsplash.com/photo-1518398046578-8cca57782e17?auto=format&fit=crop&w=1200&q=80',
  'volunteer' => 'https://www.rosterfy.com/wp-content/uploads/2026/01/Appreciation-for-volunteers-scaled.webp',
  'records' => 'https://miro.medium.com/0*lzyNu8ztv9-aJSyn.jpg',
  'safety' => 'https://images.unsplash.com/photo-1469571486292-0bde58a3e769?auto=format&fit=crop&w=1800&q=80',
  'cta' => 'https://images.unsplash.com/photo-1529390079861-591de354faf5?auto=format&fit=crop&w=1800&q=80',
];

$stats = [
  'verified_ngos' => 42,
  'active_campaigns' => 65,
  'donations_verified' => 1840,
  'volunteers_connected' => 390,
];

$featuredCampaigns = [];

try {
  $pdo = db();

  $statQueries = [
    'verified_ngos' => "SELECT COUNT(*) FROM ngo_profiles WHERE verification_status = 'verified'",
    'active_campaigns' => "SELECT COUNT(*) FROM campaigns WHERE status IN ('approved', 'active')",
    'donations_verified' => "SELECT COUNT(*) FROM donations WHERE status = 'confirmed'",
    'volunteers_connected' => "SELECT COUNT(*) FROM volunteer_campaigns WHERE status = 'accepted'",
  ];

  foreach ($statQueries as $key => $sql) {
    $value = $pdo->query($sql)->fetchColumn();
    if ($value !== false) {
      $stats[$key] = max(0, (int)$value);
    }
  }

  $campaignSql = "SELECT c.id, c.title, c.description, c.target_amount, c.collected_amount, c.image_url, c.status,
                         cc.name AS category_name, np.ngo_name
                  FROM campaigns c
                  LEFT JOIN campaign_categories cc ON cc.id = c.category_id
                  INNER JOIN ngo_profiles np ON np.id = c.ngo_id
                  WHERE c.status IN ('pending', 'approved', 'active')
                  ORDER BY c.created_at DESC
                  LIMIT :limit";
  $stmt = $pdo->prepare($campaignSql);
  $stmt->bindValue(':limit', 6, PDO::PARAM_INT);
  $stmt->execute();
  $featuredCampaigns = $stmt->fetchAll() ?: [];
} catch (Throwable $e) {
  // Graceful fallback keeps homepage available even if queries fail.
}

$heroCampaign = $featuredCampaigns[0] ?? null;

require_once __DIR__ . '/includes/public_header.php';
?>
<main class="landing-main" id="top">

  <!-- A. Premium Hero -->
  <section class="dn-hero" aria-labelledby="hero-heading">
    <div class="dn-hero__mesh" aria-hidden="true"></div>
    <div class="dn-hero__grain" aria-hidden="true"></div>
    <div class="container dn-hero__grid">
      <div class="dn-hero__copy reveal reveal-up">
        <p class="dn-kicker">Donate with trust · Verified NGOs · Proof on record</p>
        <h1 id="hero-heading">Give with confidence. See every step of your impact.</h1>
        <p class="dn-hero__lead">Donate Now connects donors, verified NGOs, and volunteers through transparent, proof-based giving—where campaigns are trackable, donations are reviewable, and local impact stays visible.</p>
        <div class="dn-hero__actions">
          <a class="btn btn-primary" href="<?= APP_URL ?>/public/campaigns.php">Explore Campaigns</a>
          <a class="btn btn-secondary" href="<?= APP_URL ?>/auth/register.php">Create Account</a>
          <a class="btn btn-ghost" href="<?= APP_URL ?>/auth/register.php?role=volunteer">Join as NGO / Volunteer</a>
        </div>
        <div class="dn-hero__chips stagger-group" role="list" aria-label="Platform trust indicators">
          <span class="dn-chip reveal reveal-up" role="listitem">
            <strong><?= number_format((int)$stats['verified_ngos']) ?>+</strong> Verified NGOs
          </span>
          <span class="dn-chip reveal reveal-up" role="listitem">
            <strong><?= number_format((int)$stats['donations_verified']) ?>+</strong> Proof-verified donations
          </span>
          <span class="dn-chip reveal reveal-up" role="listitem">
            <strong><?= number_format((int)$stats['volunteers_connected']) ?>+</strong> Volunteers connected
          </span>
        </div>
      </div>

      <div class="dn-hero__stage reveal reveal-scale">
        <div class="dn-hero__frame">
          <img src="<?= sanitize($landingImages['hero']) ?>" alt="Volunteers organizing donated supplies for local community" width="640" height="720" loading="eager" decoding="async">
        </div>
        <div class="dn-float dn-float--proof">
          <span class="dn-float__label">Proof uploaded</span>
          <strong>Transaction #TID-48291</strong>
          <span class="dn-float__status dn-float__status--pending">Pending review</span>
        </div>
        <div class="dn-float dn-float--flow">
          <ol>
            <li class="is-done">Payment sent</li>
            <li class="is-active">Proof uploaded</li>
            <li>NGO review</li>
            <li>Confirmed</li>
          </ol>
        </div>
        <?php if ($heroCampaign): ?>
          <?php
            $hcTarget = max(0.0, (float)($heroCampaign['target_amount'] ?? 0));
            $hcCollected = max(0.0, (float)($heroCampaign['collected_amount'] ?? 0));
            $hcProgress = $hcTarget > 0 ? min(100.0, ($hcCollected / $hcTarget) * 100) : 0.0;
          ?>
          <a class="dn-float dn-float--campaign" href="<?= APP_URL ?>/public/campaign_detail.php?id=<?= (int)($heroCampaign['id'] ?? 0) ?>">
            <span class="dn-float__eyebrow">Live campaign</span>
            <strong><?= sanitize((string)($heroCampaign['title'] ?? 'Community cause')) ?></strong>
            <div class="dn-float__bar" role="presentation"><span style="width: <?= number_format($hcProgress, 2) ?>%"></span></div>
            <em><?= number_format($hcProgress, 0) ?>% funded · <?= sanitize((string)($heroCampaign['ngo_name'] ?? 'Verified NGO')) ?></em>
          </a>
        <?php else: ?>
          <div class="dn-float dn-float--campaign">
            <span class="dn-float__eyebrow">Campaign transparency</span>
            <strong>Every rupee tracked with proof</strong>
            <div class="dn-float__bar" role="presentation"><span style="width: 68%"></span></div>
            <em>Verified NGOs · Manual proof flow</em>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <a class="dn-scroll-cue" href="#impact" aria-label="Scroll to impact section">
      <span>Discover impact</span>
      <svg width="20" height="28" viewBox="0 0 20 28" fill="none" aria-hidden="true"><rect x="1" y="1" width="18" height="26" rx="9" stroke="currentColor" stroke-width="1.5"/><circle cx="10" cy="9" r="2" fill="currentColor"/></svg>
    </a>
  </section>

  <!-- B. Impact Stats Band -->
  <section id="impact" class="dn-section dn-impact-band">
    <div class="dn-impact-band__curve" aria-hidden="true"></div>
    <div class="container">
      <div class="dn-impact-band__head reveal reveal-up">
        <p class="dn-kicker">Impact at a glance</p>
        <h2>Proof-backed giving, growing every day</h2>
      </div>
      <?php
        $statSlides = [
          ['icon' => '✓', 'key' => 'verified_ngos', 'label' => 'Verified NGOs', 'hint' => 'Reviewed before full participation'],
          ['icon' => '◆', 'key' => 'active_campaigns', 'label' => 'Active Campaigns', 'hint' => 'Community needs open now'],
          ['icon' => '◎', 'key' => 'donations_verified', 'label' => 'Donations Verified', 'hint' => 'Proof confirmed by NGOs'],
          ['icon' => '✦', 'key' => 'volunteers_connected', 'label' => 'Volunteers Connected', 'hint' => 'Beyond financial support'],
        ];
        landing_carousel_open('Impact statistics', 'stats', 38);
        foreach (array_merge($statSlides, $statSlides) as $slide):
          $value = (int)($stats[$slide['key']] ?? 0);
      ?>
        <article class="dn-stat-pill">
          <span class="dn-stat-pill__icon" aria-hidden="true"><?= sanitize($slide['icon']) ?></span>
          <div class="dn-stat-pill__body">
            <h3 data-counter="<?= $value ?>">0</h3>
            <p><?= sanitize($slide['label']) ?></p>
            <small><?= sanitize($slide['hint']) ?></small>
          </div>
        </article>
      <?php endforeach; landing_carousel_close(); ?>
    </div>
  </section>

  <!-- C. About / Mission -->
  <section id="about" class="dn-section dn-about">
    <div class="container dn-about__grid">
      <figure class="dn-about__media reveal reveal-left">
        <img src="<?= sanitize($landingImages['about']) ?>" alt="NGO team collaborating on community outreach" loading="lazy" width="560" height="640">
        <figcaption class="dn-about__quote">
          <blockquote>“Trust should be built in—not assumed.”</blockquote>
        </figcaption>
      </figure>
      <div class="dn-about__copy">
        <p class="dn-kicker reveal reveal-up">About Donate Now</p>
        <h2 class="reveal reveal-up">A human platform for transparent local giving</h2>
        <p class="dn-lead reveal reveal-up">We connect donors, verified NGOs, and volunteers through proof-based workflows—so every campaign, donation, and volunteer hour leaves a clear, reviewable trail.</p>
        <ul class="dn-pillar-list stagger-group">
          <li class="reveal reveal-up">
            <span class="dn-pillar-list__num" aria-hidden="true">01</span>
            <div>
              <h3>Transparency</h3>
              <p>Donors upload payment proof; NGOs verify; admins monitor. Every step is auditable.</p>
            </div>
          </li>
          <li class="reveal reveal-up">
            <span class="dn-pillar-list__num" aria-hidden="true">02</span>
            <div>
              <h3>Accountability</h3>
              <p>Verified profiles, campaign review, and reporting keep the platform safe and fair.</p>
            </div>
          </li>
          <li class="reveal reveal-up">
            <span class="dn-pillar-list__num" aria-hidden="true">03</span>
            <div>
              <h3>Local impact</h3>
              <p>Community-led causes where donors see tangible outcomes in their own regions.</p>
            </div>
          </li>
        </ul>
        <div class="dn-about__actions reveal reveal-up">
          <a class="btn btn-primary" href="<?= APP_URL ?>/pages/about.php">Learn more about us</a>
          <a class="btn btn-ghost" href="<?= APP_URL ?>/pages/contact.php">Contact us</a>
        </div>
      </div>
    </div>
  </section>

  <!-- D. How It Works -->
  <section class="dn-section dn-process" id="how-it-works">
    <div class="container">
      <header class="dn-section-head reveal reveal-up">
        <p class="dn-kicker">How it works</p>
        <h2>Your donation journey—from browse to confirmed</h2>
        <p class="dn-section-head__sub">A clear path designed for accountability, not guesswork.</p>
      </header>
      <div class="dn-process-rail stagger-group" role="list">
        <div class="dn-process-rail__track" aria-hidden="true">
          <span class="dn-process-rail__line"></span>
          <span class="dn-process-rail__progress"></span>
        </div>
        <?php
          $journeySteps = [
            ['no' => '01', 'title' => 'Explore trusted campaigns', 'text' => 'Browse verified, active causes and choose what matters locally.'],
            ['no' => '02', 'title' => 'Pay via NGO methods', 'text' => 'Use EasyPaisa, JazzCash, or bank details defined by the NGO.'],
            ['no' => '03', 'title' => 'Upload proof + TID', 'text' => 'Submit your screenshot and transaction ID for review.'],
            ['no' => '04', 'title' => 'NGO verifies proof', 'text' => 'The receiving NGO confirms or rejects based on evidence.'],
            ['no' => '05', 'title' => 'Records stay visible', 'text' => 'Admins oversee; status and history remain traceable for everyone.'],
          ];
          foreach ($journeySteps as $step):
        ?>
          <article class="dn-process-step reveal reveal-up" role="listitem">
            <div class="dn-process-step__marker">
              <span class="dn-process-step__node" aria-hidden="true"><span class="dn-process-step__node-core"></span></span>
              <span class="dn-process-step__no"><?= sanitize($step['no']) ?></span>
            </div>
            <div class="dn-process-step__body">
              <h3><?= sanitize($step['title']) ?></h3>
              <p><?= sanitize($step['text']) ?></p>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- E. Featured Campaigns -->
  <section class="dn-section dn-campaigns" id="campaigns">
    <div class="dn-campaigns__bg" style="--section-bg-image: url('<?= sanitize($landingImages['featuredSection']) ?>');" aria-hidden="true"></div>
    <div class="dn-campaigns__overlay" aria-hidden="true"></div>
    <div class="container dn-campaigns__inner">
      <header class="dn-section-head dn-section-head--campaigns reveal reveal-up">
        <p class="dn-kicker">Featured campaigns</p>
        <h2>Active causes you can support right now</h2>
        <p class="dn-section-head__sub">Every card links to a live campaign with proof-based donation tracking.</p>
      </header>
      <?php if (!$featuredCampaigns): ?>
        <div class="dn-empty reveal reveal-scale">
          <div class="dn-empty__icon" aria-hidden="true">◇</div>
          <h3>No featured campaigns yet</h3>
          <p>Campaigns will appear here after NGO submission and review.</p>
          <a class="btn btn-primary" href="<?= APP_URL ?>/public/campaigns.php">Browse Campaigns</a>
        </div>
      <?php else: ?>
        <?php
          landing_carousel_open('Featured campaigns', 'campaigns', 36);
          foreach (array_merge($featuredCampaigns, $featuredCampaigns) as $campaign) {
            landing_render_featured_campaign_card($campaign);
          }
          landing_carousel_close();
        ?>
        <p class="dn-campaigns__more reveal reveal-up">
          <a class="dn-link-arrow" href="<?= APP_URL ?>/public/campaigns.php">View all campaigns</a>
        </p>
      <?php endif; ?>
    </div>
  </section>

  <!-- F. Transparency / Proof Flow -->
  <section class="dn-section dn-proof-flow" id="safety">
    <div class="container">
      <header class="dn-section-head reveal reveal-up">
        <p class="dn-kicker">Transparency model</p>
        <h2>Every donation leaves a clear trail</h2>
        <p class="dn-section-head__sub">Manual proof keeps NGOs in control while donors stay informed.</p>
      </header>
      <div class="dn-proof-panels stagger-group" role="list">
        <?php
          $flowSteps = [
            ['icon' => '→', 'title' => 'Payment sent', 'text' => 'Donor completes payment through NGO-defined channels.'],
            ['icon' => '↑', 'title' => 'Proof uploaded', 'text' => 'Screenshot and transaction ID submitted for review.'],
            ['icon' => '◉', 'title' => 'NGO review', 'text' => 'NGO confirms or rejects based on transaction evidence.'],
            ['icon' => '✓', 'title' => 'Confirmed', 'text' => 'Status updates; records remain permanently traceable.'],
          ];
          foreach ($flowSteps as $index => $step):
        ?>
          <article class="dn-proof-panel reveal reveal-up" role="listitem">
            <span class="dn-proof-panel__icon" aria-hidden="true"><?= sanitize($step['icon']) ?></span>
            <h3><?= sanitize($step['title']) ?></h3>
            <p><?= sanitize($step['text']) ?></p>
            <?php if ($index < count($flowSteps) - 1): ?>
              <span class="dn-proof-panel__connector" aria-hidden="true"></span>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- G. Roles -->
  <section class="dn-section dn-roles" id="ngos">
    <div class="container">
      <header class="dn-section-head reveal reveal-up">
        <p class="dn-kicker">Who it's for</p>
        <h2>One platform, three ways to make a difference</h2>
      </header>
      <div class="dn-role-cards stagger-group">
        <article class="dn-role-card reveal reveal-up">
          <div class="dn-role-card__media">
            <img src="<?= sanitize($landingImages['donor']) ?>" alt="Donor reviewing campaign progress" loading="lazy">
          </div>
          <div class="dn-role-card__body">
            <span class="dn-role-card__tag">Donor</span>
            <h3>Give with visible proof and records</h3>
            <p>Explore causes, pay through NGO methods, upload proof, and track each donation from pending to confirmed.</p>
            <ul>
              <li>Browse verified campaigns</li>
              <li>Upload screenshot + TID</li>
              <li>Follow status in your history</li>
            </ul>
            <a class="btn btn-primary btn-sm" href="<?= APP_URL ?>/auth/register.php">Create donor account</a>
          </div>
        </article>
        <article class="dn-role-card dn-role-card--featured reveal reveal-up">
          <div class="dn-role-card__media">
            <img src="<?= sanitize($landingImages['ngo']) ?>" alt="NGO team managing campaigns" loading="lazy">
          </div>
          <div class="dn-role-card__body">
            <span class="dn-role-card__tag">Verified NGO</span>
            <h3>Organize transparent, proof-based giving</h3>
            <p>Create campaigns, manage payment methods, review proofs, and coordinate volunteers—all in one place.</p>
            <ul>
              <li>Campaign & payment method management</li>
              <li>Verify or reject donation proofs</li>
              <li>Complete donation & volunteer records</li>
            </ul>
            <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>/auth/register.php">Register your NGO</a>
          </div>
        </article>
        <article class="dn-role-card reveal reveal-up">
          <div class="dn-role-card__media">
            <img src="<?= sanitize($landingImages['volunteer']) ?>" alt="Volunteers supporting a local cause" loading="lazy">
          </div>
          <div class="dn-role-card__body">
            <span class="dn-role-card__tag">Volunteer</span>
            <h3>Show up beyond the donation</h3>
            <p>Join campaigns with your time and skills—request to participate and track responses clearly.</p>
            <ul>
              <li>Browse campaign opportunities</li>
              <li>Submit join requests</li>
              <li>Track acceptance status</li>
            </ul>
            <a class="btn btn-ghost btn-sm" href="<?= APP_URL ?>/auth/register.php?role=volunteer">Join as volunteer</a>
          </div>
        </article>
      </div>
    </div>
  </section>

  <!-- H. Safety / Trust -->
  <section class="dn-section dn-trust-panel" id="volunteer">
    <div class="dn-trust-panel__bg" style="--section-bg-image: url('<?= sanitize($landingImages['safety']) ?>');" aria-hidden="true"></div>
    <div class="dn-trust-panel__overlay" aria-hidden="true"></div>
    <div class="container dn-trust-panel__inner">
      <div class="dn-trust-panel__intro reveal reveal-up">
        <p class="dn-kicker dn-kicker--light">Safety &amp; trust</p>
        <h2>Protection built into every action</h2>
        <p>Verified NGOs, admin moderation, safety reports, and permanent donation records keep the ecosystem accountable.</p>
      </div>
      <?php
        $safetySlides = [
          ['title' => 'Report suspicious activity', 'text' => 'Flag fake NGOs, campaigns, payments, fraud, or abuse.'],
          ['title' => 'Admin moderation', 'text' => 'Admins review reports and apply evidence-based controls.'],
          ['title' => 'Account protection', 'text' => 'Block, suspend, or hold accounts when policy requires it.'],
          ['title' => 'Transaction visibility', 'text' => 'Sender, receiver, campaign, status, and proof stay traceable.'],
        ];
        landing_carousel_open('Safety features', 'safety', 32);
        foreach (array_merge($safetySlides, $safetySlides) as $slide):
      ?>
        <article class="dn-trust-card">
          <h3><?= sanitize($slide['title']) ?></h3>
          <p><?= sanitize($slide['text']) ?></p>
        </article>
      <?php endforeach; landing_carousel_close(); ?>
    </div>
  </section>

  <!-- Records ribbon -->
  <section class="dn-section dn-records">
    <div class="container">
      <header class="dn-section-head reveal reveal-up">
        <p class="dn-kicker">Records &amp; history</p>
        <h2>Every role sees a clear timeline</h2>
      </header>
      <div class="dn-records-strip stagger-group">
        <article class="dn-record-tile reveal reveal-left">
          <span class="dn-record-tile__label">Donor</span>
          <h3>Donation history</h3>
          <p>Campaigns, statuses, and proof details in one organized view.</p>
        </article>
        <article class="dn-record-tile reveal reveal-up">
          <span class="dn-record-tile__label">NGO</span>
          <h3>Campaign records</h3>
          <p>Received donations, payment methods, and volunteer logs.</p>
        </article>
        <article class="dn-record-tile reveal reveal-right">
          <span class="dn-record-tile__label">Volunteer</span>
          <h3>Participation journey</h3>
          <p>Joined campaigns, request outcomes, and field contributions.</p>
        </article>
        <article class="dn-record-tile reveal reveal-up">
          <span class="dn-record-tile__label">Admin</span>
          <h3>Oversight history</h3>
          <p>Users, reports, transactions, and moderation actions.</p>
        </article>
      </div>
    </div>
  </section>

  <!-- I. Community Voices -->
  <section id="community-voices" class="dn-section dn-voices">
    <div class="dn-voices__bg" style="--section-bg-image: url('<?= sanitize($landingImages['communityVoices']) ?>');" aria-hidden="true"></div>
    <div class="dn-voices__overlay" aria-hidden="true"></div>
    <div class="container dn-voices__inner">
      <header class="dn-section-head dn-section-head--light reveal reveal-up">
        <p class="dn-kicker">Community voices</p>
        <h2>What donors, NGOs, and volunteers experience</h2>
        <p class="dn-section-head__sub">Proof-based verification changes how people give, organize, and show up.</p>
      </header>
      <?php
        $voiceSlides = [
          [
            'role' => 'Donor',
            'quote' => 'I upload proof once, see status move to confirmed, and keep everything in my history without chasing receipts.',
            'avatar' => 'AR',
            'avatarClass' => '',
            'name' => 'Aisha Rahman',
            'location' => 'Lahore',
          ],
          [
            'role' => 'Verified NGO',
            'quote' => 'One place for campaigns, payment methods, and proofs. Donors see we verify every entry—it strengthens trust.',
            'avatar' => 'CC',
            'avatarClass' => ' dn-avatar--olive',
            'name' => 'Care Circle Foundation',
            'location' => 'Program lead',
          ],
          [
            'role' => 'Volunteer',
            'quote' => 'I browse campaigns, send a join request, and get a clear accept or decline. Donations and volunteering stay connected.',
            'avatar' => 'UT',
            'avatarClass' => '',
            'name' => 'Usman Tariq',
            'location' => 'Weekend field volunteer',
          ],
        ];
        landing_carousel_open('Community voices', 'voices', 30);
        foreach (array_merge($voiceSlides, $voiceSlides) as $slide):
      ?>
        <article class="dn-voice-card">
          <span class="dn-voice-card__role"><?= sanitize($slide['role']) ?></span>
          <blockquote><p><?= sanitize($slide['quote']) ?></p></blockquote>
          <footer>
            <span class="dn-avatar<?= $slide['avatarClass'] ?>" aria-hidden="true"><?= sanitize($slide['avatar']) ?></span>
            <div>
              <strong><?= sanitize($slide['name']) ?></strong>
              <span><?= sanitize($slide['location']) ?></span>
            </div>
          </footer>
        </article>
      <?php endforeach; landing_carousel_close(); ?>
      <p class="dn-voices__note reveal reveal-up">Illustrative perspectives aligned with how Donate Now is designed to work.</p>
    </div>
  </section>

  <!-- J. FAQ -->
  <section class="dn-section dn-faq" id="faq">
    <div class="container dn-faq__wrap">
      <header class="dn-section-head reveal reveal-up">
        <p class="dn-kicker">FAQ</p>
        <h2>Everything you need before you give</h2>
      </header>
      <div class="dn-faq-list" role="list">
        <?php
          $faqs = [
            ['q' => 'How does Donate Now verify donations?', 'a' => 'Donors upload payment screenshot and TID, then NGOs verify each submission before final confirmation.'],
            ['q' => 'Who confirms a donation?', 'a' => 'The receiving NGO confirms or rejects donation proof based on transaction evidence.'],
            ['q' => 'Can admin see transactions?', 'a' => 'Yes. Admin can monitor donation records, statuses, related accounts, and report activity.'],
            ['q' => 'Can NGOs add their own payment methods?', 'a' => 'Yes. NGOs manage their own methods such as EasyPaisa, JazzCash, and bank details.'],
            ['q' => 'Can I report a fake NGO or fake payment?', 'a' => 'Yes. Users can submit reports for fraud, abuse, fake NGO, fake campaign, and suspicious payments.'],
            ['q' => 'Can volunteers join campaigns?', 'a' => 'Yes. Volunteers can browse campaigns, request to join, and NGOs can accept or reject requests.'],
            ['q' => 'Is payment gateway used?', 'a' => 'No external payment gateway is used currently. Payment methods are NGO-defined and proof is uploaded manually.'],
          ];
        ?>
        <?php foreach ($faqs as $index => $item): ?>
          <article class="dn-faq-item reveal reveal-up" role="listitem">
            <h3>
              <button type="button" class="dn-faq-trigger" data-faq-trigger="faq-<?= (int)$index ?>" aria-expanded="false" aria-controls="faq-<?= (int)$index ?>">
                <span><?= sanitize($item['q']) ?></span>
                <svg class="dn-faq-chevron" width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M5 8l5 5 5-5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
              </button>
            </h3>
            <div class="dn-faq-panel" id="faq-<?= (int)$index ?>" hidden>
              <p><?= sanitize($item['a']) ?></p>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- K. Final CTA -->
  <section class="dn-section dn-final-cta">
    <div class="dn-final-cta__bg" style="--section-bg-image: url('<?= sanitize($landingImages['cta']) ?>');" aria-hidden="true"></div>
    <div class="dn-final-cta__overlay" aria-hidden="true"></div>
    <div class="container dn-final-cta__box reveal reveal-scale">
      <p class="dn-kicker dn-kicker--light">Start today</p>
      <h2>Make every donation visible, verified, and meaningful</h2>
      <p>Join as a donor, NGO, or volunteer—and help build a more transparent culture of local giving.</p>
      <div class="dn-final-cta__actions">
        <a class="btn btn-primary" href="<?= APP_URL ?>/public/campaigns.php">Explore Campaigns</a>
        <a class="btn btn-light" href="<?= APP_URL ?>/auth/register.php">Create Account</a>
        <a class="btn btn-outline-light" href="<?= APP_URL ?>/auth/register.php">Register NGO</a>
      </div>
    </div>
  </section>

</main>
<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
