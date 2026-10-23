# COH-PMS System Architecture & Schema Changes

**Document:** Dissertation Chapter 4 Alignment Guide (Data Dictionary, ERD, Class Diagram, Flowcharts)  
**Project:** City of Harare Property Management System (COH-PMS)  
**Date:** October 2026  

This document details every schema enhancement, architectural restructuring, route modification, and behavioural rule implemented across the project. Use this reference to ensure complete synchronization between your code implementation and Chapter 4 (System Design) of your final national diploma project documentation.

---

## 1. Database Schema & Data Dictionary Changes

The relational database schema is finalized across 5 normalized tables in `database/coh_pms.sql`:

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

### 1.3 `pricing_rules` Table
- **Rule Type ENUM:** `'Weekday Discount'`, `'Weekend Surcharge'`, `'Off-Peak Discount'`, `'Long Booking Discount'`, `'Peak Demand Surcharge'`.
- **Adjustment Type ENUM:** `'Percentage'`, `'Fixed Amount'` (Default: `'Percentage'`).
- **Days of Week:** `days_of_week` VARCHAR(30) comma-separated format (`Mon,Tue,Wed,Thu`).
- **Duration Threshold:** `min_hours` DECIMAL(4,1) used exclusively for long booking qualification.
- **Priority Engine:** `priority` INT (Default: `1`). Higher numbers take precedence; identical priorities break ties by lowest `pricing_rule_id`.
- **Status ENUM:** `'Active'`, `'Inactive'` (Default: `'Active'`).

### 1.4 `bookings` Table
- **Booking Reference:** `booking_reference` VARCHAR(20) UNIQUE format `COH-YYYYMMDD-XXXX`. Calculated transactionally to prevent duplicate references.
- **Charge Breakdown Fields:**
  - `standard_charge` DECIMAL(10,2) (snapshots venue standard price)
  - `adjustment_amount` DECIMAL(10,2) (negative for discounts, positive for surcharges)
  - `total_charge` DECIMAL(10,2) (standard + adjustment; never below US$0.00)
- **Status ENUM:** `'Pending'`, `'Approved'`, `'Rejected'`, `'Cancelled'`, `'Confirmed'`, `'Completed'`.
- **Audit Columns:** `reviewed_by` INT FK to `users(user_id)`.
- **Performance Indexes:**
  - `idx_bookings_venue_date` on `(venue_id, booking_date, start_time, end_time)`
  - `idx_bookings_customer` on `(customer_id)`
  - `idx_bookings_status` on `(booking_status)`

### 1.5 `payments` Table
- **Amount Enforcement:** `amount_paid` DECIMAL(10,2) strictly validated against `bookings.total_charge`.
- **Payment Method ENUM:** `'Cash'`, `'Bank Transfer'`, `'Mobile Money'`, `'Card'`.
- **Receipt Number:** `receipt_number` VARCHAR(30) UNIQUE format `RCT-YYYYMMDD-XXXX`, generated transactionally upon payment verification.
- **Status ENUM:** `'Pending Verification'`, `'Verified'`, `'Rejected'` (Default: `'Pending Verification'`).
- **Audit Columns:** `verified_by` INT FK to `users(user_id)` and `verification_date` DATETIME.
- **Indexes & Foreign Keys:**
  - `fk_payments_booking` on `booking_id` with `ON DELETE CASCADE ON UPDATE CASCADE`.
  - `idx_payments_status` on `(payment_status)`.

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
   - Evaluates active rules matching venue type, date range, day of week, and duration.
   - Applies the single rule with highest priority; never stacks multiple adjustments.
   - Calculates percentage or fixed adjustments, clamping final charge to zero.
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
| `booking/checkAvailability` | GET | Authenticated | Live AJAX endpoint returning slot availability and calculated pricing breakdown. |
| `booking/cancel` | POST | Customer | Customer self-cancellation for own `Pending` or unpaid `Approved` bookings. |
| `booking/details` | GET | Customer, Staff | Detailed view of booking specifications, charge breakdown, and payment status. |
| `payment/submit` | POST | Customer | Customer payment submission for `Approved` bookings. |
| `payment/verify` | POST | Admin, Revenue Officer | Verifies submitted payment, generates receipt, and advances booking to `Confirmed`. |
| `payment/reject` | POST | Admin, Revenue Officer | Rejects invalid payment with audit trail, keeping booking open for re-payment. |
| `payment/receipt` | GET | Customer, Staff | Official printable municipal booking confirmation and payment receipt. |
| `report/exportCsv` | GET | Admin, Booking, Revenue, Mgmt | Exports filtered report datasets to clean CSV. |

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
