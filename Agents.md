# COH-PMS Developer Handbook

This file is the operating manual for anyone (human or AI) continuing work on the City of Harare Property Management System. It describes the system as it exists today, the rules that must not be broken, and the standards every future change must follow.

---

## 1. Working Rules

1. **Plain PHP only.** No Composer, no NPM, no frameworks, no external CDNs. The system must run offline in Laragon.
2. **Correct, secure and explainable beats clever.** This is an academic project that must be defended in a viva; prefer readable code and documented business rules.
3. **One phase at a time.** For substantial work: implement, list changed files, give Laragon test steps, state an acceptance check, then stop for review.
4. **No pseudocode and no placeholder snippets.** Every file must be complete and runnable.
5. **Use the existing architecture.** Controllers handle HTTP, services own business rules, models own SQL, views only render.
6. **Never remove data-protection behaviour** (role guards, CSRF, prepared statements, output escaping, audit logs) to make a feature easier.
7. **Test before declaring done.** Run the six suites and a PHP syntax sweep (see section 11).

---

## 2. Project Summary

**Name:** City of Harare Property Management System (COH-PMS)

**Purpose:** Replace paper venue registers with a secure web system for booking council venues, applying approved pricing rules and reporting facility usage and verified revenue.

**Objectives:**
- **OBJ 1:** Secure web-based venue management.
- **OBJ 2:** Automatic rule-based price adjustment.
- **OBJ 3:** Facility usage and revenue reports.

**Scope limit:** venue booking, pricing, payment recording/verification, and reporting only. No maintenance module, leases, asset disposal, SMS gateway, or live payment gateway.

**Currency:** USD, rendered as `US$ 0.00`. **Timezone:** `Africa/Harare`.

---

## 3. Technology and Architecture

| Layer | Location | Responsibility |
|---|---|---|
| Front controller | `public/index.php` | Loads config/helpers, sets security headers, validates the route, dispatches, renders 404/405/500 |
| Autoloader | `app/core/autoload.php` | Loads classes from `core`, `models`, `services`, `controllers`, `helpers` |
| Router | `app/core/Router.php` | Maps `?r=controller/action` to `XController::action` |
| Base controller | `app/core/Controller.php` | `render`, `redirect`, `redirectRoute`, auth/role guards, `isPost`, `requirePost`, `csrfIsValid` |
| Controllers | `app/controllers/` | Request validation, authorisation, service calls, redirect/render |
| Services | `app/services/` | Availability, pricing, booking state machine, payments, reports |
| Models | `app/models/` | Prepared PDO queries only |
| Helpers | `app/helpers/` | Auth, CSRF, audit/throttle, flash, formatting, validation, uploads, mail |
| Views | `views/` | HTML output only; no business logic beyond loops and display conditionals |
| Config | `config/` | Environment, constants, PDO factory |

**Routing:** `BASE_URL . '/?r=controller/action'`. Routes are limited to `^[a-zA-Z]+(/[a-zA-Z]+)?$`. Only public methods declared on the target controller may be dispatched (reflection guard).

**Two portals:**
- Customer portal (`customer_user_id` session key).
- Staff portal (`staff_user_id` session key) for Booking Officer, Revenue Officer, Administrator and Council Management.

---

## 4. Database (8 Tables)

Schema: `database/coh_pms.sql`. Seeds: `database/seed_data.sql`. Migrations: `database/migrations/`. Customer reads also use the RLS-approximation views `v_customer_bookings`, `v_customer_payments` and `v_customer_receipts` defined in the same schema and in `2026_11_02_add_row_level_security_views.sql`.

| Table | Key columns |
|---|---|
| `users` | `user_id`, `full_name`, `email` (unique), `phone_number`, `password_hash`, `role` enum, `account_status` enum, timestamps |
| `venues` | `venue_id`, `venue_name`, `venue_type` enum, `location`, `capacity`, `facilities`, `description`, `standard_price` (per booking day), `venue_status` enum, `image_path`, timestamps |
| `event_types` | `event_type_id`, `event_name` (unique), `charge_type` enum (`Fixed Amount`/`Percentage`), `charge_value`, `status` enum, `description`, timestamps |
| `event_type_venue_types` | `event_type_id` FK, `venue_type` enum, composite primary key; controls which venue types are suitable for an event type |
| `pricing_rules` | `pricing_rule_id`, `rule_name`, `venue_type` enum, `rule_type` enum, `adjustment_type` enum, `adjustment_value`, `days_of_week`, `min_hours`, `start_date`, `end_date`, `priority` (higher wins), `rule_status` enum, timestamps |
| `bookings` | `booking_id`, `booking_reference` (unique, `COH-YYYYMMDD-XXXX`), `customer_id` FK, `venue_id` FK, `event_type_id` FK, `pricing_rule_id` FK, `booking_date`, `start_time`, `end_time`, `event_type`, `expected_attendance`, `standard_charge`, `event_type_charge`, `adjustment_amount`, `total_charge`, `booking_status` enum, `reviewed_by` FK, timestamps |
| `payments` | `payment_id`, `booking_id` FK, `amount_paid`, `payment_method` enum, `transaction_reference`, `payment_date`, `payment_status` enum, `verified_by` FK, `verification_date`, `receipt_number` (unique, `COH-PMS-VER-XXXXXX`), timestamps |
| `password_reset_tokens` | `reset_id`, `user_id` FK, `token_hash` (unique SHA-256), `expires_at`, `used_at`, `requested_ip`, `created_at` |

**Foreign keys:** bookings customer/venue `RESTRICT`; `event_type_id` and `pricing_rule_id` `SET NULL`; `event_type_venue_types.event_type_id` `CASCADE`; `reviewed_by`, `verified_by` `SET NULL`; payments booking `CASCADE`.

**Indexes:** `bookings(venue_id, booking_date, start_time, end_time)`, `bookings(customer_id)`, `bookings(booking_status)`, `bookings(event_type_id)`, `payments(booking_id)`, `payments(payment_status)`, `password_reset_tokens(token_hash)`, `password_reset_tokens(user_id)`, `password_reset_tokens(expires_at)`, `event_type_venue_types(venue_type)`.

> Any schema change must update `coh_pms.sql`, `seed_data.sql` where relevant, and add an idempotent script under `database/migrations/`.

---

## 5. Roles, Workflow and Business Rules

### 5.1 Roles

| Role | Permissions |
|---|---|
| Customer | Self-register, browse venues, check availability, create bookings, cancel eligible bookings, submit payments, view own bookings/receipts |
| Booking Officer | Manage venues, review bookings (approve/reject/update/cancel/complete), usage reports |
| Revenue Officer | Verify/reject payments, issue receipts, payment and revenue reports |
| Administrator | All staff functions, users, venues, event types, pricing rules, all reports. Payment verification results are not editable. |
| Council Management | Read-only reports and CSV export |

Customers self-register as `Customer`. Only an Administrator creates staff or management accounts (24-hour invitation link).

### 5.2 Booking lifecycle

```text
Pending  -> Approved | Rejected | Cancelled
Approved -> Confirmed (payment verification only) | Cancelled
Confirmed -> Completed | Cancelled (Administrator or Booking Officer)
Rejected | Cancelled | Completed -> no further transitions
```

Transitions live only in `BookingService::TRANSITIONS` and are checked by `BookingService::canTransition()`.

### 5.3 Availability (`AvailabilityService`)

- Blocking statuses: `Pending`, `Approved`, `Confirmed`.
- Overlap rule: `new_start < existing_end AND new_end > existing_start`.
- Reject past dates, `end_time <= start_time` (except valid overnight windows), unavailable venues and attendance above capacity.
- Creation runs the overlap check and insert inside one transaction with `SELECT ... FOR UPDATE`.
- Overnight bookings are allowed only from 18:00 or later until 09:00 or earlier the next day.

### 5.4 Pricing (`PricingService`, OBJ 2)

- Standard charge is the venue price per booking day.
- Event-type charge is added on top: fixed amount, or percentage of the standard charge.
- A matching active rule is selected by highest `priority`, then lowest `pricing_rule_id`; only one rule applies.
- Weekday, Off-Peak and Long Booking rules discount; Weekend and Peak Demand rules surcharge.
- Final total = standard + event-type charge + adjustment, never below zero, rounded to 2 decimals.
- Store the full breakdown on the booking so later rule changes never rewrite history.
- Equivalent event-type wordings (for example `Wedding`, `Traditional Wedding`, `Wedding Reception`) are seeded with the same charge, so customer terminology never changes the price.
- Event types carry venue-type suitability mappings. A booking is rejected unless the chosen venue type is mapped to the event type, and the client-side warning is a convenience only. Unmapped custom event types remain bookable in every venue type.
- Event-type charges are resolved from the event name on the server; posted charge values are never trusted.
- Worked examples for a US$100 venue (no event-type charge): weekday 10% = US$90; weekend 20% = US$120; off-peak 15% = US$85; 8+ hour long booking 5% = US$95; no rule = US$100.

### 5.5 Payments (`PaymentService`)

- A customer may pay only an `Approved` booking with no `Pending Verification` or `Verified` payment.
- `amount_paid` must equal the booking total.
- Verification is atomic: payment `Verified`, verifier and date stored, receipt generated, booking `Confirmed`.
- Rejection leaves the booking `Approved` so the customer can resubmit.
- Cash is recorded through the same flow.

### 5.6 Reports (`ReportService`, OBJ 3)

- Filters: date range, venue, venue type, booking status, payment status.
- Reports: bookings, facility usage (count and hours per venue), payments, revenue (verified only, by venue and month).
- CSV export uses `fputcsv`, a UTF-8 BOM and formula-injection mitigation.
- Visibility: Administrator sees all; Booking Officer sees bookings/usage; Revenue Officer sees payments/revenue; Management sees all read-only.

---

## 6. Security Standards (Non-Negotiable)

1. Every query uses PDO prepared statements; real prepares are enabled and multi-statements are disabled.
2. Passwords use `hash_password_secure()` (bcrypt cost 12); legacy cost-10 hashes are rehashed on the next successful login. Minimum 8 characters with a letter and a digit, maximum 128.
3. Every POST form carries a CSRF token; the server verifies it against the session or the `coh_pms_csrf` cookie copy before acting. Tokens rotate on login and logout. **Exception (2 November 2026):** sign-in (`auth/login`, `auth/staffLogin`) does not require a CSRF token, so a withheld or stale session cookie cannot block access. Credentials, account status and portal role are still verified. Never extend this exception to another action.
4. All output is escaped with `e()`.
5. Sessions regenerate on login, are destroyed on logout, and expire after one idle timeout. Logout always completes, even when the posted token is stale. Cookies are `HttpOnly`, `SameSite=Lax` and `Secure` under HTTPS. The session cookie is `coh_pms_session`; the CSRF token is mirrored in the host-only `coh_pms_csrf` cookie so a withheld session cookie cannot cause a false "form expired" rejection. Forwarded headers are not trusted when deciding cookie flags.
6. Role checks happen on the server in every protected action, never only in the UI.
7. All input is validated server-side; JavaScript validation is a convenience only.
8. Uploads allow only jpg/png/webp up to 2 MB, verified with `finfo` and `getimagesize()` (max 8000x8000), renamed randomly, stored under `public/uploads/venues/` with PHP execution disabled.
9. Secrets live in `.env` (ignored); source folders are blocked by `.htaccess`; errors are logged, never shown as stack traces in production.
10. Login attempts are throttled after 5 failures for 5 minutes. `rate_limit_allow()` adds per-action limits for login-by-IP, registration, password-reset requests, password changes, booking edits and payment submissions.
11. All state changes use POST. Never mutate data on GET.
12. Password-change links are 64-character random tokens, stored only as SHA-256 hashes, single-use, expire after 60 minutes and are throttled per account.
13. Customer reads use the `v_customer_*` views, which filter by `@coh_pms_customer_id`. The views are defence in depth; ownership checks in services/controllers remain mandatory. MySQL/MariaDB have no native RLS.
14. The CSP uses a per-request nonce for inline scripts. Do not reintroduce `event` attributes (`onclick`, `onerror`) or inline scripts without the nonce.
15. Never delete users or venues that have bookings; deactivate instead.

---

## 7. Coding Standards

- `declare(strict_types=1);` at the top of every PHP file.
- PSR-12-style formatting, 4 spaces, braces on the next line for classes/methods.
- Class names `PascalCase`, methods `camelCase`, variables `snake_case` in views and models, constants `UPPER_SNAKE_CASE`.
- One class per file; filename must match the class name.
- Controllers: authorise -> validate -> call service -> redirect/render. No SQL in controllers.
- Services: business rules and transactions. No HTML.
- Models: SQL only, always prepared, return arrays.
- Views: escape every dynamic value with `e()`. Keep logic to loops and conditionals.
- Comments explain the business rule; do not narrate obvious syntax.
- Add `// CHAPTER 5.3.x - <module>` at the top of main module files for the dissertation.
- New routes must be added to `Router` validation implicitly through the controller/action pair; controller actions must be public and declared on the controller.
- New helper functions must belong to one of the existing helper files or a new `*_helper.php` loaded in `public/index.php`.

---

## 8. Front-End and Design System

- Design tokens live in `public/assets/css/style.css` section 1. Reuse tokens; do not hardcode colours.
- Palette: deep municipal navy, off-white, slate, exactly one gold accent.
- Typography: Poppins, fluid `clamp()` scale, 65–72 character measure for prose.
- Accessibility: WCAG 2.2 AAA text contrast, visible `:focus-visible` outlines, semantic landmarks, skip link, keyboard-operable dialogs.
- Every component defines hover, focus-visible, active, disabled, loading and error states.
- Responsive breakpoints: 1280, 1024, 768, 640. Tables become stacked cards on small screens.
- Device policy: The Council Staff Management Portal is restricted to PC devices only (desktop and laptop computers). Mobile user agents and client hints requesting staff routes are greeted with a dedicated 403 desktop-only restriction screen (`views/errors/staff_desktop_only.php`).
- Customer mobile experience: Streamlined header (secondary contact rows hidden on small viewports), 16px form controls to prevent iOS viewport auto-zoom, 44px min-height touch targets conforming to WCAG 2.5.5, and proportional card padding.
- No external fonts, icons or scripts. Use local `uicons` classes and inline SVG.
- Print styles must keep receipts and reports legible; hide navigation and buttons with `.no-print`.
- All interactive data changes must show a confirmation dialog (the shared `dialog` in `layouts/footer.php`).

---

## 9. Environment and Deployment

- `.env` is optional; defaults match Laragon. Never commit `.env`.
- `APP_ENV=production` always disables error display; `APP_DEBUG=true` only enables it outside production.
- `APP_URL` controls `BASE_URL`; leave blank for auto-detection.
- `SESSION_TIMEOUT` is the idle timeout; it is the only session lifetime limit.
- `DB_USER_CUSTOMER`/`DB_PASS_CUSTOMER` optionally select a views-only MySQL account for customer read models. See `database/security/least_privilege_accounts.sql.example`.
- Vhost document root should be `public/`. The root `.htaccess` forwards to `public/` when the project root is used instead.
- The root `.htaccess` blocks `config`, `app`, `database`, `views`, `docs`, `python`, `tests`, `storage`, dotfiles, SQL and Markdown.
- `public/uploads/venues/.htaccess` disables PHP execution.
- Logs: `storage/logs/audit.log` and `storage/logs/rate_limits.json` (both ignored by Git).

---

## 10. Testing and Verification

Use the Laragon PHP binary if `php` is not on PATH:

```powershell
$php = "D:\162\Documentaries\WebsiteDevelopment\L\Laragon\bin\php\php-8.3.26-Win32-vs16-x64\php.exe"
& $php tests\logic_test.php
& $php tests\f7_security_test.php
& $php tests\security_hardening_test.php
& $php tests\password_reset_test.php
& $php tests\suite_test.php
```

Expected: 11 passed, 32 passed, 24 hardening cases passed (up to 29 with MySQL), 8 password-reset cases passed (skipped when MySQL is unavailable), 17 pagination cases passed (skipped when MySQL is unavailable), 37 passed (zero failures). The password-reset, pagination and row-filter checks skip when MySQL is unavailable.

CI entry point (syntax sweep plus all six suites, non-zero exit on failure):

```powershell
& $php tests\run_all.php
```

OpenCode integration uses the project `opencode.json`; `.opencode.yml` and `opencode pipeline` are not supported by OpenCode.

Syntax sweep:

```powershell
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { & $php -l $_.FullName }
```

Manual smoke test after changes:
1. Home page loads and reports database connectivity.
2. Customer registers or signs in, filters venues, opens a venue, checks availability.
3. Booking submits with the correct total; staff approve it.
4. Customer submits payment; Revenue Officer verifies and the receipt prints.
5. Administrator creates/edits an event type and pricing rule.
6. Reports totals match the filtered data; CSV opens correctly.
7. Unauthorised role reaches a 403; bad route reaches 404.

---

## 11. Change Checklist

Before finishing any change:

- [ ] Business rule implemented in a service, not a view or controller.
- [ ] All SQL parameterised.
- [ ] All output escaped.
- [ ] CSRF + role check on every write (sign-in is the documented exception).
- [ ] Schema change mirrored in `coh_pms.sql`, seeds and a migration.
- [ ] `.env.example` updated for any new setting.
- [ ] Documentation updated (`Readme.md`, `docs/`, this file).
- [ ] All six test suites pass.
- [ ] PHP syntax sweep clean.
- [ ] Responsive and print layouts checked.

---

## 12. Future Scope and Roadmap

Deliver these in order of value. Keep the same architecture and security standards.

### Short term (high value, low risk)

1. ~~**Event-type management polish** — usage counts per event type and a warning before deactivating a type used by active bookings.~~ **Done (October 2026):** the event-type list shows total and active usage, and disabling a type in use requires an explicit warning confirmation.
2. ~~**Booking edit workflow** — allow a customer to amend a `Pending` booking, re-running availability and pricing transactionally.~~ **Done (November 2026):** `BookingController::edit/update` and `BookingService::updateBooking()` re-check ownership, status, availability and pricing in one transaction; the reference is re-issued only when the booking date changes.
3. ~~**Report charts** — inline SVG charts for revenue and venue usage (no chart library).~~ **Done (October 2026):** `chart_helper.php` renders composition donut charts on the staff dashboard and reports, and report filters use a compact toolbar with a filter dialog and a clear action.
4. ~~**Pagination** — server-side pagination for bookings, payments, users and report tables once data grows.~~ **Done (November 2026):** `pagination_helper.php` provides clamped page metadata and a filter-preserving pager; staff bookings, payments and users use SQL `LIMIT`/`OFFSET`, and the booking, payment, usage and revenue report tables are paged with totals calculated over the whole filtered set.
5. **Email-ready notifications** — abstract notification messages behind a service so an SMTP or SMS adapter can be added later without touching controllers.

### Medium term

6. **Refund and cancellation accounting** — record refunds as negative payments with their own audit trail; never delete payment rows.
7. **Document attachments** — optional proof-of-payment upload with the same MIME and size protections as venue images.
8. **Audit viewer** — read-only Administrator page for `storage/logs/audit.log` (JSON lines) with filters.
9. **Backup and restore guidance** — documented `mysqldump` procedure and a maintenance mode switch.
10. **API-ready JSON endpoints** — reuse services to expose read-only JSON for a future mobile client.

### Long term (only with explicit approval)

11. **Live payment gateway** — adapter behind `PaymentService`; keep manual verification as the fallback.
12. **SMS/email delivery** — queued notifications after approval, verification and cancellation.
13. **Multi-tenancy / other councils** — only if the dissertation scope expands; requires a tenant column and scoped queries.
14. **Two-factor authentication** — TOTP for staff accounts.

### Explicitly out of scope

- Asset maintenance, leases, disposal, payroll.
- Replacing the plain-PHP stack with a framework.
- Storing card data or raw gateway credentials.

---

## 13. Documentation Index

- `Readme.md` — setup, accounts, testing, troubleshooting.
- `docs/Property Management System Specifications.md` — full specification.
- `docs/System Architecture And Schema Changes.md` — architecture and data dictionary.
- `docs/System Testing.md` — test matrix.
- `docs/System Screenshots.md` — screenshot guide.
- `docs/Frontend Design Instructions.md` — design rules.
- `docs/User Account Creation Strategy.md` — invitation flow.
- `docs/Security Hardening And User Interface Polishing.md` — completed hardening pass.
- `docs/Coding Standards And Future Roadmap.md` — condensed standards and roadmap.
