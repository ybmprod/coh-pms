# Coding Standards And Future Roadmap

**Project:** City of Harare Property Management System (COH-PMS)
**Audience:** the student, supervisor, and any developer continuing the project
**Related:** `Agents.md` (full handbook), `Readme.md` (setup)

---

## 1. Architecture Contract

```text
Browser
  -> public/index.php        security headers, route whitelist, dispatch guard
  -> app/core/Router.php     ?r=controller/action
  -> app/controllers/*       authorise -> validate -> call service -> redirect/render
  -> app/services/*          business rules, transactions, state machine
  -> app/models/*            prepared PDO queries only
  -> views/*                 escaped HTML output only
```

Rules:

- Controllers never contain SQL or pricing formulas.
- Services never contain HTML.
- Models never decide permissions.
- Views never mutate data or run queries.
- Business rules belong in exactly one service method, so the dissertation can cite one location.

---

## 2. PHP Standards

1. `declare(strict_types=1);` in every file.
2. One class per file, filename equal to the class name (`EventType.php`).
3. PSR-12 style: 4 spaces, braces on the next line for classes and methods.
4. Types on parameters and return values wherever possible.
5. Prepared statements for every query; never concatenate user input.
6. Escape every dynamic value in a view with `e()`.
7. Every POST verifies the CSRF token against the session or the `coh_pms_csrf` cookie copy before acting.
8. Every protected action re-checks the role and `account_status` on the server.
9. Use POST for all state changes; return 405 for GET on write actions.
10. Customer reads use the `v_customer_*` views; ownership checks stay in the service/controller.
11. Use `hash_password_secure()` for new password hashes and rehash legacy hashes on login.
12. Inline scripts require `csp_nonce()`; never add `onclick`/`onerror` attributes.
13. No `var_dump`, `print_r`, or debug output in committed code.

---

## 3. Naming Conventions

| Item | Convention | Example |
|---|---|---|
| Classes | PascalCase | `PricingService` |
| Methods / functions | camelCase | `calculateChargePrefix()` |
| Variables in PHP | snake_case or camelCase matching surrounding file | `$bookingId`, `$filters` |
| Constants | UPPER_SNAKE_CASE | `BOOKING_STATUS_PENDING` |
| Database tables | snake_case plural | `pricing_rules` |
| Database columns | snake_case | `event_type_charge` |
| Routes | `controller/action` lower case | `booking/checkAvailability` |
| CSS classes | kebab-case, BEM-ish | `.cv-card`, `.status-badge.pending` |
| JS functions | camelCase | `updatePricingPreview()` |
| Markdown files | Title Case With Spaces | `System Testing.md` |
| Python scripts | snake_case.py | `update_bookings.py` |

---

## 4. Database Change Procedure

1. Update `database/coh_pms.sql`.
2. Update `database/seed_data.sql` if new rows or columns need demonstration data.
3. Add an idempotent migration in `database/migrations/` using `information_schema` checks so it can run on MySQL 8 and MariaDB.
4. Update `docs/System Architecture And Schema Changes.md` and the specification.
5. Test on a fresh import and on an existing database.

Never edit `bookings`, `payments` or `pricing_rules` history in place; store snapshots so old records stay explainable.

---

## 5. Front-End Standards

- Reuse the tokens in `public/assets/css/style.css` section 1.
- Palette: municipal navy, off-white, slate, one gold accent.
- Typography: Poppins, fluid `clamp()` scale, readable measure.
- Required states for interactive elements: `:hover`, `:focus-visible`, `:active`, `[disabled]`, loading, error.
- Accessibility: semantic landmarks, labels tied to inputs, `aria-*` on icon-only controls, visible focus, skip link, WCAG 2.2 AAA contrast.
- Responsive: test at 1280, 1024, 768 and 640 px; tables become cards on small screens.
- Offline only: no external fonts, icons, scripts or images.
- Print: receipts and reports must print without navigation or buttons; use `.no-print`.
- All destructive actions use the shared confirmation dialog and POST.

---

## 6. Security Checklist For Every Change

- [ ] Prepared statements only.
- [ ] CSRF token checked on POST against the session or cookie copy (sign-in is the documented exception).
- [ ] Role and account status re-checked server-side.
- [ ] Customer reads use the row-filtered `v_customer_*` views.
- [ ] Write actions use `rate_limit_allow()` where abuse is possible.
- [ ] Output escaped.
- [ ] Inline scripts carry the CSP nonce; no inline event attributes.
- [ ] Upload MIME, size and image dimensions validated.
- [ ] No secrets in code, logs, or the repository.
- [ ] Error details logged, not displayed in production.
- [ ] Audit event written for sensitive actions; context redaction applied.

---

## 7. Definition Of Done

1. Code implemented in the correct layer.
2. All six test suites pass.
3. PHP syntax sweep clean.
4. Manual smoke test completed (login, booking, payment, report).
5. Responsive and print layouts checked.
6. Documentation updated.
7. Changed files listed and acceptance check written.

---

## 8. Future Roadmap

### Short term

1. ~~Event-type usage counts and deactivation warnings.~~ **Done (October 2026):** the event-type list shows total and active booking usage, and disabling a type in use requires an explicit warning confirmation.
2. ~~Customer amendment of `Pending` bookings with transactional re-pricing.~~ **Done (November 2026):** the customer edit form re-checks availability and pricing in one transaction, and the edit guard is covered by `BKG-04`.
3. ~~Inline SVG charts for revenue and usage.~~ **Done (October 2026):** `chart_helper.php` renders composition donut charts on the staff dashboard and reports, with a compact filter toolbar, filter dialog and clear action.
4. ~~Server-side pagination for large tables.~~ **Done (November 2026):** shared pagination helper, SQL `LIMIT`/`OFFSET` on the staff lists, and paged report tables whose totals always cover the full filtered set.
5. Notification service abstraction for future email/SMS adapters.
**Follow-up (before release):** Re-introduce CSRF on sign-in using the existing double-submit cookie fallback, then retire the November 2026 login exception once cookie-related lockouts are confirmed gone.

### Medium term

6. Refund records as negative payments with audit trail.
7. Proof-of-payment attachments using the existing upload protections.
8. Read-only audit log viewer for Administrators.
9. Backup/restore procedure and maintenance mode.
10. Read-only JSON endpoints for a future mobile client.

### Long term (explicit approval required)

11. Live payment gateway adapter behind `PaymentService`.
12. Queued email/SMS notifications.
13. Multi-tenant support for other councils.
14. TOTP two-factor authentication for staff.

### Out of scope

- Asset maintenance, leases, disposal and payroll.
- Replacing plain PHP with a framework or adding Composer/NPM dependencies.
- Storing card data or gateway credentials.

---

## 9. Dissertation Hooks

Keep the `// CHAPTER 5.3.x - <module>` comment at the top of each main module file:

| Code | Module |
|---|---|
| 5.3.1 | Authentication and access control |
| 5.3.2 | Venue management |
| 5.3.3 | Booking and availability |
| 5.3.4 | Pricing rules |
| 5.3.5 | Payment verification |
| 5.3.6 | Confirmation and receipt |
| 5.3.7 | Reporting |

When adding a module, add its chapter marker and a corresponding row in `docs/System Testing.md`.

