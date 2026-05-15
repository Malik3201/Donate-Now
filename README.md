# Donate Now

Transparent, proof-based local giving platform (PHP + MySQL).

## Quick start

1. Import `database/schema.sql` or run `php database/run_migration.php` via XAMPP PHP.
2. Copy environment variables into `.env` (see `.env.example` if present, or use `APP_URL`, `DB_*`, optional Brevo/ImageKit keys).
3. Open `http://localhost/Donate-Now/` (match `APP_URL` in `.env`).

## Understanding the code

**Read `includes/CODE_GUIDE.php` first** — directory map, request flow, roles, and key includes.

Additional comments live in:

- `config/app.php`, `config/database.php`
- `includes/functions.php`, `auth_check.php`, `role_check.php`
- `index.php` (home page sections)

## Roles

| Role       | Folder        |
|-----------|---------------|
| Admin     | `/admin/`     |
| NGO       | `/ngo/`       |
| Donor     | `/donor/`     |
| Volunteer | `/volunteer/` |

## Public pages

- Home: `index.php`
- Static: `pages/about.php`, `contact.php`, `privacy-policy.php`, `terms.php`
- Campaigns (guest): `public/campaigns.php`

FAQ and “How it works” are sections on the home page (`#faq`, `#about`), not separate PHP files.

## Maintenance

- **Do not commit** `.env` or `uploads/` (see `.gitignore`).
- `database/seed.php` is for local demo data only.
- `admin/email_test.php` is for testing mail configuration.
