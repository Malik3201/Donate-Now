<?php
declare(strict_types=1);

/**
 * =============================================================================
 * DONATE NOW — CODE GUIDE (documentation file, not loaded at runtime)
 * =============================================================================
 *
 * Open this file when you need to understand how the website is organized.
 *
 * STACK
 * -----
 * PHP 8+ (no framework), MySQL, plain HTML/CSS/JS on Apache/XAMPP.
 * Config from `.env` via config/app.php → env_value().
 *
 * REQUEST FLOW
 * ------------
 * 1. Public marketing site: index.php, pages/*.php → public_header + public_footer
 * 2. Auth: auth/login.php, auth/register.php → auth_header + auth_footer
 * 3. Logged-in app: */dashboard.php, profile/*, etc. → auth_check.php + dashboard_layout_*
 *
 * DIRECTORY MAP
 * -------------
 * /config/          app.php (.env), database.php (PDO), brevo.php, imagekit.php
 * /includes/        Shared PHP: auth, mail, UI, uploads, notifications, reports
 * /auth/            Login, register, logout, password reset
 * /admin/           Platform admin (users, campaigns, donations, reports, analytics)
 * /ngo/             NGO dashboards (campaigns, donations, volunteers, payment methods)
 * /donor/           Donor dashboards (browse, donate, my donations)
 * /volunteer/       Volunteer dashboards (browse, join campaigns)
 * /profile/         Account profile, photo, password (all roles)
 * /public/          Public campaign listing + detail (no login required)
 * /pages/           Static content: about, contact, privacy, terms
 * /reports/         User-submitted safety/fraud reports
 * /notifications/   In-app notification list
 * /database/        schema.sql, migrations/, run_migration.php, seed.php (dev)
 * /assets/css|js/   Styles and scripts (landing, dashboard, auth, mobile-app)
 *
 * KEY INCLUDES (load order matters)
 * ---------------------------------
 * functions.php       sanitize(), redirect(), session user, CSRF, activity log
 * auth_check.php      Requires login + active account → sets $authUser, $pdo
 * role_check.php      require_role(['admin'|'ngo'|...]) on protected pages
 * ui_helpers.php      Logo, favicon, asset_url(), placeholders, mobile CSS tag
 * mail_helper.php     Brevo API or SMTP emails + HTML templates
 * upload_helper.php   ImageKit uploads (campaign images, donation proof, profile)
 * location_helpers.php  Leaflet map picker/display for NGO/campaign locations
 * static_pages.php    Wrapper for About/Contact/Privacy/Terms pages
 * landing_carousel.php  Home page horizontal carousels (mobile only)
 * dashboard_sidebar.php  Role-based nav links
 *
 * ROLES & DATA
 * ------------
 * users.role: admin | ngo | donor | volunteer
 * Donation flow: donor pays offline → uploads proof → NGO confirms/rejects → admin oversight
 * campaigns.status: pending → approved/active → completed; NGO verification on ngo_profiles
 *
 * EMAIL
 * -----
 * Brevo: set BREVO_API_KEY (API) OR BREVO_SMTP_* (SMTP) in .env. See config/brevo.php.
 *
 * FRONTEND
 * --------
 * landing.css + landing.js     Home page animations, carousels, nav hide on scroll
 * dashboard.css              Logged-in UI (warm cream/terracotta theme)
 * mobile-app.css             Touch layouts ≤992px only (does not change desktop)
 * static-pages.css           About, Contact, Privacy, Terms heroes
 *
 * SETUP
 * -----
 * 1. Import database/schema.sql (or run database/run_migration.php)
 * 2. Copy .env.example into .env (DB_*, Brevo, ImageKit optional). APP_URL can be empty for auto-detect.
 * 3. Point Apache vhost/document root to this folder
 *
 * REMOVED / UNUSED (cleanup)
 * --------------------------
 * pages/how-it-works.php, pages/faqs.php — duplicate of home sections; removed.
 * FAQ and How It Works live on index.php (#faq, how-it-works section).
 */

// Intentionally empty — this file exists only as in-repo documentation.
