# City of Harare Property Management System (COH-PMS)

COH-PMS is a web system for hiring City of Harare council venues such as community halls, community centres, stadiums and open spaces. It was built as a final-year project for the National Diploma in Information and Communication Technology.

Venue hire at the council has relied on paper registers and visits to the office. That leads to double bookings, prices that differ from one clerk to the next, slow approvals and reports that take days to put together. COH-PMS moves the whole process online, from the first enquiry to the printed receipt.

## Project objectives

1. Provide a secure web-based venue management system.
2. Adjust venue prices automatically using the approved pricing rules.
3. Produce management reports on facility usage and verified revenue.

## What the system does

Customers register, browse venues, pick a date and time, and see whether the slot is free and what it will cost before they submit. After a booking officer approves the request, the customer records a payment. A revenue officer checks the payment, and the booking is then confirmed and a receipt can be printed.

Council staff use the same system to manage venues, set pricing rules, review bookings, verify payments and run reports. Five roles are supported: Administrator, Booking Officer, Revenue Officer, Council Management and Customer.

Payments are recorded and verified by hand. There is no online payment gateway, and the system does not send email or SMS.

## Technology

| Part | Choice |
|---|---|
| Backend | PHP 8.1 or later with PDO, written as controllers, services and models |
| Database | MySQL or MariaDB, database name `coh_pms`, character set `utf8mb4` |
| Frontend | HTML5, CSS3 and plain JavaScript |
| Libraries | None. No jQuery, React, Bootstrap or external CDN, so it runs offline |
| Server | Laragon on Windows (Apache, PHP, MySQL, phpMyAdmin) |
| Structure | One front controller, `public/index.php`, with routes written as `?r=controller/action` |

## Installation on Laragon

1. Copy the `coh-pms` folder into Laragon's web root, for example `C:\laragon\www\coh-pms`.
2. Start Laragon and click Start All so that Apache and MySQL are running.
3. Open phpMyAdmin at `http://localhost/phpmyadmin` and import `database/coh_pms.sql`. This creates the `coh_pms` database and its five tables: `users`, `venues`, `pricing_rules`, `bookings` and `payments`.
4. Import `database/seed_data.sql` once. It adds the test accounts, six venues, the 2026 and 2027 pricing rules, and a set of sample bookings and payments.
5. Open `config/database.php` and check that the connection details match your MySQL setup. The defaults are host `127.0.0.1`, database `coh_pms`, user `root` and an empty password.
6. Open the site at `http://coh-pms.test` if Laragon's automatic virtual hosts are on, or at `http://localhost/coh-pms/public` otherwise.

Warning: `coh_pms.sql` starts with `DROP DATABASE IF EXISTS coh_pms`. Importing it a second time deletes every record, including bookings you have made while testing. Import it once, and only run it again when you want a clean start.

### Fonts

The interface uses the Poppins font, loaded from local files. Place `Poppins-Regular`, `Poppins-Medium`, `Poppins-SemiBold` and `Poppins-Bold` (`.woff2` or `.ttf`) in `public/assets/fonts/`. If the files are missing, the pages still work and the browser falls back to a standard system font.

## Test accounts

The seed file creates one account for each role. Passwords follow the system rule of at least 8 characters with a letter and a digit, and are stored only as bcrypt hashes.

| Role | Email | Password |
|---|---|---|
| Administrator | admin@coh.co.zw | Admin@123 |
| Booking Officer | booking.officer@coh.co.zw | Booking@123 |
| Revenue Officer | revenue.officer@coh.co.zw | Revenue@123 |
| Council Management | management@coh.co.zw | Management@123 |
| Customer 1 | customer1@coh.co.zw | Customer@123 |
| Customer 2 | customer2@coh.co.zw | Customer@123 |
| Customer 3 | customer3@coh.co.zw | Customer@123 |

What each role can do:

- Administrator: manages staff accounts, venues and pricing rules, and can see every report.
- Booking Officer: manages venues and reviews booking requests. Can see the booking and usage reports.
- Revenue Officer: verifies or rejects payments and sees the payment and revenue reports.
- Council Management: read-only access to all reports, including CSV export.
- Customer: browses venues, books, pays, cancels where allowed and prints receipts.

## How a booking moves through the system

1. The customer chooses a venue, a date and a start and end time. The page checks availability and shows the price while the form is being filled in.
2. The customer submits the request and the booking is saved as Pending.
3. A Booking Officer or Administrator either rejects it, which ends the booking as Rejected, or approves it.
4. Once a booking is Approved, the customer records a payment. The payment is marked Pending Verification, and the amount comes from the booking, not from the form.
5. A Revenue Officer or Administrator checks the payment. If it is rejected, the booking stays Approved and the customer can pay again. If it is verified, the payment is marked Verified, a receipt number is issued and the booking becomes Confirmed.
6. After the event date has passed, the booking can be marked Completed.

A customer may cancel a booking while it is Pending, or while it is Approved and no payment has been made. Every booking has a reference in the form `COH-YYYYMMDD-XXXX`, and every receipt has a number in the form `RCT-YYYYMMDD-XXXX`.

A slot counts as taken when another booking for the same venue and date is Pending, Approved or Confirmed and its times overlap. The availability check and the save happen in one database transaction, so two customers cannot book the same slot at the same moment. Bookings that finish exactly when another begins are allowed.

## Pricing rules

Every venue has a standard price per booking. Pricing rules then adjust it. A rule has a type (discount or surcharge), a percentage or fixed amount, the venue type it applies to, a date range, optional weekdays, an optional minimum number of hours, a priority and a status.

For each booking the system finds the active rules that match, keeps only the one with the highest priority, and applies it. If two rules share a priority, the one with the lower rule number wins. Rules are never added together, and a fixed discount can never take the total below zero. Only the Administrator can create or change rules.

Examples for a venue with a standard price of US$100:

- Weekday Discount, 10 percent off from Monday to Thursday: US$90
- Weekend Surcharge, 20 percent extra from Friday to Sunday: US$120
- Long Booking, 5 percent off for bookings of 8 hours or more: US$95
- No matching rule: US$100

## Reports

There are four reports, each with date, venue and status filters where they apply, and each can be exported as CSV.

- Bookings: every booking with customer, venue, date, time, status and charge.
- Facility usage: booked hours per venue compared with available hours, counting Approved, Confirmed and Completed bookings. Available hours are taken as 12 per day over the selected dates. Venues with no bookings still appear with zero.
- Payments: every recorded payment and its status.
- Verified revenue: the total of Verified payments only. Pending, rejected and unpaid bookings are not counted.

## Running the automated tests

The tests run from the command line and need no extra tools. Open Laragon's terminal in the project folder and run:

```powershell
php tests\logic_test.php
php tests\suite_test.php
php tests\f7_security_test.php
```

`logic_test.php` has 8 checks of the pricing rules and the priority tie-break. `suite_test.php` has 20 checks covering passwords, routing, booking status changes, pricing, and the reference and receipt formats. `f7_security_test.php` has 12 checks on password rules, file upload inspection and the router's protections. All 40 should pass.

These tests do not use the database. The booking, payment and report workflows are checked by hand using the test cases in `docs/TESTING.md`.

## Security measures

- Passwords are hashed with `password_hash()` and checked with `password_verify()`.
- Five failed logins for the same email lock login for five minutes.
- The session ID is renewed at login, and the account is re-checked on every request, so a deactivated user loses access straight away.
- Every form has a CSRF token, every database query uses prepared statements, and all output is escaped.
- Role checks run on the server for every protected action. A wrong role gets a 403 page.
- Uploaded venue photos are checked by their real file type, renamed, and stored in a folder where PHP cannot run.
- The router accepts only known controller and action names, and the front controller sets security headers.

## Folder structure

```text
coh-pms/
  app/
    controllers/   Request handlers for each area of the system
    core/          Router and the base controller with login and role checks
    helpers/       Small functions for sessions, CSRF, messages, formatting, validation and uploads
    models/        Database queries using PDO
    services/      Business rules: booking, availability, pricing, payment and reports
  config/          Settings, constants and the database connection
  database/        coh_pms.sql (schema) and seed_data.sql (test data)
  docs/            Specification, test plan, screenshot list and change log
  public/          The only folder Apache serves
    assets/        CSS, JavaScript and fonts
    uploads/       Venue photographs
    index.php      Front controller
  tests/           Command-line test scripts
  views/           Page templates for the public site, customers and staff
  AGENTS.md        Instructions for AI coding tools used on the project
  README.md        This file
```

The `.htaccess` file in the project root blocks direct web access to `app`, `config`, `database`, `docs`, `tests` and `views`.

## Known limits

- Payments are entered and verified by staff. There is no payment gateway.
- No email or SMS notifications are sent.
- The login lock is stored in the session, so it slows down guessing but does not stop someone who clears their cookies.
- A booking is marked Completed by staff after the event date, not automatically.

## Troubleshooting

- Blank page or database error: check that MySQL is running in Laragon and that the details in `config/database.php` are correct.
- Page not found on every link: open the site through `public`, either `http://coh-pms.test` or `http://localhost/coh-pms/public`, and make sure Apache's rewrite module is enabled.
- Venue image will not upload: the file must be a JPG, PNG or WebP of 2 MB or less, and `public/uploads/venues/` must be writable.
- Login says the credentials are wrong after several attempts: wait five minutes, then try again.
- Prices show no discount: pricing rules have dates. Check that the booking date falls inside a rule's start and end dates.

  [![Architecture diagram](https://gitdiagram.com/diagram-badge.svg)](https://gitdiagram.com/ybmprod/coh-pms?utm_source=readme&utm_medium=badge)
