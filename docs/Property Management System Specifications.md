# COH-PMS SPECIFICATION (docs/Property Management System Specifications.md)

City of Harare Property Management System, the master specification.
`Agents.md` holds the permanent coding rules. This file holds **what the system must do**. If the two disagree, ask the student before changing anything.

Version: rebuilt after the source-code review (October 2026). It includes the decisions made during that review.

---

## 1. PROJECT SUMMARY

- **Name:** City of Harare Property Management System (COH-PMS)
- **Type:** Web-based system for council venue hire (community halls, community centres, stadia, open spaces).
- **Problem:** Paper registers and office visits cause double-booking, slow processing, inconsistent prices, and weak reports.
- **Context:** Final-year National Diploma ICT project. The student must be able to explain and defend every part.

### Objectives (every feature must serve one)
- **OBJ 1:** Implement a secure web-based venue management system.
- **OBJ 2:** Automatically adjust property prices using approved rules.
- **OBJ 3:** Produce reports on facility usage and revenue.

### Out of scope
Maintenance, leases, asset disposal, SMS or email sending, live payment gateways (card, EcoCash, etc.), multi-day bookings, recurring bookings.

---

## 2. TECHNOLOGY (FIXED)

| Item | Decision |
|---|---|
| Backend | Plain PHP 8.1+, PDO |
| Database | MySQL/MariaDB, database `coh_pms`, `utf8mb4` |
| Frontend | HTML5, CSS3, vanilla JavaScript. No jQuery, React, Bootstrap, or CDN |
| Server | Laragon on Windows (Apache, PHP, MySQL, phpMyAdmin), VS Code |
| Pattern | MVC-style: models (SQL), services (business rules), controllers (request handling), views (HTML) |
| Routing | `public/index.php` with `?r=controller/action` |
| Payments | Manual recording and verification only. Built so a gateway can be added later |
| Currency | USD, shown as `US$ 0.00`. Timezone `Africa/Harare` |

---

## 3. ROLES AND PERMISSIONS

| Capability | Customer | Booking Officer | Revenue Officer | Administrator | Council Management |
|---|:-:|:-:|:-:|:-:|:-:|
| Register, log in | yes | yes | yes | yes | yes |
| Browse venues, check availability | yes | yes | no | yes | no |
| Create booking, cancel own booking | yes | no | no | no | no |
| Amend own `Pending` booking (re-checks availability and price) | yes | no | no | no | no |
| Submit payment for own booking | yes | no | no | no | no |
| Manage venues (add, edit, set status) | no | yes | no | yes | no |
| Review bookings (approve, reject, cancel, complete) | no | yes | no | yes | no |
| Verify or reject payments, issue receipts | no | no | yes | yes | no |
| Manage pricing rules | no | no | no | yes | no |
| Manage users (create staff, activate or deactivate) | no | no | no | yes | no |
| View booking and usage reports | no | yes | no | yes | yes |
| View payment and revenue reports | no | no | yes | yes | yes |
| Export CSV | no | own reports | own reports | all | all |
| Anything that changes data | own data only | per above | per above | per above | **never (read-only)** |

Rules:
- Customers self-register and always get role `Customer`. Only the Administrator creates other roles.
- A customer sees only their own bookings, payments, and receipts.
- Every role check is done **on the server** in every controller action. Hiding a button is never enough.
- The Administrator cannot deactivate their own account, or the last Active Administrator.
- Deactivated users cannot log in, and an active session of a deactivated user is ended on the next request.

---

## 4. OFFICIAL WORKFLOW

```
Customer logs in -> selects venue, date, start and end time
-> system checks availability
   -> not available: message, customer chooses another slot
   -> available: system applies the pricing rule and shows the charge
-> customer submits request            => booking_status = Pending
   -> optional: customer amends Pending booking
      -> server re-checks availability and pricing transactionally
-> Booking Officer reviews
   -> reject                           => Rejected
   -> approve                          => Approved
-> customer submits payment details    => payment_status = Pending Verification
-> Revenue Officer verifies
   -> not verified                     => payment Rejected, booking stays Approved
   -> verified                         => payment Verified, booking Confirmed,
                                          receipt number issued, confirmation and receipt available
-> after the event date                => Booking Officer marks Completed
```

### Statuses (exact spelling)
- **Booking:** `Pending`, `Approved`, `Rejected`, `Cancelled`, `Confirmed`, `Completed`
- **Payment:** `Pending Verification`, `Verified`, `Rejected`

### Allowed booking status changes (enforced only in `BookingService`)

| From | To | Who |
|---|---|---|
| Pending | Approved, Rejected | Booking Officer, Administrator |
| Pending | Cancelled | Owner customer, Booking Officer, Administrator |
| Approved | Cancelled | Owner customer (only if no payment is Pending Verification or Verified), Booking Officer, Administrator |
| Approved | Confirmed | **Only** `PaymentService`, when a payment is verified |
| Confirmed | Completed | Booking Officer, Administrator (only after the booking date) |
| Confirmed | Cancelled | Booking Officer, Administrator |
| Rejected, Cancelled, Completed | nothing | final |

---

## 5. DATABASE (8 TABLES)

Files: `database/coh_pms.sql` (schema), `database/seed_data.sql` (test data) and `database/migrations/` (idempotent upgrades). Schema changes require approval and must be mirrored in the base schema, a migration and this specification.

> Warning: `coh_pms.sql` starts with `DROP DATABASE IF EXISTS coh_pms`. This is fine for development but deletes all data when re-imported. Use the migration scripts to upgrade an existing database instead.

**users**: `user_id` PK, `full_name` VARCHAR(100), `email` VARCHAR(100) UNIQUE, `phone_number` VARCHAR(20) NULL, `password_hash` VARCHAR(255), `role` ENUM(Customer, Booking Officer, Revenue Officer, Administrator, Council Management) DEFAULT Customer, `account_status` ENUM(Active, Inactive) DEFAULT Active, `created_at`, `updated_at`.

**venues**: `venue_id` PK, `venue_name` VARCHAR(150), `venue_type` ENUM(Community Hall, Community Centre, Stadium, Open Space, Other), `location` VARCHAR(150), `capacity` INT, `facilities` VARCHAR(255) NULL, `description` TEXT NULL, `standard_price` DECIMAL(10,2) **(price per booking, not per hour)**, `venue_status` ENUM(Available, Unavailable, Under Maintenance) DEFAULT Available, `image_path` VARCHAR(255) NULL, `created_at`, `updated_at`.

**event_types**: `event_type_id` PK, `event_name` VARCHAR(100) UNIQUE, `charge_type` ENUM(Fixed Amount, Percentage) DEFAULT Fixed Amount, `charge_value` DECIMAL(10,2), `status` ENUM(Active, Inactive) DEFAULT Active, `description` VARCHAR(255) NULL, `created_at`, `updated_at`. The charge is added to the booking total independently of pricing-rule adjustments.

**event_type_venue_types**: `event_type_id` FK event_types, `venue_type` ENUM(Community Hall, Community Centre, Stadium, Open Space, Other), composite primary key. Maps each event type to the venue types that can host it; an event type with no rows falls back to every venue type.

**pricing_rules**: `pricing_rule_id` PK, `rule_name` VARCHAR(100), `venue_type` ENUM(All, Community Hall, Community Centre, Stadium, Open Space, Other) DEFAULT All, `rule_type` ENUM(Weekday Discount, Weekend Surcharge, Off-Peak Discount, Long Booking Discount, Peak Demand Surcharge), `adjustment_type` ENUM(Percentage, Fixed Amount) DEFAULT Percentage, `adjustment_value` DECIMAL(10,2) (always positive; direction comes from `rule_type`), `days_of_week` VARCHAR(30) NULL (for example `Mon,Tue,Wed,Thu`), `min_hours` DECIMAL(4,1) NULL, `start_date` DATE NULL, `end_date` DATE NULL, `priority` INT DEFAULT 1 (higher wins), `rule_status` ENUM(Active, Inactive) DEFAULT Active, `created_at`, `updated_at`.

**bookings**: `booking_id` PK, `booking_reference` VARCHAR(20) UNIQUE (`COH-YYYYMMDD-XXXX`), `customer_id` FK users, `venue_id` FK venues, `event_type_id` INT NULL FK event_types, `pricing_rule_id` FK pricing_rules NULL, `booking_date` DATE, `start_time` TIME, `end_time` TIME, `event_type` VARCHAR(100) NULL, `expected_attendance` INT NULL, `standard_charge` DECIMAL(10,2), `event_type_charge` DECIMAL(10,2) DEFAULT 0, `adjustment_amount` DECIMAL(10,2) DEFAULT 0 (negative = discount, positive = increase), `total_charge` DECIMAL(10,2), `booking_status` ENUM(...) DEFAULT Pending, `reviewed_by` INT NULL FK users, `created_at`, `updated_at`.

**payments**: `payment_id` PK, `booking_id` FK bookings, `amount_paid` DECIMAL(10,2), `payment_method` ENUM(Cash, Bank Transfer, Mobile Money, Card), `transaction_reference` VARCHAR(100), `payment_date` DATETIME, `payment_status` ENUM(Pending Verification, Verified, Rejected) DEFAULT Pending Verification, `verified_by` INT NULL FK users, `verification_date` DATETIME NULL, `receipt_number` VARCHAR(30) UNIQUE NULL (`COH-PMS-VER-XXXXXX`).

**password_reset_tokens**: `reset_id` PK, `user_id` FK users, `token_hash` CHAR(64) UNIQUE (SHA-256 of the emailed token, never the plain token), `expires_at` DATETIME, `used_at` DATETIME NULL, `requested_ip` VARCHAR(45) NULL, `created_at`.

**Keys:** bookings.customer_id RESTRICT, bookings.venue_id RESTRICT, bookings.event_type_id SET NULL, bookings.pricing_rule_id SET NULL, bookings.reviewed_by SET NULL, event_type_venue_types.event_type_id CASCADE, payments.booking_id CASCADE, payments.verified_by SET NULL, password_reset_tokens.user_id CASCADE.
**Indexes:** `bookings(venue_id, booking_date, start_time, end_time)`, `bookings(customer_id)`, `bookings(booking_status)`, `bookings(event_type_id)`, `payments(booking_id)`, `payments(payment_status)`, `password_reset_tokens(token_hash)`, `password_reset_tokens(user_id)`, `password_reset_tokens(expires_at)`, `event_type_venue_types(venue_type)`.

Users and venues with bookings are never deleted. They are deactivated or set Unavailable.

---

## 6. BUSINESS RULES

### 6.1 Availability (`AvailabilityService`)
- A slot is blocked when another booking for the **same venue and date** has status `Pending`, `Approved`, or `Confirmed` and the times overlap.
- Overlap test: `new_start < existing_end AND new_end > existing_start`. A booking that ends at 12:00 does not block one that starts at 12:00.
- Refuse: past dates; `end_time <= start_time`; venue not `Available`; attendees above capacity; attendees below 1.
- Check and INSERT happen inside **one PDO transaction** with `SELECT ... FOR UPDATE`.
- A JSON endpoint gives live availability and live price to `booking.js`. The server check on submit is always required.
- The selected venue type must be mapped to the event type in `event_type_venue_types`. Invalid combinations are rejected server-side; unmapped custom event types may use any venue type.

### 6.2 Pricing (`PricingService`), Objective 2
1. `standard_charge` = venue `standard_price` (per booking). Add the event-type charge next: a fixed amount or a percentage of the standard charge, based on the active `event_types` record.
2. Hours = `(end - start)` in minutes divided by 60. Hours are used only for `min_hours`.
3. Rule matches when all of these are true: rule is `Active`; `venue_type` equals the venue type or `All`; booking date is inside `start_date` and `end_date` when they are set; the weekday is in `days_of_week` when set; hours are at least `min_hours` when set.
4. Direction is fixed by `rule_type`:
   - **Reduce:** Weekday Discount, Off-Peak Discount, Long Booking Discount.
   - **Increase:** Weekend Surcharge, Peak Demand Surcharge.
5. If several rules match, apply **only the one with the highest `priority`**. On a tie, the lower `pricing_rule_id` wins.
6. Adjustment: Percentage = `standard * value / 100`. Fixed Amount = `value`. A discount larger than the standard charge is limited to the standard charge.
7. `total_charge = standard_charge + event_type_charge + adjustment_amount`, never below 0, rounded to 2 decimals.
8. Save `standard_charge`, `event_type_charge`, `adjustment_amount`, `total_charge`, `event_type_id`, and `pricing_rule_id` on the booking, so old bookings never change when rules or event-type charges change.
9. Show the event-type charge, rule name, adjustment, and final charge on the booking form, the booking details page, and the receipt.

**Reference results for a US$100 venue:** Mon to Thu with Weekday Discount 10% gives 90. Fri to Sun with Weekend Surcharge 20% gives 120. Off-peak 15% gives 85. A booking of 8 hours or more with Long Booking Discount 5% gives 95. No matching rule gives 100.

### 6.3 Payments (`PaymentService`)
- A customer can submit a payment only for **their own booking** that is `Approved` and has no payment in `Pending Verification` or `Verified`.
- The amount is taken from the booking `total_charge` on the server. The browser value is never trusted. The payment date is the server time.
- Verify, in **one transaction**: lock the payment, require `Pending Verification`, set `Verified`, `verified_by`, `verification_date`, create a unique receipt number, set the booking to `Confirmed`.
- Reject: payment `Rejected`, booking stays `Approved`, the customer may submit new payment details.
- Receipt number: `COH-PMS-VER-XXXXXX` (6-digit sequence, retry on duplicate).
- Cash payments are also entered through the system so every transaction has a record.

### 6.4 Reports (`ReportService`), Objective 3
- Filters: date range, venue, venue type, booking status, payment status. Every filter value is validated on the server.
- Reports: **Booking report**, **Facility usage report** (booking count, booked hours, usage percentage; venues with no bookings still appear), **Payment report**, **Revenue report** (**Verified payments only**, by venue and by month).
- Hours are calculated in minutes divided by 60 and rounded to 2 decimals. Usage percentage assumes 12 available hours per day, and the page states this.
- Reports show HTML tables with totals, inline SVG donut charts for composition, a compact filter toolbar with a filter dialog and a clear action, and a **CSV export** (UTF-8 with BOM, formula-injection protection, totals row).
- Access follows the roles table in Section 3.

---

## 7. FOLDER STRUCTURE

```
coh-pms/
├── config/        config.php, database.php, constants.php
├── app/
│   ├── core/         autoload.php, Router.php, Controller.php
│   ├── controllers/  Auth, User, Venue, Eventtype, Booking, Pricingrule, Payment, Report, Dashboard
│   ├── models/       User, Venue, EventType, PricingRule, Booking, Payment, Report   (SQL only)
│   ├── services/     AvailabilityService, PricingService, BookingService, PaymentService, ReportService
│   └── helpers/      auth, csrf, audit, validation, format, flash, upload
├── views/         layouts/, auth/, customer/, staff/, errors/ (403, 404, 405, 500), home.php
├── public/        index.php, .htaccess, assets/{css,js,images}, uploads/venues/ (+ .htaccess)
├── database/      coh_pms.sql, seed_data.sql, migrations/
├── python/        split_users.py, update_bookings.py, update_sidebar.py, update_sidebar_js.py
├── tests/         suite_test.php, f7_security_test.php, logic_test.php
├── docs/          Property Management System Specifications.md, System Testing.md, System Screenshots.md,
│                  System Architecture And Schema Changes.md, Frontend Design Instructions.md,
│                  User Account Creation Strategy.md, Security Hardening And User Interface Polishing.md,
│                  Coding Standards And Future Roadmap.md
├── .env.example   Environment template
├── Agents.md
└── Readme.md
```
Local URL: `http://localhost/coh-pms/public`, or `http://coh-pms.test` with a Laragon virtual host. `BASE_URL` must be configurable.

---

## 8. SECURITY REQUIREMENTS

1. PDO prepared statements everywhere; `ATTR_EMULATE_PREPARES=false`; `ERRMODE_EXCEPTION`; multi-statements disabled.
2. `password_hash` and `password_verify`; new hashes use bcrypt cost 12; minimum 8 characters with at least one letter and one digit, maximum 128.
3. CSRF token on every POST form, verified on the server against the session or the `coh_pms_csrf` cookie copy. Tokens rotate on login and logout.
4. All output escaped with `e()`.
5. `session_regenerate_id(true)` on login; single idle timeout; full destroy on logout; HttpOnly and SameSite cookies; Secure flag on HTTPS.
6. Server-side role check on every action; user status re-checked on every request.
7. Server-side validation of every input.
8. Uploads: jpg, png, webp only; 2 MB; `finfo` MIME check; `getimagesize()` header decode; maximum 8000x8000; random name; mode 0640; no PHP execution in the uploads folder.
9. No stack traces or database errors shown to users. Log them; production always disables debug output.
10. Login attempt limit: 5 failures, then a 5-minute lock, plus per-IP and per-action fixed-window limits for registration, password resets, booking writes and payment submissions.
11. State-changing actions use POST only.
12. Never delete users or venues that have bookings.
13. Security headers: `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy: no-referrer`, nonce-based CSP, COOP, CORP and Permissions-Policy.
14. Password-change links use 64-character random tokens stored only as SHA-256 hashes, expire after 60 minutes, are single-use, invalidate prior links and are throttled per account. Public requests return the same message whether or not the email exists.
15. Customer reads use row-filtered `v_customer_*` views driven by `@coh_pms_customer_id`; application ownership checks remain mandatory because MySQL/MariaDB have no native RLS.
16. Audit context is recursively redacted for passwords, tokens, secrets, cookies and API keys.

---

## 9. INTERFACE & FRONTEND UI/UX DESIGN ENGINEERING

- Colours: dark blue `#0B3C5D`, white, light grey `#F4F6F8`, one accent.
- Two layouts: public/customer (top bar) and staff (top bar and sidebar). The sidebar shows only links the role may use. Login and Register links show only to guests; Logout is a POST form.
- Status badges, flash messages, confirmation prompts for destructive actions, labelled forms, mobile-friendly (breakpoint 768px).
- Staff Portal device restriction: The Council staff workspace is strictly PC-only (desktop and laptop computers). Mobile user agents and client hints attempting to access staff routes are redirected to a dedicated 403 desktop-only restriction page (`views/errors/staff_desktop_only.php`).
- Customer mobile UI/UX strategy: Fluid responsive hierarchy, streamlined mobile header (secondary phone/email contact hidden on small screens), 16px form controls to prevent iOS auto-zoom, 44px min-height touch targets conforming to WCAG 2.5.5, and proportional card padding.
- Printable booking confirmation and receipt page with a print stylesheet.
- Plain, simple wording in all messages.

### Frontend UI/UX Design Engineering Instructions
Provide elite, production-grade frontend UI/UX implementations for modern websites. Ensure all code outputs strictly diverge from generic, template-like "AI aesthetics" by prioritizing intentional typography, modern color geometry, and robust interaction states.

#### 9.1 Universal Layout & Composition
- **Asymmetry & Grid Systems:** Use CSS Grid and Flexbox dynamically. Break out of rigid, repetitive card layouts. Utilize proportional, content-driven spacing instead of arbitrary hardcoded widths.
- **Visual Anchors:** Ensure a clear typographical hierarchy. Emphasize section entries with distinctive header treatments or bold font sizing.
- **The Overuse Ban:** Avoid generic full-bleed gradients, neon glowing card borders, and center-aligned paragraphs for core content blocks.

#### 9.2 Modern Color Engineering & Design Tokens
- **OKLCH Color Space:** Implement design systems using modern OKLCH colors for uniform perceptual brightness across themes.
- **Intentional Palettes:** Default to an editorial, minimal palette (e.g., deep charcoal, soft off-whites, neutral slate) paired with exactly one distinct accent color used sparingly for active elements or branding.
- **Contrast Compliance:** Strictly adhere to WCAG 2.2 AAA text-to-background contrast requirements. Never use light grey text on white backgrounds or deep blue on black.

#### 9.3 Advanced UI Elements & Dynamic States
- **Top-Layer Animations:** When implementing modals, drawers, or slide-outs, use modern CSS properties (`@starting-style`, `transition-behavior: allow-discrete`, and `overlay`) to ensure seamless transition tracking when elements enter and leave the DOM.
- **Component Libraries:** Prefer generating structural mockups utilizing standardized UI systems (like Tailwind CSS tokens, `shadcn/ui` components, or Radix primitives).
- **State Coverage:** Every interactive component must natively define styles for six core states: `:hover`, `:focus-visible`, `:active`, `[disabled]`, loading skeleton placeholders, and error-handling conditions.

#### 9.4 Accessibility (a11y) & Performance Execution
- **Semantic HTML:** Use structural landmarks natively (`<main>`, `<nav>`, `<aside>`, `<header>`, `<footer>`). Never stack nested `<div>` tags if semantic options exist.
- **Keyboard Operability:** Ensure all custom interactive components can be navigated via keyboard focus logic with visible element boundaries (`outline: 2px solid var(--accent)`).
- **Responsive Layouts:** Establish fluid-type systems and media queries bounded by clean, standardized logical breakpoints (`sm`, `md`, `lg`, `xl`). Prevent component truncation or awkward vertical text breaks on standard screens.

---

## 10. CHAPTER 5 MAPPING (project report)

Each main file starts with a comment `// CHAPTER 5.3.x - <name>`.

| Section | Name | Main files |
|---|---|---|
| 5.3.1 | User Authentication and Access Control | AuthController, UserController, DashboardController, Controller.php, auth_helper.php |
| 5.3.2 | Venue Management | VenueController, Venue.php, upload_helper.php |
| 5.3.3 | Booking and Availability Management | BookingController, Booking.php, BookingService, AvailabilityService |
| 5.3.4 | Pricing Rules Implementation | PricingruleController, PricingRule.php, PricingService |
| 5.3.5 | Payment Recording and Verification | PaymentController, Payment.php, PaymentService |
| 5.3.6 | Reporting Implementation | ReportController, Report.php, ReportService |

---

## 11. BUILD PHASES

### A. Original build (Phases 1 to 10)
| Phase | Scope |
|---|---|
| 1 | Folders, SQL, seed data, config, PDO, router, helpers |
| 2 | Registration, login, logout, role redirect, layouts, 403 and 404 |
| 3 | Administrator dashboard and user management |
| 4 | Venue management with image upload; customer venue pages |
| 5 | Availability service and live check |
| 6 | Pricing rules and pricing service |
| 7 | Booking creation, my bookings, cancel, Booking Officer review |
| 8 | Payments, verification, receipts, confirmation page |
| 9 | Reports, CSV, role dashboards |
| 10 | Final review, README, tests, screenshots list |

### B. Corrections (Phases F1 to F8), see `COH-PMS_Fix_Prompt.md`
F1 services layer. F2 access control and sessions. F3 booking and availability. F4 pricing. F5 payments and receipts. F6 reports. F7 security hardening and cleanup. F8 documentation, tests, final review.

**Current status:** Phases 1 to 10 and corrections F1 to F8 are complete. The service layer, live availability, customer cancellation, booking details, printable receipt, login lockout, environment configuration, autoloading, automated tests and documentation are implemented. Event-type pricing and the `bookings.event_type_id` schema link were added during the October 2026 reconstruction pass, followed by event-type usage counts, a deactivation warning for types used by active bookings, plain-English descriptions, and venue-type suitability filtering with server-side enforcement.

---

## 12. SEED DATA

- Accounts: `admin@coh.co.zw` (Administrator), `booking.officer@coh.co.zw`, `revenue.officer@coh.co.zw`, `management@coh.co.zw` (Council Management), `customer1@coh.co.zw`, `customer2@coh.co.zw`, `customer3@coh.co.zw`. Test passwords are listed in `Readme.md`.
- 49 venues of different types (Harare locations), 42 event types (including alternative wordings with plain-English descriptions) and 5 pricing rules from Section 6.2. Pricing rule dates cover 2026 and 2027.
- 12 bookings and 7 payments covering every status. Booking totals include the event-type charge.

---

## 13. DEFINITION OF DONE (every phase)

1. Files created or changed (list).
2. Exact steps to test in Laragon.
3. Acceptance check (what the student should see).
4. Security checklist (Section 8) confirmed for the items touched.
5. Anything not done or unclear.
6. Stop and wait for the student to say "continue".

