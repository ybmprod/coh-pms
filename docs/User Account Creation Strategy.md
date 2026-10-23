┌────────────────────────────────────────────────────────────────────────────────────────┐
│  FEATURE REQUEST: ADMIN-PROVISIONED STAFF ONBOARDING & EMAIL ACTIVATION WORKFLOW       │
├────────────────────────────────────────────────────────────────────────────────────────┤
│  OBJECTIVE                                                                             │
│  Implement a secure, admin-initiated staff provisioning process for COH-PMS.           │
│  Administrators will create profiles and assign roles, while staff will securely       │
│  set their own passwords via an automated email link.                                  │
│                                                                                        │
│  USER STORY                                                                            │
│  As an Administrator, I want to create a staff account with an assigned role without   │
│  handling their credentials, so the system can email them a secure password-setup link.│
│                                                                                        │
│  TECHNICAL REQUIREMENTS                                                                │
│  1. Admin Provisioning Form: Captures First/Last Name, Council Email, and System Role.  │
│     Saves record with status 'Pending_Activation' (Password field remains empty/null).  │
│                                                                                        │
│  2. Token & Email Engine: Generates a secure, random invitation token valid for 24     │
│     hours. Fires an automated onboarding email containing the unique activation URL.   │
│                                                                                        │
│  3. Self-Service Activation Route: Validates the incoming token. Renders a secure      │
│     password form. On submission, hashes the password, marks status as 'Active',       │
│     purges/invalidates the token, and redirects the user to the login portal.          │
│                                                                                        │
│  4. Resilience & Error Handling: Renders explicit error screens for expired/invalid    │
│     tokens. Provides a "Resend Invitation" button in the Admin dashboard.              │
└────────────────────────────────────────────────────────────────────────────────────────┘
