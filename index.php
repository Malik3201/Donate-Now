<?php
declare(strict_types=1);

/**
 * Public home page (landing). Marketing sections + featured campaigns from DB.
 * Layout: public_header.php → sections below → public_footer.php
 * See includes/CODE_GUIDE.php for full site structure.
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/ui_helpers.php';
require_once __DIR__ . '/includes/landing_carousel.php';

$isLandingPage = true;
$pageTitle = 'Donate Now | Transparent Local Giving';

$landingImages = [
  'hero' => 'https://www.shutterstock.com/image-photo/volunteer-holding-cardboard-box-donated-600nw-2565173299.jpg',
  'ourStory' => 'https://img.magnific.com/free-photo/donate-sign-charity-campaign_53876-127165.jpg?semt=ais_hybrid&w=740&q=80',
  'featuredSection' => 'https://www.2moda.com/cdn/shop/articles/2Moda-294797-beginner-donations-guide-image1.jpg?v=1720715278',
  'communityVoices' => 'https://static.vecteezy.com/system/resources/thumbnails/071/731/746/small/close-up-of-two-hands-exchanging-a-cardboard-donation-box-with-a-red-heart-symbol-representing-charity-and-giving-photo.jpeg',
  'ngo' => 'https://images.unsplash.com/photo-1559027615-cd4628902d4a?auto=format&fit=crop&w=1200&q=80',
  'donor' => 'https://images.unsplash.com/photo-1518398046578-8cca57782e17?auto=format&fit=crop&w=1200&q=80',
  'volunteer' => 'https://images.unsplash.com/photo-1529390079861-591de354faf5?auto=format&fit=crop&w=1200&q=80',
  'cta' => 'https://images.unsplash.com/photo-1529390079861-591de354faf5?auto=format&fit=crop&w=1800&q=80',
];

$stats = [
  'verified_ngos' => 42,
  'active_campaigns' => 65,
  'donations_verified' => 1840,
  'volunteers_connected' => 390,
];

$featuredCampaigns = [];

// Load live stats and featured campaigns; fall back to defaults above if DB is unavailable
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

require_once __DIR__ . '/includes/public_header.php';
?>
<main class="landing-main">
  <!-- Hero: primary CTAs and trust metrics -->
  <section class="hero section-dark" style="--hero-image: url('<?= sanitize($landingImages['hero']) ?>');">
    <div class="hero-overlay"></div>
    <div class="hero-grain"></div>
    <div class="container hero-inner">
      <div class="hero-content hero-centered reveal reveal-up">
        <span class="hero-eyebrow">Transparent local giving, built for real impact</span>
        <h1>Give with confidence. Create real local impact.</h1>
        <p>Donate Now connects donors, verified NGOs, and volunteers through a transparent proof-based flow where every donation is trackable, reviewable, and meaningful.</p>
        <div class="hero-cta">
          <a class="btn btn-primary" href="<?= APP_URL ?>/public/campaigns.php">Explore Campaigns</a>
          <a class="btn btn-light" href="<?= APP_URL ?>/auth/register.php">Create Account</a>
          <a class="btn btn-outline-light" href="<?= APP_URL ?>/auth/register.php">Register NGO</a>
        </div>
        <div class="hero-proof-strip hero-proof-strip-centered stagger-group">
          <article class="proof-item reveal reveal-up">
            <strong><?= number_format((int)$stats['verified_ngos']) ?>+</strong>
            <span>Verified NGOs onboarded</span>
          </article>
          <article class="proof-item reveal reveal-up">
            <strong><?= number_format((int)$stats['donations_verified']) ?>+</strong>
            <span>Proof-verified donations tracked</span>
          </article>
          <article class="proof-item reveal reveal-up">
            <strong><?= number_format((int)$stats['volunteers_connected']) ?>+</strong>
            <span>Volunteers connected to campaigns</span>
          </article>
        </div>
      </div>
    </div>
  </section>

  <section id="impact" class="section impact-snapshot">
    <div class="container split-intro">
      <div class="section-copy reveal reveal-left">
        <span class="section-kicker">Impact Snapshot</span>
        <h2>One platform. Four clear signals of trust.</h2>
        <p>Built for people who want proof, not promises. These numbers reflect a growing local ecosystem where giving, verification, and accountability happen together.</p>
      </div>
      <?php
        $statSlides = [
          ['icon' => 'N', 'key' => 'verified_ngos', 'label' => 'Verified NGOs', 'hint' => 'Reviewed before full participation'],
          ['icon' => 'C', 'key' => 'active_campaigns', 'label' => 'Active Campaigns', 'hint' => 'Community needs currently open'],
          ['icon' => 'D', 'key' => 'donations_verified', 'label' => 'Donations Verified', 'hint' => 'Proof confirmed by NGOs'],
          ['icon' => 'V', 'key' => 'volunteers_connected', 'label' => 'Volunteers Connected', 'hint' => 'People supporting beyond money'],
        ];
        landing_carousel_open('Impact statistics', 'stats', 38);
        foreach (array_merge($statSlides, $statSlides) as $slide):
          $value = (int)($stats[$slide['key']] ?? 0);
      ?>
        <article class="stat-card">
          <span class="stat-icon"><?= sanitize($slide['icon']) ?></span>
          <h3 data-counter="<?= $value ?>">0</h3>
          <p><?= sanitize($slide['label']) ?></p>
          <small><?= sanitize($slide['hint']) ?></small>
        </article>
      <?php endforeach; landing_carousel_close(); ?>
    </div>
  </section>

  <section id="about" class="section about-us-section">
    <div class="container">
      <div class="about-us-intro reveal reveal-up">
        <span class="section-kicker">About Us</span>
        <h2>Donate Now exists because trust should be built in, not assumed.</h2>
        <p>We connect donors, verified NGOs, and volunteers through transparent, proof-based giving built for real local impact.</p>
      </div>
      <div class="about-us-grid stagger-group">
        <article class="about-us-card reveal reveal-up">
          <h3>Transparency</h3>
          <p>Donors upload payment proof; NGOs verify; admins monitor. Every step leaves a clear audit trail.</p>
        </article>
        <article class="about-us-card reveal reveal-up">
          <h3>Accountability</h3>
          <p>Verified NGO profiles, campaign review, and reporting tools keep the platform safe and fair.</p>
        </article>
        <article class="about-us-card reveal reveal-up">
          <h3>Local impact</h3>
          <p>Community-led causes where donors can see tangible outcomes in their own regions.</p>
        </article>
      </div>
      <p class="about-us-actions reveal reveal-up">
        <a class="btn btn-primary" href="<?= APP_URL ?>/pages/about.php">Learn more about us</a>
        <a class="btn btn-olive" href="<?= APP_URL ?>/pages/contact.php">Contact us</a>
      </p>
    </div>
  </section>

  <section class="section how-it-works">
    <div class="container">
      <div class="section-heading reveal reveal-up">
        <span class="section-kicker">How It Works</span>
        <h2>A clear donation path designed for accountability.</h2>
      </div>
      <div class="timeline">
        <article class="timeline-step reveal reveal-left">
          <span class="step-no">01</span>
          <h3>Donor explores trusted campaign</h3>
          <p>Browse verified, active campaigns and choose a local cause that matters.</p>
        </article>
        <article class="timeline-step reveal reveal-right">
          <span class="step-no">02</span>
          <h3>NGO shares payment methods</h3>
          <p>Select NGO-defined EasyPaisa, JazzCash, or bank method details.</p>
        </article>
        <article class="timeline-step reveal reveal-left">
          <span class="step-no">03</span>
          <h3>Donor uploads screenshot + TID</h3>
          <p>Submit proof details after payment for verification review.</p>
        </article>
        <article class="timeline-step reveal reveal-right">
          <span class="step-no">04</span>
          <h3>NGO verifies donation proof</h3>
          <p>The NGO confirms or rejects based on transaction evidence.</p>
        </article>
        <article class="timeline-step reveal reveal-left">
          <span class="step-no">05</span>
          <h3>Admin monitors records</h3>
          <p>Oversight ensures traceability across users, campaigns, and reports.</p>
        </article>
      </div>
    </div>
  </section>

  <section class="section featured-campaigns section-photo-bg section-photo-bg--campaigns" id="campaigns" style="--section-bg-image: url('<?= sanitize($landingImages['featuredSection']) ?>');">
    <div class="section-photo-overlay section-photo-overlay--campaigns" aria-hidden="true"></div>
    <div class="container featured-campaigns-inner">
      <div class="section-heading reveal reveal-up">
        <span class="section-kicker">Featured Campaigns</span>
        <h2>Active causes you can support right now.</h2>
      </div>
      <?php if (!$featuredCampaigns): ?>
        <div class="campaign-empty reveal reveal-scale">
          <div class="empty-icon" aria-hidden="true">o</div>
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
      <?php endif; ?>
    </div>
  </section>
  <section class="section transparency-model section-dark-cocoa" id="safety">
    <div class="container">
      <div class="section-heading reveal reveal-up">
        <span class="section-kicker">Transparency Model</span>
        <h2>Every donation leaves a clear trail.</h2>
      </div>
      <?php
        $flowSlides = [
          ['title' => 'Donor', 'text' => 'Chooses a campaign and payment method.'],
          ['title' => 'Payment Proof', 'text' => 'Screenshot + TID are uploaded safely.'],
          ['title' => 'NGO Verification', 'text' => 'Proof is confirmed or rejected by NGO.'],
          ['title' => 'Admin Oversight', 'text' => 'Moderation and visibility remain active.'],
          ['title' => 'Permanent Record', 'text' => 'Status and trace stay in structured history.'],
        ];
        landing_carousel_open('Transparency flow', 'flow', 34);
        foreach (array_merge($flowSlides, $flowSlides) as $slide):
      ?>
        <article class="flow-card"><strong><?= sanitize($slide['title']) ?></strong><p><?= sanitize($slide['text']) ?></p></article>
      <?php endforeach; landing_carousel_close(); ?>
    </div>
  </section>

  <section class="section ngo-section" id="ngos">
    <div class="container dual-layout">
      <figure class="media-side reveal reveal-left">
        <img src="<?= sanitize($landingImages['ngo']) ?>" alt="NGO team organizing donation campaign">
      </figure>
      <div class="content-side">
        <span class="section-kicker">For NGOs</span>
        <h2 class="reveal reveal-up">Built for NGOs that want organized, transparent giving.</h2>
        <div class="feature-list stagger-group">
          <article class="feature-item reveal reveal-up">Create and manage impact campaigns.</article>
          <article class="feature-item reveal reveal-up">Add EasyPaisa, JazzCash, and bank methods.</article>
          <article class="feature-item reveal reveal-up">Review screenshot and TID proof uploads.</article>
          <article class="feature-item reveal reveal-up">Verify, reject, and track donations with clarity.</article>
          <article class="feature-item reveal reveal-up">Manage volunteers supporting your campaigns.</article>
          <article class="feature-item reveal reveal-up">Keep complete donation and action records.</article>
        </div>
        <a class="btn btn-olive reveal reveal-scale" href="<?= APP_URL ?>/auth/register.php">Register Your NGO</a>
      </div>
    </div>
  </section>

  <section class="section donor-experience">
    <div class="container dual-layout reverse">
      <div class="content-side">
        <span class="section-kicker">For Donors</span>
        <h2 class="reveal reveal-up">Donate with confidence through visible status and records.</h2>
        <p class="reveal reveal-up">Explore causes, donate through NGO-defined methods, upload proof, and follow each donation from pending to confirmed while keeping your giving history organized.</p>
        <div class="mini-cards stagger-group">
          <article class="mini-card reveal reveal-right"><strong>Donation Pending</strong><span>02</span></article>
          <article class="mini-card reveal reveal-right"><strong>Donation Confirmed</strong><span>18</span></article>
          <article class="mini-card reveal reveal-right"><strong>Total Donated</strong><span>PKR 145,000</span></article>
          <article class="mini-card reveal reveal-right"><strong>Campaigns Supported</strong><span>09</span></article>
        </div>
      </div>
      <figure class="media-side reveal reveal-right">
        <img src="<?= sanitize($landingImages['donor']) ?>" alt="Donor reviewing campaign progress on phone">
      </figure>
    </div>
  </section>

  <section class="section volunteer-section" id="volunteer">
    <div class="container dual-layout">
      <figure class="media-side reveal reveal-left">
        <img src="<?= sanitize($landingImages['volunteer']) ?>" alt="Volunteers helping community members together">
      </figure>
      <div class="content-side">
        <span class="section-kicker">For Volunteers</span>
        <h2 class="reveal reveal-up">Support local causes with your time, skills, and presence.</h2>
        <ul class="soft-list">
          <li class="reveal reveal-up">Browse campaign opportunities aligned with your interests.</li>
          <li class="reveal reveal-up">Submit requests to join and track response status.</li>
          <li class="reveal reveal-up">Share your availability and field contribution skills.</li>
          <li class="reveal reveal-up">Participate in initiatives beyond financial donations.</li>
        </ul>
        <a class="btn btn-primary reveal reveal-scale" href="<?= APP_URL ?>/auth/register.php?role=volunteer">Join as Volunteer</a>
      </div>
    </div>
  </section>

  <section class="section safety-reporting section-dark">
    <div class="container">
      <div class="section-heading reveal reveal-up">
        <span class="section-kicker">Safety & Reporting</span>
        <h2>Safety is built into every action.</h2>
      </div>
      <div class="safety-core reveal reveal-scale">
        <p>Every report, verification, and moderation action stays visible in a clear trust trail.</p>
      </div>
      <?php
        $safetySlides = [
          ['title' => 'Report suspicious activity', 'text' => 'Flag fake NGO, fake campaign, fake payment, fraud, or abuse.'],
          ['title' => 'Admin moderation', 'text' => 'Admins can review reports and apply controls with evidence.'],
          ['title' => 'Account protection', 'text' => 'Block, suspend, or temporarily hold users when needed.'],
          ['title' => 'Transaction visibility', 'text' => 'Sender, receiver, campaign, status, and proof remain traceable.'],
        ];
        landing_carousel_open('Safety features', 'safety', 32);
        foreach (array_merge($safetySlides, $safetySlides) as $slide):
      ?>
        <article class="safety-card"><h3><?= sanitize($slide['title']) ?></h3><p><?= sanitize($slide['text']) ?></p></article>
      <?php endforeach; landing_carousel_close(); ?>
    </div>
  </section>

  <section class="section records-history">
    <div class="container">
      <div class="section-heading reveal reveal-up">
        <span class="section-kicker">Records & History</span>
        <h2>Your giving history stays organized.</h2>
      </div>
      <div class="record-rows stagger-group">
        <article class="record-card reveal reveal-left"><span class="record-chip">Donor view</span><h3>Donor Record</h3><p>Donation history, campaign names, statuses, and proof details in one timeline.</p></article>
        <article class="record-card reveal reveal-right"><span class="record-chip">NGO view</span><h3>NGO Record</h3><p>Campaigns, received donations, payment methods, and volunteer participation logs.</p></article>
        <article class="record-card reveal reveal-left"><span class="record-chip">Volunteer view</span><h3>Volunteer Record</h3><p>Joined campaigns, request outcomes, and participation journey by campaign.</p></article>
        <article class="record-card reveal reveal-right"><span class="record-chip">Admin view</span><h3>Admin Record</h3><p>Users, reports, transactions, moderation actions, and oversight history.</p></article>
      </div>
    </div>
  </section>

  <section id="community-voices" class="section testimonials section-photo-bg section-photo-bg--dark testimonials-voices" style="--section-bg-image: url('<?= sanitize($landingImages['communityVoices']) ?>');">
    <div class="section-photo-overlay section-photo-overlay--voices" aria-hidden="true"></div>
    <div class="section-photo-grain section-photo-grain--subtle" aria-hidden="true"></div>
    <div class="container testimonials-voices-inner">
      <header class="voices-intro reveal reveal-up">
        <span class="section-kicker section-kicker--on-dark">Community Voices</span>
        <h2>What donors, NGOs, and volunteers say about transparent giving.</h2>
        <p class="voices-lead">These voices reflect how proof-based verification and clear records change the way people give, organize, and show up for local causes.</p>
      </header>
      <?php
        $voiceSlides = [
          [
            'role' => 'Donor',
            'quote' => 'I used to hesitate before sending support. Here I upload proof once, see the status move to confirmed, and keep everything in my history without chasing receipts.',
            'avatar' => 'AR',
            'avatarClass' => '',
            'name' => 'Aisha Rahman',
            'location' => 'Lahore',
          ],
          [
            'role' => 'Verified NGO',
            'quote' => 'Our team finally has one place for campaigns, payment methods, and donation proofs. Donors see that we verify every entry—it strengthens trust without extra admin chaos.',
            'avatar' => 'CC',
            'avatarClass' => ' voice-avatar--olive',
            'name' => 'Care Circle Foundation',
            'location' => 'Program lead',
          ],
          [
            'role' => 'Volunteer',
            'quote' => 'I browse active campaigns, send a join request, and get a clear accept or decline. Knowing the same platform tracks donations and volunteering keeps me engaged.',
            'avatar' => 'UT',
            'avatarClass' => '',
            'name' => 'Usman Tariq',
            'location' => 'Weekend field volunteer',
          ],
        ];
        landing_carousel_open('Community voices', 'voices', 30);
        foreach (array_merge($voiceSlides, $voiceSlides) as $slide):
      ?>
        <article class="voice-card">
          <span class="voice-role"><?= sanitize($slide['role']) ?></span>
          <blockquote>
            <p><?= sanitize($slide['quote']) ?></p>
          </blockquote>
          <footer class="voice-meta">
            <span class="voice-avatar<?= $slide['avatarClass'] ?>" aria-hidden="true"><?= sanitize($slide['avatar']) ?></span>
            <div>
              <strong><?= sanitize($slide['name']) ?></strong>
              <span class="voice-location"><?= sanitize($slide['location']) ?></span>
            </div>
          </footer>
        </article>
      <?php endforeach; landing_carousel_close(); ?>
      <p class="voices-note reveal reveal-up">Illustrative community perspectives aligned with how Donate Now is designed to work.</p>
    </div>
  </section>

  <section class="section faq-section" id="faq">
    <div class="container faq-wrap">
      <div class="section-heading reveal reveal-up">
        <span class="section-kicker">FAQ</span>
        <h2>Everything you need to know before you give.</h2>
      </div>
      <div class="faq-list">
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
          <article class="faq-item reveal reveal-up">
            <h3>
              <button type="button" class="faq-trigger" data-faq-trigger="faq-<?= (int)$index ?>" aria-expanded="false" aria-controls="faq-<?= (int)$index ?>">
                <?= sanitize($item['q']) ?>
                <span aria-hidden="true">+</span>
              </button>
            </h3>
            <div class="faq-panel" id="faq-<?= (int)$index ?>" hidden>
              <p><?= sanitize($item['a']) ?></p>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="section final-cta section-dark" style="--hero-image: url('<?= sanitize($landingImages['cta']) ?>');">
    <div class="hero-overlay"></div>
    <div class="container cta-box reveal reveal-scale">
      <h2>Make every donation visible, verified, and meaningful.</h2>
      <p>Join Donate Now as a donor, NGO, or volunteer and help build a more transparent culture of local giving.</p>
      <div class="hero-cta">
        <a class="btn btn-primary" href="<?= APP_URL ?>/public/campaigns.php">Explore Campaigns</a>
        <a class="btn btn-light" href="<?= APP_URL ?>/auth/register.php">Create Account</a>
        <a class="btn btn-outline-light" href="<?= APP_URL ?>/auth/register.php">Register NGO</a>
      </div>
    </div>
  </section>
</main>
<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
