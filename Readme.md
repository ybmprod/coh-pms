# City of Harare Property Management System (COH-PMS)

A secure, offline-capable venue hire and property management system for the City of Harare municipal council. Built as a final-year National Diploma in Information and Communication Technology (ICT) project using plain PHP, MySQL and vanilla front-end code.

---

## 1. Project Overview

COH-PMS replaces paper venue registers and in-person booking visits for community halls, community centres, stadia and open spaces across Harare. It prevents double bookings, applies approved pricing rules consistently, records payments, and produces management reports.

| Objective | How the system delivers it |
|---|---|
| **OBJ 1** — Implement a secure web-based venue management system | Role-based portals, CSRF protection, prepared statements, session hardening, audit logging, rate-limited login |
| **OBJ 2** — Automatically adjust and calculate property prices using approved rules | `PricingService` applies the highest-priority active rule plus event-type charges and stores a permanent charge breakdown |
| **OBJ 3** — Produce comprehensive management reports on facility usage and verified revenue | `ReportService` produces booking, usage, payment and revenue reports with CSV export |

### Technology stack

- **Backend:** plain PHP 8.1+ (procedural helpers, PDO models, service layer, controllers).
- **Database:** MySQL / MariaDB, database `coh_pms`, charset `utf8mb4`.
- **Frontend:** HTML5, CSS3 (OKLCH design tokens), vanilla JavaScript, local Poppins/uicons — no CDN, no Composer, no NPM.
- **Server:** Laragon (Apache + PHP + MySQL + phpMyAdmin) on Windows.
- **Architecture:** front controller `public/index.php`, query router `?r=controller/action`, MVC-Service layers, lightweight class autoloader.
- **Device Access & Portals:** The Staff Management Portal is restricted to PC devices only (desktop and laptop computers) to maintain administrative audit integrity; mobile devices (smartphones/tablets) requesting staff routes are greeted with a dedicated restriction screen. The Customer Portal is fully responsive and mobile-optimized with 44px touch targets, 16px form controls (preventing iOS auto-zoom), streamlined mobile headers, and proportional card padding.

---

## 2. Requirements

- Laragon with PHP 8.1 or newer (PHP 8.3 is used by the bundled tests).
- MySQL 8 / MariaDB with `utf8mb4`.
- A modern browser (Chrome, Edge or Firefox).

---

## 3. Laragon Setup

### Step 1 — Place the project in the web root

```text
D:\162\Documentaries\WebsiteDevelopment\L\Laragon\www\coh-pms
```

On another machine, place it at `<laragon>\www\coh-pms`.

### Step 2 — Start Laragon

Start Apache and MySQL.

### Step 3 — Create and import the database

1. Open phpMyAdmin (`http://localhost/phpmyadmin`).
2. Import `database/coh_pms.sql`. This creates the `coh_pms` database and **8 tables**: `users`, `venues`, `event_types`, `event_type_venue_types`, `pricing_rules`, `bookings`, `payments`, `password_reset_tokens`.
3. Import `database/seed_data.sql` once. It loads staff, customers, venues, event types, pricing rules, bookings and payments.

> **Upgrading an existing database:** run the migrations in this order from phpMyAdmin:
> 1. `database/migrations/2026_10_31_add_event_type_id_to_bookings.sql` — adds the missing `bookings.event_type_id` column, index and foreign key without dropping data.
> 2. `database/migrations/2026_10_31_reconcile_event_types_and_charges.sql` — renames legacy event types, adds the missing canonical types, links existing bookings, and recalculates event charges, totals and payment amounts. It preserves users, venues, bookings and payments and is safe to re-run.
> 3. `database/migrations/2026_10_31_expand_event_type_wordings.sql` — adds common alternative wordings for existing event types. It only inserts missing rows and is safe to re-run.
> 4. `database/migrations/2026_10_31_add_password_reset_tokens.sql` — adds the secure, single-use customer password-reset token table. It uses `CREATE TABLE IF NOT EXISTS` and is safe to re-run.
> 5. `database/migrations/2026_10_31_rewrite_event_type_descriptions.sql` — replaces charge comparisons in event-type descriptions with plain-English explanations. It is safe to re-run.
> 6. `database/migrations/2026_10_31_add_event_type_venue_types.sql` — adds the event-type/venue-type suitability mapping, seeds the default mappings, and gives unmapped custom event types access to every venue type. It is safe to re-run.
> 7. `database/migrations/2026_11_02_add_row_level_security_views.sql` — creates the `coh_current_customer_id()` function and the read-only `v_customer_bookings`, `v_customer_payments` and `v_customer_receipts` views used by the customer portal. It is safe to re-run.
>
> Fresh installations already include the new schema and seed data in `coh_pms.sql` and `seed_data.sql`.
>
> Optional least-privilege accounts are documented in `database/security/least_privilege_accounts.sql.example`. Set `DB_USER_CUSTOMER`/`DB_PASS_CUSTOMER` in `.env` only after granting that account `SELECT` on the three customer views and nothing on the base tables.

### Step 4 — Configure the environment

Copy `.env.example` to `.env` and adjust if needed:

```text
APP_ENV=development
APP_DEBUG=true
APP_URL=
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=coh_pms
DB_USER=root
DB_PASS=
# Optional: least-privilege account for customer read models only.
DB_USER_CUSTOMER=
DB_PASS_CUSTOMER=
MAIL_TRANSPORT=auto
MAIL_FROM=no-reply@hararecity.co.zw
```

`.env` is ignored by Git. Defaults match a standard Laragon installation, so the app also runs without a `.env` file. Leave `APP_URL` blank to detect the URL automatically, or set it explicitly:

```text
APP_URL=http://coh-pms.test
APP_URL=http://localhost/coh-pms/public
```

`APP_BASE_URL` is accepted as a compatibility alias for council deployment environments; `APP_URL` wins when both are set.

Account emails use `MAIL_TRANSPORT`. In development, `auto` writes the message to `storage/logs/mail.log` so links are never lost when no mail server is running. In production, `auto` uses PHP `mail()` when PHP `SMTP` is configured and reports a delivery failure otherwise. Set `MAIL_TRANSPORT=mail` to try real delivery during testing; development still keeps an outbox copy.

### Step 5 — Open the application

- Laragon virtual host: `http://coh-pms.test`
- Sub-folder URL: `http://localhost/coh-pms/public`

The virtual host document root must point at the `public/` folder. The root `.htaccess` also forwards requests to `public/` when the project root is used as the document root.

---

## 4. Seeded Test Accounts

All seeded accounts use passwords that satisfy the policy (minimum 8 characters, at least one letter and one digit). Only bcrypt hashes are stored.

| Role | Email | Password | Capabilities |
|---|---|---|---|
| Administrator | `admin@coh.co.zw` | `Admin@123` | All staff functions, user accounts, venues, event types, pricing rules, reports |
| Booking Officer | `booking.officer@coh.co.zw` | `Booking@123` | Venues, booking review, usage reports |
| Revenue Officer | `revenue.officer@coh.co.zw` | `Revenue@123` | Payment verification, receipts, payment and revenue reports |
| Council Management | `management@coh.co.zw` | `Management@123` | Read-only reports and CSV export |
| Customer 1 | `customer1@coh.co.zw` | `Customer@123` | Venue browsing, bookings, payments, receipts |
| Customer 2 | `customer2@coh.co.zw` | `Customer@123` | Historical customer records |
| Customer 3 | `customer3@coh.co.zw` | `Customer@123` | Historical customer records |

---

## 5. Folder Structure

```text
coh-pms/
├── app/
│   ├── controllers/       Request handlers (Auth, Dashboard, User, Venue, Eventtype, Pricingrule, Booking, Payment, Report)
│   ├── core/              autoload.php, Router.php, Controller.php (auth, role and HTTP helpers)
│   ├── helpers/           auth, csrf, security, audit, flash, format, validation, upload
│   ├── models/            PDO data access (User, Venue, EventType, PricingRule, Booking, Payment, Report, PasswordReset)
│   └── services/          Availability, Pricing, Booking, Payment, Report business rules
├── config/
│   ├── config.php         .env loader, session hardening, BASE_URL, timezone, app constants
│   ├── constants.php      Role, booking, payment and venue constants
│   └── database.php       PDO factory (exceptions, real prepares, assoc fetch)
├── database/
│   ├── coh_pms.sql        Full schema: 8 tables plus row-filtered customer views
│   ├── seed_data.sql      Demonstration users, venues, event types, rules, bookings, payments
│   ├── security/          Least-privilege MySQL account template
│   └── migrations/        Idempotent upgrade scripts for existing databases
├── docs/                  Specification, architecture, testing, screenshots, design, roadmap
├── public/                The only web-exposed folder
│   ├── assets/            css/style.css, js/booking.js, fonts, images
│   ├── uploads/venues/    Uploaded venue photographs (PHP execution disabled)
│   ├── .htaccess          Front-controller rewrite, compression and caching
│   └── index.php          Security headers, route whitelist, dispatch guard
├── python/                Legacy maintenance scripts (snake_case.py)
├── tests/                 logic, security, security hardening, password reset, regression suites
├── views/                 layouts, auth, customer, staff, errors, home, staff_home
├── .env.example           Environment template
├── .htaccess              Denies source folders, dotfiles, SQL and Markdown
├── Agents.md              Developer handbook and coding standards
└── Readme.md              This guide
```

---

## 6. Running the Automated Tests

PHP is not always on the Windows PATH. Use the Laragon binary if necessary:

```powershell
$php = "D:\162\Documentaries\WebsiteDevelopment\L\Laragon\bin\php\php-8.3.26-Win32-vs16-x64\php.exe"
& $php tests\logic_test.php
& $php tests\f7_security_test.php
& $php tests\security_hardening_test.php
& $php tests\password_reset_test.php
& $php tests\suite_test.php
```

On another machine, replace the path with `<laragon>\bin\php\php-8.3.26-Win32-vs16-x64\php.exe`.

| Suite | Scope | Expected |
|---|---|---|
| `logic_test.php` | Pricing rules, priority, overnight windows | 11 passed |
| `f7_security_test.php` | Passwords, MIME uploads, router guards, CSRF, throttling, audit log | 32 passed |
| `security_hardening_test.php` | CSP nonces, CSRF rotation with cookie-copy fallback, action rate limits, bcrypt cost 12, recursive audit redaction, row-filtered views | 24 passed (up to 29 with MySQL) |
| `password_reset_test.php` | Token hashing, expiry, single-use consumption, throttling, mail fallback | 8 passed (skipped when MySQL is unavailable) |
| `pagination_test.php` | Page windows, customer read views, report totals across pages, unpaginated CSV | 17 passed (skipped when MySQL is unavailable) |
| `suite_test.php` | Integrated security, transitions, pricing, event types, venue suitability, PC device restriction, booking amendment, references, pagination | 37 passed |

All suites run standalone without PHPUnit. The password-reset, pagination and row-filter tests use the local database and report `SKIP` when MySQL is unavailable.

For one CI-friendly gate that runs the syntax sweep and all six suites with a non-zero exit code on failure:

```powershell
& $php tests\run_all.php
```

### OpenCode Integration

The project includes a valid `opencode.json` project configuration. It loads `Agents.md`, exposes a `verify` command, and leaves all permissions unchanged. Restart OpenCode after changing it.

OpenCode does not read `.opencode.yml` and does not provide `opencode pipeline` or `opencode build` commands. Use `tests\run_all.php` as the portable verification gate, and keep deployment packaging in the council CI system.

For a full syntax check:

```powershell
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { & $php -l $_.FullName }
```

---

## 7. Booking and Payment Lifecycle

```text
Customer selects venue, event type, date and times
  └─> live suitability check against the event type's allowed venue types
  └─> live AJAX availability + rule-based charge (standard + event type + adjustment)
  └─> submit booking ......................... Pending
        ├─ Booking Officer rejects ........... Rejected
        └─ Booking Officer approves .......... Approved
              └─ customer submits payment .... Pending Verification
                    ├─ Revenue Officer rejects  Payment Rejected (booking stays Approved)
                    └─ Revenue Officer verifies Payment Verified + receipt number
                                                Booking Confirmed
  └─ after the event ......................... Completed
```

A customer may amend their own `Pending` booking from My Bookings → Edit. The server re-runs availability and pricing in one transaction, ignores the booking being amended when checking overlaps, and re-issues the booking reference only when the date changes.

Allowed transitions are enforced only in `BookingService::TRANSITIONS`. Direct manual confirmation is blocked; confirmation happens when a payment is verified.

---

## 8. Troubleshooting

| Symptom | Fix |
|---|---|
| White page / HTTP 500 | Set `APP_DEBUG=true` in `.env`, check `storage/logs/` and the Apache error log |
| "Database connection failed" | Confirm MySQL is running and `.env` credentials are correct |
| CSS or icons missing | Ensure `public/assets/fonts/` and `public/assets/css/style.css` exist; hard-refresh the browser |
| Images show a placeholder | Upload a venue photograph through Staff → Venues; only seeded paths with actual files render |
| Booking insert fails | Run the migration in section 3, then retry |
| Login keeps returning to sign-in | Check that cookies are allowed for the site and `APP_URL` matches the address in the browser |
| "Your form has expired" on login | Sign-in no longer checks CSRF; reload with Ctrl+F5. If it persists, the browser is running an older cached page or a different copy of the app |
| Password-change email not received | In development, inspect `storage/logs/mail.log`; set `MAIL_TRANSPORT=log` to force the outbox or configure PHP `SMTP` for real delivery |
| 403 on a staff page | Sign in through the staff portal with an account whose role has access |

Customer and staff sign-in deliberately do not require a CSRF token (2 November 2026), so a withheld or stale session cookie cannot lock a user out of the login form. Every other POST action still requires CSRF validation.

---

## 9. Documentation Index

| File | Purpose |
|---|---|
| `docs/Property Management System Specifications.md` | Full functional specification and schema |
| `docs/System Architecture And Schema Changes.md` | Architecture, data dictionary and route reference |
| `docs/System Testing.md` | Test matrix and evidence |
| `docs/System Screenshots.md` | Ordered screenshot guide for Chapter 5.4 |
| `docs/Frontend Design Instructions.md` | Design system rules |
| `docs/User Account Creation Strategy.md` | Staff invitation and activation flow |
| `docs/Security Hardening And User Interface Polishing.md` | Completed hardening checklist |
| `docs/Coding Standards And Future Roadmap.md` | Engineering standards and future scope |
| `Agents.md` | Developer handbook for future work |

