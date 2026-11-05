# COH-PMS System Architecture & Schema Changes

**Document:** Dissertation Chapter 4 Alignment Guide (Data Dictionary, ERD, Class Diagram, Flowcharts)  
**Project:** City of Harare Property Management System (COH-PMS)  
**Date:** October 2026  

This document details every schema enhancement, architectural restructuring, route modification, and behavioural rule implemented across the project. Use this reference to ensure complete synchronization between your code implementation and Chapter 4 (System Design) of your final national diploma project documentation.

---

## 1. Database Schema & Data Dictionary Changes

The relational database schema is finalized across 8 normalized tables in `database/coh_pms.sql`:

### 1.1 `users` Table
- **Role Field ENUM:** `'Customer'`, `'Booking Officer'`, `'Revenue Officer'`, `'Administrator'`, `'Council Management'`.
  - *Design Note:* Added the `'Council Management'` role to support read-only executive reporting without operational write permissions.
- **Account Status ENUM:** `'Active'`, `'Inactive'` (Default: `'Active'`).
  - *Design Note:* Users are never deleted from the database if foreign key references exist; accounts are deactivated instead.
- **Unique Key:** `uq_users_email` on `email`.

### 1.2 `venues` Table
- **Pricing Basis:** `standard_price` DECIMAL(10,2) represents the price **per booking day/session**, not an hourly rate.
- **Image Storage:** `image_path` stores only the relative randomized filename (e.g. `a1b2c3...webp`), resolved via `venue_image_url()`.
- **Venue Status ENUM:** `'Available'`, `'Unavailable'`, `'Under Maintenance'` (Default: `'Available'`).
- **Facilities & Description:** `facilities` VARCHAR(255) and `description` TEXT capture physical amenities (seating, PA system, lighting).

### 1.3 `event_types` Table
- **Catalogue:** `event_name` VARCHAR(100) UNIQUE, e.g. Wedding, Community Meeting, Sports Event.
- **Charge Type ENUM:** `'Fixed Amount'`, `'Percentage'`. A percentage is calculated on the venue standard charge.
- **Charge Value:** `charge_value` DECIMAL(10,2), added to the booking total independently of pricing-rule adjustments.
- **Status ENUM:** `'Active'`, `'Inactive'` (Default: `'Active'`). Inactive types remain on historical bookings but are hidden from new bookings.
- **Relationship:** `bookings.event_type_id` references this table with `ON DELETE SET NULL`.
- **Venue Suitability:** each event type is mapped to realistic venue types through `event_type_venue_types`; the booking form warns about mismatches and `BookingService` rejects them.
- **Seed Coverage:** 42 realistic event types, including zero-charge civic categories, charged commercial categories, and alternative wordings. Each description explains in plain English what happens during that type of event.

### 1.4 `event_type_venue_types` Table
- **Purpose:** Stores the suitability mapping between event types and venue types so invalid combinations (for example, Football Match in a Community Hall) are blocked before booking.
- **Composite Key:** `(event_type_id, venue_type)` prevents duplicate mappings.
- **Venue Type ENUM:** `'Community Hall'`, `'Community Centre'`, `'Stadium'`, `'Open Space'`, `'Other'`, matching `venues.venue_type`.
- **Foreign Key:** `event_type_id` references `event_types(event_type_id)` with `ON DELETE CASCADE ON UPDATE CASCADE`, indexed as `idx_event_type_venue_type`.
- **Fallback Rule:** Event types with no mapping are treated as suitable for every venue type, so custom catalogue entries remain bookable.
- **Enforcement:** `EventType::isVenueCompatible()` and `BookingService` validate every booking server-side; the customer form warning and suitable-venue filter are conveniences only.

### 1.5 `pricing_rules` Table
- **Rule Type ENUM:** `'Weekday Discount'`, `'Weekend Surcharge'`, `'Off-Peak Discount'`, `'Long Booking Discount'`, `'Peak Demand Surcharge'`.
- **Adjustment Type ENUM:** `'Percentage'`, `'Fixed Amount'` (Default: `'Percentage'`).
- **Days of Week:** `days_of_week` VARCHAR(30) comma-separated format (`Mon,Tue,Wed,Thu`).
- **Duration Threshold:** `min_hours` DECIMAL(4,1) used exclusively for long booking qualification.
- **Priority Engine:** `priority` INT (Default: `1`). Higher numbers take precedence; identical priorities break ties by lowest `pricing_rule_id`.
- **Status ENUM:** `'Active'`, `'Inactive'` (Default: `'Active'`).

### 1.6 `bookings` Table
- **Booking Reference:** `booking_reference` VARCHAR(20) UNIQUE format `COH-YYYYMMDD-XXXX`. Calculated transactionally to prevent duplicate references.
- **Event Type Link:** `event_type_id` INT NULL FK to `event_types(event_type_id)` with `ON DELETE SET NULL`, indexed as `idx_bookings_event_type`.
- **Charge Breakdown Fields:**
  - `standard_charge` DECIMAL(10,2) (snapshots venue standard price)
  - `event_type_charge` DECIMAL(10,2) (snapshotted event-type surcharge)
  - `adjustment_amount` DECIMAL(10,2) (negative for discounts, positive for surcharges)
  - `total_charge` DECIMAL(10,2) (standard + event-type charge + adjustment; never below US$0.00)
- **Status ENUM:** `'Pending'`, `'Approved'`, `'Rejected'`, `'Cancelled'`, `'Confirmed'`, `'Completed'`.
- **Audit Columns:** `reviewed_by` INT FK to `users(user_id)`.
- **Performance Indexes:**
  - `idx_bookings_venue_date` on `(venue_id, booking_date, start_time, end_time)`
  - `idx_bookings_customer` on `(customer_id)`
  - `idx_bookings_status` on `(booking_status)`

### 1.7 `payments` Table
- **Amount Enforcement:** `amount_paid` DECIMAL(10,2) strictly validated against `bookings.total_charge`.
- **Payment Method ENUM:** `'Cash'`, `'Bank Transfer'`, `'Mobile Money'`, `'Card'`.
- **Receipt Number:** `receipt_number` VARCHAR(30) UNIQUE format `COH-PMS-VER-XXXXXX`, generated transactionally upon payment verification.
- **Status ENUM:** `'Pending Verification'`, `'Verified'`, `'Rejected'` (Default: `'Pending Verification'`).
- **Audit Columns:** `verified_by` INT FK to `users(user_id)` and `verification_date` DATETIME.
- **Indexes & Foreign Keys:**
  - `fk_payments_booking` on `booking_id` with `ON DELETE CASCADE ON UPDATE CASCADE`.
  - `idx_payments_status` on `(payment_status)`.

### 1.8 `password_reset_tokens` Table
- **Purpose:** Supports customer-initiated password changes without altering `users.account_status` or storing a plaintext token in the user row.
- **Token Storage:** `token_hash` CHAR(64) UNIQUE stores the SHA-256 hash of a 64-character random token. The plain token exists only in the emailed link.
- **Expiry:** `expires_at` DATETIME is set to 60 minutes after issue; expired rows never resolve.
- **Single Use:** `used_at` marks consumption atomically inside a transaction that also replaces `users.password_hash` and invalidates every other outstanding token for that account.
- **Throttling:** A new token is refused when one was issued for the same account within the previous 60 seconds.
- **Request Context:** `requested_ip` VARCHAR(45) stores the requester IP for audit purposes only.
- **Indexes & Foreign Keys:**
  - `uq_password_reset_token_hash` on `(token_hash)`.
  - `idx_password_reset_user` on `(user_id)`.
  - `idx_password_reset_expiry` on `(expires_at)`.
  - `fk_password_reset_user` on `user_id` with `ON DELETE CASCADE ON UPDATE CASCADE`.

### 1.9 Row-Filtered Customer Views

MySQL/MariaDB do not offer native row-level security. The customer portal therefore reads through three read-only views created by `database/migrations/2026_11_02_add_row_level_security_views.sql`:

| View | Purpose | Filter |
|---|---|---|
| `v_customer_bookings` | Customer booking list, details and amendment form | `bookings.customer_id = coh_current_customer_id()` |
| `v_customer_payments` | Latest payment for a customer booking | joined `bookings.customer_id` |
| `v_customer_receipts` | Verified receipt with venue, pricing rule and verifier names | joined `bookings.customer_id` |

- `config/database.php` exports `@coh_pms_customer_id`, `@coh_pms_actor_id` and `@coh_pms_actor_role` on every new PDO connection.
- `coh_current_customer_id()` returns `COALESCE(CAST(@coh_pms_customer_id AS UNSIGNED), 0)`, so a connection without an authenticated customer sees no rows.
- Customer read models call `get_db_connection('customer_read')`. When `DB_USER_CUSTOMER` is set, that connection uses the optional views-only account from `database/security/least_privilege_accounts.sql.example`.
- The views are defence in depth only. Controllers and services still verify ownership and role server-side, because any account able to execute arbitrary SQL could set the session variable.

---

## 2. Architectural Restructuring (Service Layer Introduction)

In accordance with good software engineering practices, business logic has been extracted from controllers and database models into an isolated **Services Layer** in `app/services/`:

```text
               ┌───────────────────────┐
               │  public/index.php     │ (Front Controller & Dispatch Guards)
               └──────────┬────────────┘
                          │
               ┌──────────▼────────────┐
               │    Controllers        │ (HTTP Validation, Role Guards, Session Auth)
               └──────────┬────────────┘
                          │
               ┌──────────▼────────────┐
               │     Services Layer    │ (Domain Logic, Pricing, State Machines)
               │ - BookingService      │
               │ - AvailabilityService │
               │ - PricingService      │
               │ - PaymentService      │
               │ - ReportService       │
               └──────────┬────────────┘
                          │
               ┌──────────▼────────────┐
               │     Models (PDO)      │ (Prepared SQL Queries Only)
               └───────────────────────┘
```

1. **`BookingService`:**
   - Enforces the strict booking state transition matrix.
   - Prohibits direct manual transitions to `Confirmed` by staff (confirmation is exclusively triggered upon payment verification).
   - Manages atomic customer cancellations for eligible bookings.
2. **`AvailabilityService`:**
   - Evaluates slot conflicts using the standard interval intersection rule: `(new_start < existing_end AND new_end > existing_start)`.
   - Filters out past dates, end times earlier than start times, and capacity overages.
3. **`PricingService`:**
   - Adds the selected event-type charge (fixed or percentage of the standard charge) before applying rule adjustments.
   - Evaluates active rules matching venue type, date range, day of week, and duration.
   - Applies the single rule with highest priority; never stacks multiple adjustments.
   - Calculates percentage or fixed adjustments, clamping the final total to zero.
4. **`PaymentService`:**
   - Validates that payment submissions match the approved booking total.
   - Executes payment verification and receipt number generation in a single atomic database transaction.
5. **`ReportService`:**
   - Computes usage metrics with decimal hour precision (`TIMESTAMPDIFF(MINUTE) / 60`).
   - Restricts revenue calculations strictly to `Verified` payments.
   - Generates CSV exports equipped with UTF-8 BOM and formula injection mitigation.

---

## 3. Route Modifications & New Endpoints

| Route (`?r=...`) | Method | Authorized Roles | Description / Function |
|---|---|---|---|
| `booking/checkAvailability` | GET | Authenticated | Live AJAX endpoint returning slot availability and calculated pricing breakdown; an optional `exclude_booking_id` ignores the customer's own `Pending` booking during amendment. |
| `booking/cancel` | POST | Customer | Customer self-cancellation for own `Pending` or unpaid `Approved` bookings. |
| `booking/details` | GET | Customer, Staff | Detailed view of booking specifications, charge breakdown, and payment status. |
| `booking/edit` | GET | Customer (owner) | Loads the amendment form for the customer's own `Pending` booking. |
| `booking/update` | POST | Customer (owner) | Re-runs availability and pricing transactionally and saves changes to a `Pending` booking. |
| `payment/submit` | POST | Customer | Customer payment submission for `Approved` bookings. |
| `payment/verify` | POST | Admin, Revenue Officer | Verifies submitted payment, generates receipt, and advances booking to `Confirmed`. |
| `payment/reject` | POST | Admin, Revenue Officer | Rejects invalid payment with audit trail, keeping booking open for re-payment. |
| `payment/receipt` | GET | Customer, Staff | Official printable municipal booking confirmation and payment receipt. |
| `report/exportCsv` | GET | Admin, Booking, Revenue, Mgmt | Exports filtered report datasets to clean CSV. |
| `auth/forgotPassword` | GET, POST | Public | Requests a single-use password-reset link; always returns a generic response to avoid account enumeration. |
| `auth/changePassword` | GET, POST | Authenticated Customer | Shows the account-security page and emails a reset link after verifying the current password. |
| `auth/resetPassword` | GET, POST | Public token holder | Validates the emailed token and atomically replaces the customer password. |

---

## 4. Behavioural & Security Refinements

1. **Active Session Re-verification:**
   - `Controller::requireAuth()` re-fetches the user row from the database on each request. If an account is deactivated by an administrator, their next action destroys their session and redirects to the login screen.
2. **Brute Force Protection:**
   - Login attempts are tracked in the session. After 5 failed attempts, the email/session is locked for 300 seconds (5 minutes).
3. **Password Policy:**
   - New accounts require a minimum of 8 characters, at least one letter, and at least one digit (`validate_password()`).
4. **Upload Security:**
   - Uploaded venue images are validated using `finfo` binary MIME inspection (allowing only `image/jpeg`, `image/png`, `image/webp`).
   - Image files are stored under randomized names in `public/uploads/venues/`.
   - Direct execution of `.php`, `.phtml`, or `.phar` scripts within the uploads folder is denied by Apache configuration.
5. **Route Reflection Guard:**
   - Only public methods declared on the specific controller class may be dispatched. Base controller methods (`render`, `redirect`) and non-existent actions return HTTP 404.
6. **Unhandled Exception Handling:**
   - Uncaught exceptions log the full trace to the server PHP error log and render a user-friendly `views/errors/500.php` page without exposing system paths.
7. **Customer Password Reset:**
   - Tokens are 64-character random values stored only as SHA-256 hashes, expire after 60 minutes, are single-use and invalidate any earlier token for the account.
   - Requests are throttled per account, public responses are identical for known and unknown emails, and eligible accounts must be active customers.
   - Authenticated requests require the current password. Successful resets clear the token and update the bcrypt hash inside a transaction.
   - Development mail uses `storage/logs/mail.log`; production uses PHP `mail()` only when SMTP is configured.
8. **Sign-In CSRF Exception and Cookie Resilience (2 November 2026):**
   - One session cookie name (`coh_pms_session`); scheme-specific suffixes were removed in the November 2026 simplification.
   - The CSRF token is mirrored in the host-only, `HttpOnly` `coh_pms_csrf` cookie. `verify_csrf_token()` accepts either the session token or the cookie token; there is no separate same-origin check.
   - `X-Forwarded-Proto` is not trusted; cookie flags follow the real request scheme.
   - `AuthController::handleLogin()` no longer verifies CSRF. Credentials, account status, portal role, lockout, per-IP limits and audit logging are unchanged. Every other POST action still requires CSRF.
