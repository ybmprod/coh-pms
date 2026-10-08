# City of Harare Property Management System (COH-PMS)

A secure, web-based venue hire and property management system for the City of Harare municipal council. Developed as a final-year National Diploma in Information and Communication Technology (ICT) project.

---

## 1. Project Overview

The **City of Harare Property Management System (COH-PMS)** automates and streamlines council venue hiring for community halls, community centres, stadia, and open spaces across Harare. It directly addresses the shortcomings of paper registers and manual office visits (double bookings, inconsistent pricing, slow processing, and weak reporting) through three core project objectives:

- **OBJ 1:** Implement a secure web-based venue management system.
- **OBJ 2:** Automatically adjust property prices using approved municipal pricing rules.
- **OBJ 3:** Produce comprehensive management reports on facility usage and verified revenue.

### Technology Stack
- **Backend:** Plain PHP 8.1+ (procedural helpers, object-oriented controllers, services, and models) using PDO.
- **Database:** MySQL / MariaDB (`coh_pms`, charset `utf8mb4`).
- **Frontend:** HTML5, CSS3, vanilla JavaScript (no jQuery, no React, no Bootstrap, no external CDNs; works completely offline).
- **Environment:** Laragon (Apache, PHP 8.1+, MySQL, phpMyAdmin) on Windows.
- **Architecture:** Lightweight MVC-Service pattern with single front controller (`public/index.php`) and query router (`?r=controller/action`).

---

## 2. Laragon Setup & Installation

### Step 1: Place Project in Web Root
Copy or clone the `coh-pms` directory into your Laragon `www` directory:
```text
C:\laragon\www\coh-pms
```
*(Or your custom Laragon drive location, e.g. `D:\...\Laragon\www\coh-pms`)*

### Step 2: Start Laragon Services
Launch Laragon and click **Start All** to ensure Apache and MySQL are running.

### Step 3: Create Database & Import Schema
1. Open **phpMyAdmin** from Laragon (`http://localhost/phpmyadmin`) or MySQL CLI.
2. Import the schema file first:
   ```text
   database/coh_pms.sql
   ```
   *(This creates the `coh_pms` database and the 5 relational tables: `users`, `venues`, `pricing_rules`, `bookings`, `payments`)*
3. Import the seed data file once:
   ```text
   database/seed_data.sql
   ```
   *(Populates staff, test customers, municipal venues, 2026–2027 pricing rules, and representative bookings/payments)*

### Step 4: Verify Database Configuration
Check `config/database.php` to ensure the database connection parameters match your Laragon MySQL setup:
```php
$host = '127.0.0.1';
$dbName = 'coh_pms';
$dbUser = 'root';
$dbPass = '';
$charset = 'utf8mb4';
```

### Step 5: Access the Web Application
Open your browser and navigate to either:
- Virtual Host (if Laragon auto-virtual hosts are active): `http://coh-pms.test`
- Standard Localhost URL: `http://localhost/coh-pms/public`

---

## 3. Seeded Test Accounts

The seed file provides pre-configured accounts representing all system user roles. Passwords satisfy the system policy: minimum 8 characters with at least one letter and one digit. In accordance with security standards, the database stores only one-way bcrypt password hashes (`PASSWORD_DEFAULT`).

| Role | Email Address | Plain Test Password | Description & Permissions |
|---|---|---|---|
| **Administrator** | `admin@coh.co.zw` | `Admin@123` | Full administrative control: manage staff accounts, venues, pricing rules, and view all reports. |
| **Booking Officer** | `booking.officer@coh.co.zw` | `Booking@123` | Manages venues, reviews customer booking requests (Approve, Reject, Cancel, Complete), views usage reports. |
| **Revenue Officer** | `revenue.officer@coh.co.zw` | `Revenue@123` | Verifies or rejects payments, issues official receipts, views payment & revenue reports. |
| **Council Management** | `management@coh.co.zw` | `Management@123` | Read-only executive access to usage, booking, payment, and revenue reports with CSV export. |
| **Customer 1** | `customer1@coh.co.zw` | `Customer@123` | Self-service portal: browses venues, books slots, submits payments, prints receipts. |
| **Customer 2** | `customer2@coh.co.zw` | `Customer@123` | Additional test customer with historical booking records. |
| **Customer 3** | `customer3@coh.co.zw` | `Customer@123` | Additional test customer with historical booking records. |

---

## 4. Folder Structure & Explanation

```text
coh-pms/
├── app/
│   ├── controllers/      # Request handlers (Auth, User, Venue, Pricingrule, Booking, Payment, Report, Dashboard)
│   ├── core/             # Router.php (clean query dispatcher) and Controller.php (base controller with auth/role guards)
│   ├── helpers/          # Procedural utility functions (auth, csrf, flash, format, validation, upload)
│   ├── models/           # Data access objects with prepared PDO queries (User, Venue, PricingRule, Booking, Payment, Report)
│   └── services/         # Encapsulated business logic layer (BookingService, AvailabilityService, PricingService, PaymentService, ReportService)
├── config/
│   ├── config.php        # Session security, cookie parameters, base URL, timezone (Africa/Harare), app constants
│   ├── constants.php     # Role definitions, booking status constants, and payment status constants
│   └── database.php      # PDO database connection factory with ERRMODE_EXCEPTION and EMULATE_PREPARES=false
├── database/
│   ├── coh_pms.sql       # Complete relational database DDL schema (5 tables, indexes, constraints)
│   └── seed_data.sql     # Seed data for users, venues, active rules, bookings, and payments
├── docs/
│   ├── COH-PMS_SPEC.md   # Master system specification
│   ├── TESTING.md        # Comprehensive test matrix with automated and manual test cases
│   ├── SCREENSHOTS.md    # Ordered list of UI pages for Chapter 5.4 dissertation inclusion
│   └── CHANGES.md        # Detailed record of database, routing, and architectural modifications
├── public/               # The single public document root exposed to Apache
│   ├── assets/           # Client-side CSS, JavaScript, and images
│   │   ├── css/style.css # Harare municipal palette styling, responsive layout, status badges, print media rules
│   │   └── js/booking.js # Real-time AJAX availability and live pricing calculation
│   ├── uploads/venues/   # Uploaded venue photographs (protected by .htaccess script execution restriction)
│   ├── .htaccess         # URL rewriting and routing directives
│   └── index.php         # Front controller with security headers, route whitelisting, and reflection method guard
├── tests/
│   ├── logic_test.php    # Objective 2 pricing rule engine verification (percentage, surcharge, off-peak, priority)
│   ├── f7_security_test.php # Security hardening verification (password policy, MIME checks, router reflection guard)
│   └── suite_test.php    # Comprehensive test suite covering business transitions, validation, and security rules
├── views/
│   ├── auth/             # Login and registration templates
│   ├── customer/         # Customer venue browsing, booking request form, booking details, receipt views
│   ├── errors/           # 403 Forbidden, 404 Not Found, and 500 Server Error error pages
│   ├── layouts/          # Reusable layout components (public header, staff header, sidebar, footer)
│   ├── staff/            # Role-specific dashboard, user management, venue CRUD, pricing rules, booking review, payment verification, reports
│   └── home.php          # Welcome landing page
├── .htaccess             # Denies direct web access to app/, config/, database/, docs/, tests/, views/
├── AGENTS.md             # Developer instruction handbook
└── README.md             # Setup guide, credentials, and architectural overview
```

---

## 5. Automated Test Suite Execution

COH-PMS includes automated command-line test suites that execute standalone without external testing frameworks:

1. **Pricing Rule Logic Tests (Objective 2):**
   ```powershell
   php tests/logic_test.php
   ```
   *Verifies Weekday Discount (10% -> US$90), Weekend Surcharge (20% -> US$120), Off-Peak Discount (15% -> US$85), Long Booking (8+ hrs -> US$95), and priority tie-breaking rules.*

2. **Security & Hardening Tests:**
   ```powershell
   php tests/f7_security_test.php
   ```
   *Verifies password complexity enforcement, PHP script upload rejection via MIME sniffing, router traversal protection, and reflection-based method blocking.*

3. **Comprehensive Integrated Suite:**
   ```powershell
   php tests/suite_test.php
   ```
   *Runs all 20 integrated automated checks across security, routing, booking transition state machines, pricing calculations, and reference formatting.*

---

## 6. Official Booking & Payment Lifecycle

```text
Customer selects venue, date, times
  │
  ├─> Live AJAX checks availability & calculates rule-based charge
  │
  └─> Submit Booking  ==>  Status: Pending
                             │
                             ├─[Booking Officer/Admin Reviews]
                             │   ├── Reject  ==>  Status: Rejected
                             │   └── Approve ==>  Status: Approved
                             │                      │
                             │                      ├─[Customer Submits Payment]
                             │                      │   ==>  Payment: Pending Verification
                             │                      │
                             │                      └─[Revenue Officer/Admin Verifies]
                             │                          ├── Reject ==> Payment: Rejected (Booking stays Approved)
                             │                          └── Verify ==> Payment: Verified, Receipt Generated
                             │                                         Booking: Confirmed
                             │                                         (Printable receipt available)
                             │
                             └─[Event date passes]
                                 └── Mark Completed ==> Status: Completed
```

[![Architecture diagram](https://gitdiagram.com/diagram-badge.svg)](https://gitdiagram.com/ybmprod/coh-pms?utm_source=readme&utm_medium=badge)
