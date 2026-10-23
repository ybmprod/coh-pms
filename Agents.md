## 0. YOUR ROLE AND HOW TO WORK

You are a senior PHP developer building a final-year National Diploma ICT project. The system must be **correct, secure, simple, and easy for a student to explain and defend**. Prefer clear code over clever code. No frameworks, no Composer, no Node.

**Work in phases.** Build ONE phase at a time (Section 9). At the end of each phase:
1. List the files you created or changed.
2. Give the exact steps I must follow to test it in Laragon.
3. Give a short "Acceptance Check" (what I should see if it works).
4. **Stop and wait for me to say "continue".** Do not start the next phase on your own.

Never use pseudocode, "..." or "rest of code here". Every file must be complete and runnable. If you must change an earlier file, show the whole updated file.

---

## 1. PROJECT SUMMARY

**Name:** City of Harare Property Management System (COH-PMS)
**Type:** Web-based system for managing council venue hire (community halls, community centres, stadia, open spaces).
**Problem it solves:** The City of Harare uses paper registers and office visits for venue bookings. This causes double-booking, slow processing, inconsistent prices, and weak reports.

**The three project objectives (every feature must serve one of them):**
- **OBJ 1:** To implement a secure web-based venue management system.
- **OBJ 2:** To automatically adjust prices of property using approved rules.
- **OBJ 3:** To produce reports on facility usage and revenue.

**Scope limit:** Venue booking, pricing, payment recording/verification, and reports only. Do NOT build maintenance, leases, asset disposal, SMS gateways, or live payment gateways.

---

## 2. TECHNOLOGY RULES (FIXED)

| Item | Decision |
|---|---|
| Backend | Plain PHP 8.1+ (procedural helpers + simple classes), PDO for MySQL |
| Database | MySQL/MariaDB, database name `coh_pms`, charset `utf8mb4` |
| Frontend | HTML5, CSS3, vanilla JavaScript (no jQuery, no React, no Bootstrap CDN) |
| Server | Laragon (Apache + PHP + MySQL + phpMyAdmin) on Windows |
| Editor | VS Code |
| Pattern | Simple MVC-style: models (database), services (business rules), controllers (request handling), views (HTML) |
| Routing | One front controller `public/index.php` with a tiny router (`?r=controller/action`). Keep it under 80 lines. |
| Payments | **Manual recording and verification only.** No live gateway. Write code so a gateway can be added later. |
| Currency | USD, shown as `US$ 0.00` |
| Timezone | `Africa/Harare` (set in config) |

Do not add any library that needs `composer install` or `npm install`. Fonts and icons must work offline (use system fonts and inline SVG or Unicode icons).

---

## 3. USER ROLES

Five roles (the ERD stores role as text):

| Role | Can do |
|---|---|
| **Customer** | Register, log in, browse venues, check availability, create booking, see calculated charge, cancel own booking (before payment is verified), submit payment details, view own bookings, confirmation and receipts |
| **Booking Officer** | Manage venues; review bookings: approve, reject, update, cancel; view booking dashboard |
| **Revenue Officer** | See approved bookings awaiting payment; record or verify/reject payments; issue receipts |
| **Administrator** | Everything staff can do except changing payment verification results; manage users (create staff accounts, activate/deactivate), venues, pricing rules; view all reports |
| **Council Management** | **Read-only** access to the reports dashboard and CSV export |

Rules: Customers self-register (role is always Customer). Only an Administrator can create staff or Council Management accounts. Every protected page must check the role on the server.

---

## 4. THE OFFICIAL WORKFLOW (MUST MATCH THE PROJECT DOCUMENT, CHAPTER 4.4)

```
Customer logs in -> selects venue, date, start/end time
-> system checks availability
   -> not available: show message, customer picks another slot
   -> available: system applies pricing rule and shows total charge
-> customer submits request          => booking_status = 'Pending'
-> Booking Officer reviews
   -> reject                         => 'Rejected' (customer notified on dashboard)
   -> approve                        => 'Approved'
-> customer submits payment details  => payment_status = 'Pending Verification'
-> Revenue Officer verifies
   -> not verified                   => payment 'Rejected'; booking stays 'Approved'
   -> verified                       => payment 'Verified', booking 'Confirmed',
                                        receipt number generated, confirmation + receipt available
-> after the event date              => Booking Officer may mark 'Completed'
```

**Booking statuses (exact spelling):** `Pending`, `Approved`, `Rejected`, `Cancelled`, `Confirmed`, `Completed`
**Payment statuses:** `Pending Verification`, `Verified`, `Rejected`

Implement status changes in ONE place (a `BookingService` method) that checks the allowed transitions:
- Pending -> Approved, Rejected, Cancelled
- Approved -> Confirmed (only through payment verification), Cancelled
- Confirmed -> Completed, Cancelled (Administrator or Booking Officer only)
- Rejected, Cancelled, Completed -> no further changes

---

## 5. DATABASE (5 TABLES, MATCHING THE ERD)

Create `database/coh_pms.sql` (creates database, tables, keys, indexes) and `database/seed_data.sql`.

**users**: `user_id` PK, `full_name` VARCHAR(100), `email` VARCHAR(100) UNIQUE, `phone_number` VARCHAR(20), `password_hash` VARCHAR(255), `role` ENUM('Customer','Booking Officer','Revenue Officer','Administrator','Council Management') DEFAULT 'Customer', `account_status` ENUM('Active','Inactive') DEFAULT 'Active', `created_at`, `updated_at`

**venues**: `venue_id` PK, `venue_name` VARCHAR(150), `venue_type` ENUM('Community Hall','Community Centre','Stadium','Open Space','Other'), `location` VARCHAR(150), `capacity` INT, `facilities` VARCHAR(255), `description` TEXT, `standard_price` DECIMAL(10,2) (price per booking day), `venue_status` ENUM('Available','Unavailable','Under Maintenance') DEFAULT 'Available', `image_path` VARCHAR(255) NULL, `created_at`, `updated_at`

**pricing_rules**: `pricing_rule_id` PK, `rule_name` VARCHAR(100), `venue_type` ENUM('All','Community Hall','Community Centre','Stadium','Open Space','Other') DEFAULT 'All', `rule_type` ENUM('Weekday Discount','Weekend Surcharge','Off-Peak Discount','Long Booking Discount','Peak Demand Surcharge'), `adjustment_type` ENUM('Percentage','Fixed Amount') DEFAULT 'Percentage', `adjustment_value` DECIMAL(10,2) (positive number; direction comes from `rule_type`), `days_of_week` VARCHAR(30) NULL (e.g. `Mon,Tue,Wed,Thu`), `min_hours` DECIMAL(4,1) NULL (for long-booking rule), `start_date` DATE NULL, `end_date` DATE NULL, `priority` INT DEFAULT 1 (higher number wins), `rule_status` ENUM('Active','Inactive') DEFAULT 'Active', `created_at`, `updated_at`

**bookings**: `booking_id` PK, `booking_reference` VARCHAR(20) UNIQUE (format `COH-YYYYMMDD-XXXX`), `customer_id` FK users, `venue_id` FK venues, `pricing_rule_id` FK pricing_rules NULL, `booking_date` DATE, `start_time` TIME, `end_time` TIME, `event_type` VARCHAR(100), `number_of_attendees` INT NULL, `standard_charge` DECIMAL(10,2), `adjustment_amount` DECIMAL(10,2) DEFAULT 0 (negative = discount, positive = increase), `total_charge` DECIMAL(10,2), `booking_status` ENUM('Pending','Approved','Rejected','Cancelled','Confirmed','Completed') DEFAULT 'Pending', `reviewed_by` INT NULL FK users, `created_at`, `updated_at`

**payments**: `payment_id` PK, `booking_id` FK bookings, `amount_paid` DECIMAL(10,2), `payment_method` ENUM('Cash','Bank Transfer','Mobile Money','Card'), `transaction_reference` VARCHAR(100), `payment_date` DATETIME, `payment_status` ENUM('Pending Verification','Verified','Rejected') DEFAULT 'Pending Verification', `verified_by` INT NULL FK users, `verification_date` DATETIME NULL, `receipt_number` VARCHAR(30) UNIQUE NULL (format `RCT-YYYYMMDD-XXXX`)

Foreign keys: bookings.customer_id -> users (RESTRICT), bookings.venue_id -> venues (RESTRICT), bookings.pricing_rule_id -> pricing_rules (SET NULL), payments.booking_id -> bookings (CASCADE), payments.verified_by -> users (SET NULL).
Indexes: `bookings(venue_id, booking_date, start_time, end_time)`, `bookings(customer_id)`, `bookings(booking_status)`, `payments(booking_id)`, `payments(payment_status)`.

> Note for the student: `facilities`, `days_of_week`, `min_hours`, `priority`, `reviewed_by`, and the `Council Management` role are small additions to support the documented requirements. Update Table 4.x and the ERD in Chapter 4.6 to match.

---

## 6. BUSINESS RULES (THE "BRAIN" OF THE SYSTEM)

### 6.1 Availability (AvailabilityService)
- A slot is **blocked** if another booking for the same venue and date has status `Pending`, `Approved`, or `Confirmed` and the times overlap.
- Overlap test: `new_start < existing_end AND new_end > existing_start`.
- Reject: past dates, `end_time <= start_time`, venues not `Available`, attendees above capacity.
- When saving a booking, run the overlap check and the INSERT **inside one PDO transaction** (`SELECT ... FOR UPDATE` on that venue's bookings for that date) so two users cannot book the same slot at the same moment.
- Provide an AJAX endpoint that returns JSON: `{available: true/false, message: "..."}`, used by `booking.js` for live checking before submit. The server check on submit is still required.

### 6.2 Pricing (PricingService) - OBJECTIVE 2
- `standard_charge` = venue `standard_price` (per booking day).
- Find **active** rules where: venue_type matches (or is `All`); booking date is inside start_date/end_date (when set); the weekday is in `days_of_week` (when set); the booking length is at least `min_hours` (when set).
- Rule types: Weekday Discount and Off-Peak Discount and Long Booking Discount **reduce** the price; Weekend Surcharge and Peak Demand Surcharge **increase** it.
- If several rules match, apply only the one with the **highest `priority`** (the rule the administrator chose). Apply only ONE rule per booking.
- `adjustment_amount`: for Percentage = `standard_charge * value / 100`; for Fixed Amount = `value`. Make it negative for discounts and positive for increases.
- **Final Charge = Standard Charge + Adjustment Amount.** Never allow a total below 0. Round to 2 decimals.
- Store `standard_charge`, `adjustment_amount`, `total_charge`, and `pricing_rule_id` in the booking so old bookings never change when rules change later.
- Show rule name, adjustment, and final charge on the booking form (live via AJAX), the booking details page, and the receipt.
- Seed this worked example (must produce these results for a US$100 hall):
  - Mon-Thu, Weekday Discount 10% -> US$90
  - Fri-Sun, Weekend Surcharge 20% -> US$120
  - Off-peak month discount 15% -> US$85
  - Booking of 8+ hours, Long Booking Discount 5% -> US$95
  - No rule -> US$100

### 6.3 Payments (PaymentService)
- Customer can submit a payment only for a booking that is `Approved` and has no payment in `Pending Verification` or `Verified`.
- `amount_paid` must equal the booking `total_charge` (reject mismatches with a clear message).
- Revenue Officer verifies or rejects. On **verify**, in ONE transaction: set payment `Verified`, store `verified_by` and `verification_date`, generate a unique `receipt_number`, set booking to `Confirmed`.
- On **reject**: payment `Rejected`, booking stays `Approved`, customer may submit new payment details.
- Cash payments are also entered through the system so every transaction has a record.

### 6.4 Reports (ReportService) - OBJECTIVE 3
Filters: date range, venue, venue type, booking status, payment status.
Reports: (a) Booking report, (b) Facility usage report (bookings count and total hours per venue, usage percentage), (c) Payment report, (d) Revenue report (verified payments only; by venue and by month).
Show on screen as HTML tables with summary totals, and provide **CSV export** (`fputcsv`). Visible to Administrator, Booking Officer (bookings and usage only), and Council Management (all, read-only). Optional: simple bar chart using plain CSS or inline SVG (no chart libraries).

---

## 7. FOLDER STRUCTURE (USE EXACTLY)

```
coh-pms/
├── config/
│   ├── config.php            (app name, base URL, timezone, session settings)
│   ├── database.php          (DB credentials, PDO connection function)
│   └── constants.php         (status and role constants)
├── app/
│   ├── controllers/          AuthController, UserController, VenueController, BookingController,
│   │                         PricingRuleController, PaymentController, ReportController, DashboardController
│   ├── models/               User, Venue, PricingRule, Booking, Payment  (PDO queries only)
│   ├── services/             AvailabilityService, PricingService, BookingService, PaymentService, ReportService
│   ├── helpers/              auth_helper, csrf_helper, validation_helper, date_helper, format_helper, flash_helper, upload_helper
│   └── core/                 Router.php, Controller.php (base: render view, redirect, require role)
├── views/
│   ├── layouts/              public_header, staff_header, sidebar, footer
│   ├── auth/                 login, register, staff_login, activate
│   ├── customer/             venues, venue_details, bookings, booking_details, receipt
│   ├── staff/                dashboard, venues, bookings, payments, users, pricing_rules, reports
│   ├── home.php, staff_home.php
│   └── errors/               403, 404, 500
├── public/                   (the only folder exposed to the web)
│   ├── index.php             (front controller)
│   ├── .htaccess
│   ├── assets/css/style.css, assets/js/booking.js, assets/fonts/, assets/images/
│   └── uploads/venues/
├── database/                 coh_pms.sql, seed_data.sql
├── docs/                     Property Management System Specifications.md, System Architecture And Schema Changes.md,
│                             System Testing.md, System Screenshots.md, Frontend Design Instructions.md,
│                             User Account Creation Strategy.md
├── docs 2/                   Reference layout/view snapshots (*.txt)
├── python/                   Python utility/migration scripts (split_users.py, update_bookings.py,
│                             update_sidebar.py, update_sidebar_js.py)
├── tests/                    suite_test.php, f7_security_test.php, logic_test.php
├── .htaccess                 (deny direct access to app/, config/, database/, views/, docs/, python/, tests/)
├── Agents.md
└── Readme.md
```
> **File Naming & Organization Rules:**
> - **Markdown files (`.md`):** Always use **Title Case With Spaces** (e.g., `Agents.md`, `Readme.md`, `docs/Property Management System Specifications.md`, `docs/System Testing.md`, `docs/System Screenshots.md`, `docs/System Architecture And Schema Changes.md`, `docs/Frontend Design Instructions.md`, `docs/User Account Creation Strategy.md`). Never use `ALL_CAPS_SNAKE_CASE.md`.
> - **Python files (`.py`):** Store all Python helper/utility scripts inside `coh-pms/python/` using `snake_case.py` filenames.

Local URL: `http://coh-pms.test` (Laragon auto virtual host pointing at `/public`) or `http://localhost/coh-pms/public`. Make `BASE_URL` configurable so both work.

---

## 8. SECURITY AND QUALITY REQUIREMENTS (NON-NEGOTIABLE)

1. PDO prepared statements for **every** query. `PDO::ATTR_EMULATE_PREPARES => false`, `ERRMODE_EXCEPTION`.
2. `password_hash(PASSWORD_DEFAULT)` and `password_verify()`. Min password length 8.
3. CSRF token in every POST form, verified on the server.
4. Escape all output with `htmlspecialchars()` (create a short `e()` helper).
5. `session_regenerate_id(true)` on login; destroy session fully on logout; session cookie `HttpOnly` and `SameSite=Lax`.
6. Server-side role check on every controller action (never rely on hidden buttons).
7. Server-side validation for all inputs (required fields, email format, numbers, dates, enums). JavaScript validation is only a convenience.
8. Image upload: allow only jpg/png/webp, max 2 MB, check MIME with `finfo`, rename to a random unique name, store in `public/uploads/venues/`.
9. Database credentials and app code never inside `public/`. No stack traces shown to users (log errors, show friendly messages).
10. Limit failed logins (e.g. 5 attempts, then 5-minute lock using session or a small counter).
11. Use POST for all actions that change data (approve, reject, delete, verify). Never use GET for these.
12. Prevent deleting a venue or user that has bookings (deactivate instead). Show a clear message.

**UI & Frontend UI/UX Design Engineering:**
clean, responsive, City of Harare style (dark blue `#0B3C5D`, white, light grey `#F4F6F8`, one accent colour). Separate public/customer layout and staff layout (top bar + sidebar). Coloured status badges, flash messages (success/error), confirmation prompts for approve/reject/delete, mobile-friendly with CSS media queries. Include `title`, labels, and accessible form fields.

Provide elite, production-grade frontend UI/UX implementations for modern websites. Ensure all code outputs strictly diverge from generic, template-like "AI aesthetics" by prioritizing intentional typography, modern color geometry, and robust interaction states:
1. **Universal Layout & Composition:**
   - **Asymmetry & Grid Systems:** Use CSS Grid and Flexbox dynamically. Break out of rigid, repetitive card layouts. Utilize proportional, content-driven spacing instead of arbitrary hardcoded widths.
   - **Visual Anchors:** Ensure a clear typographical hierarchy. Emphasize section entries with distinctive header treatments or bold font sizing.
   - **The Overuse Ban:** Avoid generic full-bleed gradients, neon glowing card borders, and center-aligned paragraphs for core content blocks.
2. **Modern Color Engineering & Design Tokens:**
   - **OKLCH Color Space:** Implement design systems using modern OKLCH colors for uniform perceptual brightness across themes.
   - **Intentional Palettes:** Default to an editorial, minimal palette (deep charcoal, soft off-whites, neutral slate) paired with exactly one distinct accent color used sparingly for active elements or branding.
   - **Contrast Compliance:** Strictly adhere to WCAG 2.2 AAA text-to-background contrast requirements. Never use light grey text on white backgrounds or deep blue on black.
3. **Advanced UI Elements & Dynamic States:**
   - **Top-Layer Animations:** When implementing modals, drawers, or slide-outs, use modern CSS properties (`@starting-style`, `transition-behavior: allow-discrete`, and `overlay`) to ensure seamless transition tracking when elements enter and leave the DOM.
   - **Component Libraries / Standardized Tokens:** Utilize standardized UI design tokens and primitives.
   - **State Coverage:** Every interactive component must natively define styles for six core states: `:hover`, `:focus-visible`, `:active`, `[disabled]`, loading skeleton placeholders, and error-handling conditions.
4. **Accessibility (a11y) & Performance Execution:**
   - **Semantic HTML:** Use structural landmarks natively (`<main>`, `<nav>`, `<aside>`, `<header>`, `<footer>`). Never stack nested `<div>` tags if semantic options exist.
   - **Keyboard Operability:** Ensure all custom interactive components can be navigated via keyboard focus logic with visible element boundaries (`outline: 2px solid var(--accent)`).
   - **Responsive Layouts:** Establish fluid-type systems and media queries bounded by clean, standardized logical breakpoints (`sm`, `md`, `lg`, `xl`). Prevent component truncation or awkward vertical text breaks on standard screens.

**Code quality:** meaningful names; short comments explaining the business rule at the top of each service method; no logic inside views other than loops and display; each controller method does: check role -> validate -> call service -> redirect/render.

**Chapter 5 support:** At the top of the main code for each module, add a comment `// CHAPTER 5.3.x - <module name>` (5.3.1 Authentication, 5.3.2 Venue Management, 5.3.3 Booking and Availability, 5.3.4 Pricing Rules, 5.3.5 Payment Verification, 5.3.6 Confirmation and Receipt, 5.3.7 Reporting) so I can easily cite short code snippets in my documentation.

---

## 9. BUILD PHASES (STOP AFTER EACH ONE)

| Phase | Build | Acceptance check |
|---|---|---|
| **1** | Folder structure, `coh_pms.sql`, `seed_data.sql`, config files, PDO connection, router, base controller, helpers (CSRF, flash, validation, format) | Import SQL in phpMyAdmin with no errors; home page loads; DB connection test passes |
| **2** | Registration, login, logout, role-based redirect to the right dashboard, 403/404 pages, layouts and CSS base | Customer registers and logs in; wrong role gets 403; logout works |
| **3** | Administrator: dashboard, user management (create staff, activate/deactivate) | Admin creates a Booking Officer who can log in |
| **4** | Venue management with image upload; customer venue list and details | Staff add/edit venue; customer sees it |
| **5** | AvailabilityService + AJAX check endpoint | Overlapping slot is refused; adjacent slot is allowed; past dates refused |
| **6** | Pricing rules CRUD + PricingService + live charge on booking form | Seeded example gives US$90, 120, 85, 95, 100 |
| **7** | Booking creation (transaction, reference number), my bookings, cancel; Booking Officer review (approve/reject/update/cancel) with status-transition checks | Full Pending -> Approved/Rejected flow works; illegal transitions blocked |
| **8** | Payment submission, Revenue Officer verification, receipt number, booking -> Confirmed, printable confirmation and receipt page | Verified payment makes booking Confirmed and shows receipt |
| **9** | Reports (4 types), filters, CSV export, Council Management view, role dashboards with summary counts | Report totals match the seeded data |
| **10** | Final review: security checklist (Section 8), responsive check, README, test-case table (below), and a list of pages to screenshot for Chapter 5.4 | All test cases pass |

**Test-case table to produce in Phase 10** (Test ID, Module, Input, Expected result, Actual result, Pass/Fail), with at least: valid/invalid login; duplicate email; double-booking attempt; past date; end before start; each pricing rule; payment amount mismatch; unauthorised page access by each role; CSRF token missing; SQL-injection string in a text box; report totals vs. seed data; CSV export opens in Excel.

**Seed data (Phase 1):** 1 Administrator `admin@coh.co.zw` / `Admin@123`, 1 Booking Officer, 1 Revenue Officer, 1 Council Management, 3 Customers (all with known test passwords listed in README), 6 venues of different types (realistic Harare-style names and locations), the 5 pricing rules from Section 6.2, and about 12 bookings and 8 payments covering every status.

---

## 10. FINAL DELIVERABLES

1. Complete project in the exact folder structure above.
2. `database/coh_pms.sql` and `database/seed_data.sql`.
3. `Readme.md`: Laragon setup (copy folder to `C:\laragon\www\`, create DB, import SQL, edit `config/database.php`, open the URL), default accounts, how to run the test cases, and a short folder-by-folder explanation.
4. A short `docs/System Testing.md` containing the test-case table.
5. A `docs/System Screenshots.md` listing every page to capture for Chapter 5.4 in this order: Home, Register/Login, Customer venue and booking pages, Booking status/confirmation/receipt, Booking Officer dashboard, Revenue Officer payment verification, Administrator venue and pricing rule pages, Management reports dashboard.

**Begin now with Phase 1 only.** Before writing code, repeat back in 5 lines what you will build in Phase 1 and ask me any question you truly need answered. Otherwise start.
