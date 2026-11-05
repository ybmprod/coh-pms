# Admin-Provisioned Staff Onboarding And Activation

**Project:** City of Harare Property Management System (COH-PMS)
**Module:** Authentication and access control (Chapter 5.3.1)
**Status:** Implemented

---

## 1. Objective

Staff accounts are created by an Administrator, never by public registration. The Administrator assigns the role and contact details, and the staff member sets their own password through a time-limited activation link. The Administrator never handles or knows the staff password.

Customers continue to self-register as `Customer` only.

---

## 2. User Story

> As an Administrator, I want to create a staff profile with an assigned role without handling their credentials, so the system can issue a secure password-setup link that expires automatically.

---

## 3. Workflow

```text
Administrator opens Staff -> Users -> Create staff account
  -> enters full name, council email, role (optional direct password)
  -> account is created in Pending Activation state
  -> a 32-byte random token is generated
  -> only a SHA-256 hash plus expiry is stored in users.password_hash
  -> a 24-hour activation URL is shown to the Administrator and written to the log
  -> staff member opens the link and sets a password
  -> password is validated, hashed with bcrypt, token is replaced
  -> account becomes Active
  -> staff signs in through the staff portal
```

If the email is not delivered (the project has no SMTP dependency), the Administrator can copy the activation URL from the confirmation panel or use **Resend invite**, which issues a fresh 24-hour token.

---

## 4. Technical Requirements And Implementation

| Requirement | Implementation |
|---|---|
| Admin provisioning form | `UserController::create()` with server-side validation and CSRF |
| Roles available | Booking Officer, Revenue Officer, Administrator, Council Management |
| Pending state | `account_status = 'Inactive'` while the password hash holds an `INVITE:` token |
| Token generation | `bin2hex(random_bytes(32))` — 64 hex characters |
| Token storage | `User::encodeInvitationHash()` stores `INVITE:<sha256>:<unix_expiry>`; the plain token is never stored |
| Token lifetime | `User::INVITE_TTL_SECONDS = 86400` (24 hours) |
| Activation route | `?r=auth/activate&token=...` handled by `AuthController::activate()` |
| Password policy | Minimum 8 characters, at least one letter and one digit (`validate_password()`) |
| Password storage | `password_hash($password, PASSWORD_BCRYPT)` via `User::activateAccount()` |
| Token invalidation | Activating replaces the token with the bcrypt hash; the old link can no longer be used |
| Expired or invalid link | Dedicated activation screens explain the state and direct the user to request a new link |
| Resend invitation | `UserController::resendInvite()` issues a new token and resets the account to Inactive |
| Login while pending | Staff login detects `User::isPendingActivation()` and explains that activation is required |
| Audit trail | `auth.activate.success`, `user.create`, `user.resend_invite` events in `storage/logs/audit.log` |

Delivery note: `UserController::dispatchOnboardingInvitation()` writes the invitation to the PHP error log and the URL is displayed in the Administrator UI. This keeps the system offline and dependency-free; an SMTP or SMS adapter can be added later without changing the token model.

---

## 5. Security Properties

1. The plain invitation token exists only in the generated URL; the database stores a SHA-256 hash.
2. Comparison uses `hash_equals()` to avoid timing leaks.
3. Tokens expire after 24 hours and are single-use because activation overwrites them with a bcrypt hash.
4. Staff cannot sign in while the account is Inactive.
5. Only an Administrator can create or resend staff invitations; the action is POST + CSRF protected.
6. Self-deactivation is blocked; the last active Administrator cannot be removed.
7. Passwords are never logged or displayed; the audit log masks sensitive fields.

---

## 6. Manual Test Steps

1. Sign in as `admin@coh.co.zw` / `Admin@123`.
2. Open **Staff -> Users**, create a Booking Officer with a new email and leave the password blank.
3. Confirm the success message and copy the activation URL shown in the panel.
4. Open the URL in a private window.
5. Submit mismatched passwords; confirm the error.
6. Submit a weak password; confirm the policy error.
7. Submit `Officer@123` twice; confirm the account activates and redirects to staff login.
8. Sign in with the new account; confirm the Booking Officer dashboard loads.
9. Re-open the original activation URL; confirm it is now invalid.
10. In the Administrator session, use **Resend invite** for the new user; confirm a new 24-hour link is issued.
11. Attempt staff login while the account is pending; confirm the pending-activation message.

---

## 7. Acceptance Criteria

- [x] Only Administrators can create staff accounts.
- [x] New staff accounts start as Pending Activation.
- [x] Invitation tokens expire after 24 hours and are single-use.
- [x] Passwords are bcrypt hashes and meet the policy.
- [x] Expired and invalid links show clear, non-technical screens.
- [x] Resending an invitation invalidates the previous link.
- [x] Customers cannot use the staff portal and staff cannot use the customer portal.
- [x] All provisioning and activation events are audited.
