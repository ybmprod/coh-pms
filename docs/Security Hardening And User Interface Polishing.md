# COH-PMS – Security Hardening + UI Polish (one focused pass)

You are working on the existing City of Harare Property Management System (COH-PMS) PHP project.

Do NOT change the three main objectives or break the existing booking / pricing / reporting flow.

## Goals (in priority order)

1. Harden security without adding heavy frameworks.
2. Polish the UI so it looks professional, consistent, and mobile-friendly while staying offline-capable (no new CDNs).

## Security tasks (do all of these)

- **Session**: force secure cookie flags (HttpOnly, Secure when HTTPS, SameSite=Lax/Strict), regenerate session ID on login, set reasonable timeout, destroy properly on logout.
- **Headers**: keep existing X-Frame-Options / X-Content-Type-Options / Referrer-Policy; add a strict Content-Security-Policy that allows only self + the local fonts/css/js already in public/assets. Add Permissions-Policy if easy.
- **CSRF**: ensure every state-changing form (POST) includes the token and is verified; protect AJAX endpoints that mutate data.
- **Auth**: keep customer vs staff portal separation; on every protected action re-check role + account_status = Active; never trust client-side role.
- **Input**: continue strict validation; escape all output with the existing e() helper; never echo raw `$_GET`/`$_POST`.
- **Uploads**: keep finfo MIME check + extension whitelist; store outside web root or behind `.htaccess` that denies script execution (already partially done).
- **Router**: keep the reflection public-method guard; reject any unexpected characters in the route.
- **Passwords**: keep current policy (min 8, letter + digit); ensure only bcrypt hashes are stored.
- **Logging**: add simple file-based audit log for login success/fail, booking status changes, payment verification, pricing-rule changes (no sensitive data).
- **Rate-limit basic**: simple session or file-based throttle on login attempts (e.g. 5 fails → 5 min lock).

## UI Polish tasks (do all of these)

- Use the existing Poppins + uicons fonts and municipal colour palette already in `style.css`.
- Make all staff and customer pages fully responsive (sidebar collapses cleanly on mobile, tables become card/list on small screens).
- Consistent status badges (Pending / Approved / Confirmed / Rejected / Verified etc.) with clear colours and icons.
- Better empty states, loading indicators for AJAX availability/pricing, and clearer form error messages.
- Print-friendly receipt and report pages (hide nav, good margins).
- Accessible: sufficient contrast, focus states, aria labels on icon-only buttons, form labels properly associated.
- Keep the public landing page, customer venue browse, and staff dashboards clean and professional – no clutter.
- Do not introduce Bootstrap, Tailwind, React, or any external CDN. Everything must work offline.

## Constraints

- Preserve the existing MVC-service structure, route style (`?r=controller/action`), and the three core objectives.
- Do not rewrite the `PricingService` or `AvailabilityService` logic – only harden around them.
- Keep the seed data and test accounts working.
- After changes, the existing `logic_test.php`, `f7_security_test.php` and `suite_test.php` should still pass (or be updated if interfaces change slightly).
- Prefer small, readable, well-commented changes over large refactors.

## Deliverables

- Updated files only (no new heavy dependencies).
- Short changelog of security and UI improvements made.
- Confirmation that the three main objectives remain intact and the booking → approval → payment → confirmation flow still works.

Start by reading `config/config.php`, `app/helpers/auth_helper.php`, `app/core/Controller.php`, `public/index.php` and the main layout files, then apply the hardening and polish systematically.
