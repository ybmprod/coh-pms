# COH-PMS System Test Plan & Test Results

**Project:** City of Harare Property Management System (COH-PMS)  
**Document:** Chapter 5 Verification and Test Matrix  
**Specification:** `docs/COH-PMS_SPEC.md` / `AGENTS.md`  

---

## 1. Testing Methodology

The verification of the City of Harare Property Management System incorporates two testing tiers:
1. **Automated Unit & Rule Tests (CLI):** Validates deterministic business logic, pricing rule algorithms, transaction integrity state transitions, password policies, input escaping, and security dispatch guards via PHP CLI scripts.
2. **Manual Functional & Interface Tests (Browser in Laragon):** Interactive verification of user journeys, CSRF handling, database transactions under simultaneous tab submissions, session lifecycle, and report downloads.

*Per project guidelines, "Actual Result" and "Status" are completed for automated test cases executed by the test engine. Manual browser verification cases are left blank for student execution in Laragon.*

---

## 2. Master Test Matrix

| Test ID | Module | Description | Input / Test Action | Expected Result | Actual Result | Status |
|---|---|---|---|---|---|---|
| **AUT-01** | Security / Auth | Minimum password length | Input password `"Pass1"` (< 8 characters) | Password rejected with `"Password must be at least 8 characters long."` | Password rejected with exact error message | **Pass** |
| **AUT-02** | Security / Auth | Password missing digit | Input password `"PasswordOnly"` (no digits) | Password rejected with `"Password must include at least one digit."` | Password rejected with exact error message | **Pass** |
| **AUT-03** | Security / Auth | Password missing letter | Input password `"123456789"` (no letters) | Password rejected with `"Password must include at least one letter."` | Password rejected with exact error message | **Pass** |
| **AUT-04** | Security / Auth | Valid password compliance | Input password `"Admin@123"` | Password accepted with zero validation errors | Password accepted without error | **Pass** |
| **AUT-05** | Security / XSS | Output escaping via `e()` | String `"<script>alert(1)</script>"` | HTML entities escaped as `&lt;script&gt;alert(1)&lt;/script&gt;` | String properly escaped to entity equivalents | **Pass** |
| **AUT-06** | Core / Routing | Standard route dispatching | Route `?r=booking/customerList` | Dispatches to `BookingController::customerList` | Dispatches correctly to `BookingController` and `customerList` | **Pass** |
| **AUT-07** | Core / Security | Controller reflection guard | Route `?r=venue/render` | Protected `render` method cannot be invoked directly from router; returns HTTP 404 | Method call blocked by reflection check; actionIsAllowed is false | **Pass** |
| **AUT-08** | Core / Security | Path traversal blocking | Route `?r=../x` | Regex whitelist rejects non-alphabetic controller names; returns HTTP 404 | Controller name rejected by `^[a-z]+$` regex | **Pass** |
| **AUT-09** | Venues / Security | Disguised PHP file upload | File containing PHP code named `exploit.png` | `finfo` MIME sniffing detects `text/x-php` / `text/plain` and rejects upload | MIME sniffing identifies non-image type and rejects | **Pass** |
| **AUT-10** | Bookings / State | Direct illegal confirmation | Calling `changeStatus` from `Pending` to `Confirmed` | Prohibited by transition matrix; throws `DomainException` | Prohibited by matrix; `DomainException` thrown | **Pass** |
| **AUT-11** | Bookings / State | Final state immutability | Attempting status change on `Completed` booking | Final status has no transitions; change refused | No transitions defined for `Completed`, `Rejected`, or `Cancelled` | **Pass** |
| **AUT-12** | Pricing (Obj 2) | Mon-Thu Weekday Discount 10% | Venue US$100.00, booking on Monday 09:00–13:00 | Standard US$100.00, Adjustment -US$10.00, Total US$90.00 | Total charge computed as US$90.00 | **Pass** |
| **AUT-13** | Pricing (Obj 2) | Fri-Sun Weekend Surcharge 20% | Venue US$100.00, booking on Friday 09:00–13:00 | Standard US$100.00, Adjustment +US$20.00, Total US$120.00 | Total charge computed as US$120.00 | **Pass** |
| **AUT-14** | Pricing (Obj 2) | Off-Peak Month Discount 15% | Venue US$100.00, active off-peak rule | Standard US$100.00, Adjustment -US$15.00, Total US$85.00 | Total charge computed as US$85.00 | **Pass** |
| **AUT-15** | Pricing (Obj 2) | Long Booking Discount (8+ hrs) | Venue US$100.00, 8-hour booking (08:00–16:00) | Single rule with highest priority applied: Total US$95.00 | Total computed as US$95.00; single rule applied | **Pass** |
| **AUT-16** | Pricing (Obj 2) | No matching rule fallback | Venue US$100.00, date outside rule windows | Standard US$100.00, Adjustment US$0.00, Total US$100.00 | Total charge computed as US$100.00 with 0 adjustment | **Pass** |
| **AUT-17** | Pricing (Obj 2) | Inactive rule exclusion | Inactive rule with 50% discount | Inactive rule ignored; total remains US$100.00 | Inactive rule completely ignored | **Pass** |
| **AUT-18** | Pricing (Obj 2) | Negative price prevention | Fixed discount US$150.00 on US$100.00 venue | Adjustment capped at -US$100.00; Total never below US$0.00 | Total charge capped at US$0.00 | **Pass** |
| **AUT-19** | Payments | Receipt number formatting | Sequence generator output | Matches pattern `^RCT-\d{8}-\d{4}$` | Format verified matching `RCT-YYYYMMDD-XXXX` | **Pass** |
| **AUT-20** | Bookings | Booking reference formatting | Sequence generator output | Matches pattern `^COH-\d{8}-\d{4}$` | Format verified matching `COH-YYYYMMDD-XXXX` | **Pass** |
| **MAN-01** | Auth | Valid staff login | `admin@coh.co.zw` / `Admin@123` | Login successful, session created, redirected to Dashboard | | |
| **MAN-02** | Auth | Invalid password login | `admin@coh.co.zw` / `WrongPass!` | Login refused, flash message `"Invalid email or password."` | | |
| **MAN-03** | Auth | Brute force lockout | 5 consecutive invalid login attempts for same email | Login locked for 5 minutes; flash message `"Too many failed login attempts..."` | | |
| **MAN-04** | Auth | Duplicate email registration | Register new customer with `customer1@coh.co.zw` | Registration refused: `"This email address is already registered."` | | |
| **MAN-05** | Access Control | Role 403 enforcement (Customer) | Customer opens `?r=user/index` or `?r=venue/index` | Access denied with HTTP 403 Forbidden page | | |
| **MAN-06** | Access Control | Role 403 enforcement (Officer) | Booking Officer opens `?r=pricingrule/index` | Access denied with HTTP 403 Forbidden page | | |
| **MAN-07** | Access Control | Role 403 enforcement (Mgmt) | Council Management posts to `booking/updateStatus` | Access denied with HTTP 403 Forbidden page | | |
| **MAN-08** | Access Control | Active deactivation session check | Admin deactivates Booking Officer in another tab | Officer's next click terminates session and redirects to login | | |
| **MAN-09** | Access Control | Self-deactivation prevention | Admin attempts to deactivate own account | Action refused: `"You cannot deactivate your own account."` | | |
| **MAN-10** | Bookings | Simultaneous double-booking | Two tabs submit identical slot at same second | One succeeds; second receives conflict error due to row lock transaction | | |
| **MAN-11** | Bookings | Adjacent slot booking | Slot 1 ends at 12:00; Slot 2 starts at 12:00 | Slot 2 is permitted and saves successfully | | |
| **MAN-12** | Bookings | Past date rejection | Customer selects yesterday's date | Refused: `"Booking date cannot be in the past."` | | |
| **MAN-13** | Bookings | End time before start time | Start: 14:00, End: 10:00 | Refused: `"End time must be after start time."` | | |
| **MAN-14** | Bookings | Capacity overflow | Attendees: 300 for a 180-capacity hall | Refused: `"Number of attendees exceeds venue capacity."` | | |
| **MAN-15** | Bookings | Customer cancel own booking | Customer cancels own `Pending` booking | Status updates to `Cancelled`; reflected on customer table | | |
| **MAN-16** | Bookings | Cancel another's booking attempt | Customer posts `booking/cancel` with another ID | Action refused with 403 or `"Booking not found for your account."` | | |
| **MAN-17** | Payments | Amount tampering prevention | Customer alters hidden `amount_paid` field in browser DOM | Server uses database `total_charge`; posted tamper ignored | | |
| **MAN-18** | Payments | Duplicate payment prevention | Submitting payment when status is `Pending Verification` | Refused: payment already submitted awaiting review | | |
| **MAN-19** | Payments | Payment rejection lifecycle | Revenue Officer rejects payment with reason | Payment becomes `Rejected`; booking stays `Approved`; customer can pay again | | |
| **MAN-20** | Payments | Payment verification lifecycle | Revenue Officer verifies payment | Payment becomes `Verified`; booking becomes `Confirmed`; receipt generated | | |
| **MAN-21** | Security / CSRF | Missing CSRF token | POST request to `auth/login` without CSRF token | Action refused; error logged; session expired flash shown | | |
| **MAN-22** | Security / SQLi | SQL injection in search/input | String `' OR '1'='1` in text inputs | Input handled safely via PDO prepared statement as literal text | | |
| **MAN-23** | Reports | Seed data total consistency | Run Booking & Revenue reports on seed data | Report totals match sum of seed database records | | |
| **MAN-24** | Reports | CSV Export compatibility | Download CSV report and open in Excel | Opens with clean columns, UTF-8 BOM, and no formula injection | | |

---

## 3. How to Run Automated Tests

Run the integrated suite from Laragon Terminal:
```powershell
& "D:\162\Documentaries\WebsiteDevelopment\L\Laragon\bin\php\php-8.3.26-Win32-vs16-x64\php.exe" tests/suite_test.php
```

All 20 automated test assertions execute sequentially, reporting `PASS` or `FAIL` with a summary exit code.
