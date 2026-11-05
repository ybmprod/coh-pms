# COH-PMS System Test Plan & Test Results

**Project:** City of Harare Property Management System (COH-PMS)  
**Document:** Chapter 5 Verification and Test Matrix  
**Specification:** `docs/Property Management System Specifications.md` / `Agents.md`  

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
| **AUT-19** | Payments | Receipt number formatting | Sequence generator output | Matches pattern `^COH-PMS-VER-\d{6}$` | Format verified matching `COH-PMS-VER-XXXXXX` | **Pass** |
| **AUT-20** | Bookings | Booking reference formatting | Sequence generator output | Matches pattern `^COH-\d{8}-\d{4}$` | Format verified matching `COH-YYYYMMDD-XXXX` | **Pass** |
| **AUT-21** | Pricing (Obj 2) | Event-type charge composition | Venue US$100.00, event type Fee US$20.00, weekday discount 10% | Total = 100 + 20 − 10 = US$110.00 | Total computed as US$110.00 | **Pass** |
| **AUT-22** | Bookings | Overnight window calculation | 22:00 to 05:30 next day | Duration 7.5 hours; valid overnight window | Duration and charge calculated correctly | **Pass** |
| **AUT-23** | Bookings | Overnight long-booking qualification | 22:00 to 06:30 next day (8.5 hours) | Long Booking Discount applies | Discount applied | **Pass** |
| **AUT-24** | Bookings | Zero-duration rejection | Start time equals end time | Window rejected | Rejected | **Pass** |
| **AUT-25** | Bookings | Reversed daytime hours rejection | 14:00 to 10:00 | Window rejected | Rejected | **Pass** |
| **AUT-26** | Account Security | Reset token generation and hashing | Issue a token and inspect `password_reset_tokens` | Token is 64 hex characters; only its SHA-256 hash is stored; it resolves to the customer | | **Pass** |
| **AUT-27** | Account Security | Single-use reset token | Consume a valid token twice | First consumption updates the bcrypt hash; second consumption fails; old password no longer verifies | | **Pass** |
| **AUT-28** | Account Security | Expired and malformed token rejection | Resolve an expired token, malformed token and unknown token | All three are rejected | | **Pass** |
| **AUT-29** | Account Security | Per-account throttle detection | Issue a token, then call `recentlyRequested()` for the same account | The account reports as recently requested, so the controller refuses another link within 60 seconds | | **Pass** |
| **AUT-30** | Account Security | Development mail outbox | Send a password-change email with `MAIL_TRANSPORT=log` | `storage/logs/mail.log` receives the recipient, subject and reset link | | **Pass** |
| **AUT-31** | Event Types | Venue suitability rejection | Football Match mapped to Stadium/Open Space/Other, tested against a Community Hall | Community Hall rejected; Stadium and Open Space accepted; message lists the suitable venue types | | **Pass** |
| **AUT-32** | Event Types | Unmapped event fallback | Event type with no `event_type_venue_types` rows | Every venue type is accepted | | **Pass** |
| **AUT-33** | Access Control | Staff PC-only device enforcement | Mobile user agent and `Sec-CH-UA-Mobile` hint versus desktop user agent | Mobile and client-hint requests trigger restriction; desktop PC requests are allowed | | **Pass** |
| **AUT-34** | Bookings | Pending-booking amendment guard | Owner `Pending` booking, another customer, and an `Approved` booking | Only the owner's `Pending` booking is editable | | **Pass** |
| **AUT-35** | Security / CSRF | Cookie-copy CSRF fallback | Clear the session token, keep the `coh_pms_csrf` cookie, then verify the posted token | The cookie copy validates the token when the session has none; an empty token is rejected | | **Pass** |
| **AUT-36** | Security / Sessions | CSRF token rotation | Read token, call `csrf_rotate()`, retry the old token | New token verifies; old token is rejected | | **Pass** |
| **AUT-37** | Security / Rate limits | Generic action limiter | Call `rate_limit_allow()` beyond its quota, then reset | Requests pass up to the quota, the next request is blocked, and reset restores access | | **Pass** |
| **AUT-38** | Security / RLS | Customer view row filter | Set `@coh_pms_customer_id` to a seeded customer, then to `0` | Only that customer's rows appear; no rows appear without an identity | | **Pass** |
| **AUT-39** | Pagination | Page clamping | Request page `99` for a 37-row result with 10 rows per page, and pages `0`, `-3` and `abc` | Out-of-range page clamps to the last page with the correct offset; invalid pages fall back to page 1; an empty set reports page 1 of 1 | | **Pass** |
| **AUT-40** | Pagination | Filter-preserving pager links | Build a pager link with a venue filter, an existing page parameter and an empty filter | Venue filter is preserved, the page parameter is replaced, empty values are dropped and the anchor is kept | | **Pass** |
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
| **MAN-21** | Security / CSRF | Missing CSRF token on a protected POST | POST to `booking/create` or `auth/register` without a CSRF token | Action refused; error logged; session expired flash shown | | |
| **MAN-22** | Security / SQLi | SQL injection in search/input | String `' OR '1'='1` in text inputs | Input handled safely via PDO prepared statement as literal text | | |
| **MAN-23** | Reports | Seed data total consistency | Run Booking & Revenue reports on seed data | Report totals match sum of seed database records | | |
| **MAN-24** | Reports | CSV Export compatibility | Download CSV report and open in Excel | Opens with clean columns, UTF-8 BOM, and no formula injection | | |
| **MAN-25** | Event Types | Usage counts and deactivation warning | Disable an event type linked to an active `Pending`, `Approved` or `Confirmed` booking | Warning names the active booking count and requires "Disable anyway"; existing bookings keep their recorded charge | | |
| **MAN-26** | Event Types | Equivalent wordings share a charge | Select `Wedding`, `Traditional Wedding` or `Wedding Reception` for the same venue, date and times | All three produce the same event-type charge of US$50.00 and the same estimated total | | |
| **MAN-27** | Reports | Filter toolbar and clear action | Apply a venue and date range from the Filter dialog, then choose Clear filters | Active filter chips summarise the scope; Clear filters restores all records | | |
| **MAN-28** | Reports | Composition donut charts | Open the staff dashboard and Reports page | Donut charts replace bar and trend charts; each legend shows the value, and the chart remains readable at compact widths | | |
| **MAN-29** | Account Security | Customer requests password-change link | Customer signs in, opens Account Security and enters the correct current password | A single-use link is emailed, only its SHA-256 hash is stored, and the success message masks the email address | | |
| **MAN-30** | Account Security | Reset link changes password | Open the emailed link, choose a new password, then sign in | Password changes, the link can no longer be reused, and the old password fails | | |
| **MAN-31** | Account Security | Expired or reused link rejection | Open a used link or a link older than 60 minutes | Invalid/expired message appears with a link to request a new one | | |
| **MAN-32** | Account Security | Forgot-password privacy | Submit a known customer email and an unknown email | Both return the same generic response; no token is created or email sent for the unknown address | | |
| **MAN-33** | Event Types | Staff maintains venue suitability | Edit an event type and change its “Appropriate venues” checkboxes | At least one venue type is required; the table shows the saved list and custom event types default to every venue type | | |
| **MAN-34** | Venues | Customer filters suitable venues | Choose “Football Match” in “Show venues suitable for” | Only Stadium, Open Space and Other venues appear; View details and Book this venue preserve the event type | | |
| **MAN-35** | Bookings | Server rejects an unsuitable combination | Tamper with the booking POST to submit a Community Hall with Football Match | Booking is refused with a message naming the suitable venue types; no row is inserted | | |
| **MAN-36** | Mobile UX | Customer responsive experience | Open customer venue, booking, and account pages on a smartphone viewport | Streamlined mobile header, touch targets >= 44px, 16px form controls without iOS auto-zoom, proportional card padding | | |
| **MAN-37** | Access Control | Staff portal PC-only restriction | Open staff routes or staff login from a mobile device (phone or tablet) | Server returns 403 with the desktop-only restriction page; desktop PC browsers remain unrestricted | | |
| **MAN-38** | Bookings | Customer amends a Pending booking | Change the venue, date, time, event type or attendance on a `Pending` booking, then save | Availability and pricing are re-run transactionally; the booking keeps its status, the reference changes only when the date changes, and the new total is stored | | |
| **MAN-39** | Bookings | Amendment rejects a clash | Amend a `Pending` booking onto a slot already held by another active booking | Save is refused with the overlap message, and the original booking is unchanged | | |
| **MAN-40** | Lists / Reports | Pagination across staff lists and reports | Page through bookings, payments, users and each report table, and change a filter while on a later page | Each list pages independently at `PAGINATION_PER_PAGE` rows; filters are preserved in the pager links; report footer totals and CSV exports still reflect the whole filtered set | | |
| **MAN-41** | Auth | Sign-in works without a CSRF token | POST `auth/login` and `auth/staffLogin` with valid credentials and no `csrf_token` field | Both sign in successfully; customer reaches the customer dashboard and staff reach the staff dashboard | | |
| **MAN-42** | Auth | Sign-in survives a withheld session cookie | Request the login form, keep only the `coh_pms_csrf` cookie, then POST the credentials | Sign-in succeeds without "Your form has expired" | | |
| **MAN-43** | Security / CSRF | Non-login POST still rejects a forged token | POST a bogus `csrf_token` to `auth/register` | Request is refused with the expired-form message | | |
| **MAN-44** | Auth | Logout never reports an expired session | Sign in, delete the CSRF cookie and post `auth/logout` with a stale token | The user is signed out and lands on the login page with "You have been logged out successfully." | | |

---

## 3. How to Run Automated Tests

Run the suites from Laragon Terminal (PHP may not be on the PATH):

```powershell
& "D:\162\Documentaries\WebsiteDevelopment\L\Laragon\bin\php\php-8.3.26-Win32-vs16-x64\php.exe" tests/logic_test.php
& "D:\162\Documentaries\WebsiteDevelopment\L\Laragon\bin\php\php-8.3.26-Win32-vs16-x64\php.exe" tests/f7_security_test.php
& "D:\162\Documentaries\WebsiteDevelopment\L\Laragon\bin\php\php-8.3.26-Win32-vs16-x64\php.exe" tests/security_hardening_test.php
& "D:\162\Documentaries\WebsiteDevelopment\L\Laragon\bin\php\php-8.3.26-Win32-vs16-x64\php.exe" tests/password_reset_test.php
& "D:\162\Documentaries\WebsiteDevelopment\L\Laragon\bin\php\php-8.3.26-Win32-vs16-x64\php.exe" tests/pagination_test.php
& "D:\162\Documentaries\WebsiteDevelopment\L\Laragon\bin\php\php-8.3.26-Win32-vs16-x64\php.exe" tests/suite_test.php
```

Current results:

| Suite | Assertions | Result |
|---|---|---|
| `logic_test.php` | 11 | 11 passed, 0 failed |
| `f7_security_test.php` | 32 | 32 passed, 0 failed |
| `security_hardening_test.php` | 29 | 24 passed when MySQL is unavailable; up to 29 with the database-backed view checks |
| `password_reset_test.php` | 8 | 8 passed, 0 failed (skipped when MySQL is unavailable) |
| `pagination_test.php` | 17 | 17 passed, 0 failed (skipped when MySQL is unavailable) |
| `suite_test.php` | 37 | 37 passed, 0 failed |

Each assertion prints `PASS` or `FAIL` and the process returns a summary exit code. The password-reset suite, the pagination suite and the row-filter view checks require MySQL and report `SKIP` when the local database is unavailable.
