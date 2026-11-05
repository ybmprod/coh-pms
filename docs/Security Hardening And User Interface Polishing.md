# COH-PMS – Security Hardening + UI Polish (one focused pass)

> **Status: completed.** All tasks below were implemented and verified against the automated suites. The October 2026 reconstruction added environment-driven configuration, a class autoloader, the missing `bookings.event_type_id` schema link, event-type seeds, an additional 405 error page, and shared password-toggle/print/loading components. This document is retained as the original acceptance brief.

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

---

## November 2026 follow-up hardening

This second pass adds defence-in-depth controls without changing the booking,
pricing or payment workflow.

### Session and CSRF

- Session ID length increased to 48 characters with 6 bits per character.
- User-agent fingerprinting was removed in the November 2026 simplification: a changed browser string logged legitimate users out.
- One idle timeout (`SESSION_TIMEOUT`); the absolute timeout was removed along with it.
- Session IDs regenerate on login and logout only; the 15-minute rotation was removed.
- CSRF tokens rotate on login and logout.
- `verify_csrf_token()` compares the submitted token with the session and cookie copies only; the `Origin`/`Referer` check was removed because it rejected genuine forms behind proxies and tunnels.
- One session cookie name (`coh_pms_session`); the scheme-specific suffixes were removed.
- The CSRF token is mirrored in the host-only, `HttpOnly` `coh_pms_csrf` cookie. Verification accepts a token matching the session or that cookie, so a withheld or replaced session cookie no longer causes a false "form expired" rejection.
- `X-Forwarded-Proto` is no longer trusted; `Secure` is set only from the real request scheme.

### Sign-in availability fix (2 November 2026)

- `AuthController::handleLogin()` no longer calls `csrfIsValid()`. Customer and staff sign-in had returned "Your form has expired. Please try again." whenever the browser withheld or replaced the session cookie, because the posted token could never match the new, empty session.
- Email/password verification, active-account checks, portal role matching, the 5-attempt lockout, the per-IP rate limit, audit logging and session regeneration are unchanged.
- CSRF checks remain enforced on registration, staff activation, password reset and password change.
- Logout no longer refuses to run on a stale or missing token: it logs the rejection and signs the user out, so the "Your session has expired" message can no longer appear while logging out (3 November 2026).
- Trade-off: an attacker could log a victim into an attacker-controlled account (login CSRF). If the check is reinstated, use the double-submit cookie fallback already present in `csrf_helper.php` rather than relying on the session alone.

### Content Security Policy

- Inline scripts now require a per-request nonce (`csp_nonce()`).
- The two layout inline scripts carry the nonce.
- Inline `onclick`/`onerror` handlers were removed and replaced by `data-*` hooks in the shared footer script.
- Added `object-src 'none'`, `worker-src 'none'`, `frame-src 'none'`, `media-src 'self'`, `manifest-src 'self'`, COOP, CORP, `X-Permitted-Cross-Domain-Policies` and `X-DNS-Prefetch-Control`.
- `X-Powered-By` is removed in PHP and Apache.
- POST bodies larger than 4 MB are rejected with HTTP 413.

### Rate limiting and passwords

- `rate_limit_allow()` adds file-backed fixed-window limits for login-by-IP (20/15 min), registration (5/hour), password-reset requests (5/15 min), password-reset completion (10/15 min), password changes (5/15 min), booking writes (30/hour) and payment submissions (10/hour).
- New passwords use bcrypt cost 12 (`hash_password_secure()`); cost-10 hashes are rehashed on the next successful login.
- Password validation rejects values over 128 characters and NUL bytes.

### Row-level-security approximation

- MySQL/MariaDB do not provide native RLS.
- `coh_current_customer_id()` reads `@coh_pms_customer_id`, which `config/database.php` sets on every connection.
- The read-only views `v_customer_bookings`, `v_customer_payments` and `v_customer_receipts` filter by that value.
- Customer booking, payment and receipt reads use these views; ownership checks remain in the controllers and services.
- `database/security/least_privilege_accounts.sql.example` documents an optional views-only MySQL account. The views are a defence-in-depth layer, not a replacement for application authorisation.

### Audit and uploads

- Audit context is recursively redacted for keys containing password, token, secret, authorisation, cookie, CSRF, API key or card number.
- Venue uploads are decoded with `getimagesize()`, capped at 8000x8000, saved with mode 0640, and still require `finfo` MIME plus extension agreement.

### Verification

```powershell
$php = "D:\162\Documentaries\WebsiteDevelopment\L\Laragon\bin\php\php-8.3.26-Win32-vs16-x64\php.exe"
& $php tests\run_all.php
```

Expected: syntax sweep clean; logic 11, security 32, hardening 36, password reset 8, pagination 17, regression 37
(zero failures; database-dependent checks skip when MySQL is stopped).
