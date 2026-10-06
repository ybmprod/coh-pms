# COH-PMS Chapter 5.4 Screen Captures Guide

**Document:** Dissertation Chapter 5.4 System Demonstration & Visual Walkthrough  
**Project:** City of Harare Property Management System (COH-PMS)  

Capture the following pages in this precise order for presentation in the Chapter 5.4 implementation section of the project documentation.

---

### Figure 5.1: System Home Page
- **Route:** `http://localhost/coh-pms/public/` or `?r=home/index`
- **Role:** Public (Unauthenticated)
- **Description:** Landing page showing City of Harare branding, system introduction, navigation bar with login/register links, and database connection status indicator.
- **Key Elements to Show:** City of Harare title, municipal dark blue palette, Clean Call-to-Action buttons ("Browse Venues", "Login", "Register").

---

### Figure 5.2: Customer Registration & Login Interfaces
- **Route:** `?r=auth/register` and `?r=auth/login`
- **Role:** Public (Unauthenticated)
- **Description:** 
  - (a) Customer self-registration form displaying full name, email, phone number, password, and password confirmation with client/server validation.
  - (b) Secure login interface with CSRF token protection, password masking, and lockout protection after failed attempts.
- **Key Elements to Show:** Clear validation rules (minimum 8 characters, letter, digit), clean stacked form styling, and flash notification area.

---

### Figure 5.3: Customer Venue Browsing & Details
- **Route:** `?r=venue/customerList` and `?r=venue/details&id=1`
- **Role:** Customer (`customer1@coh.co.zw`)
- **Description:** 
  - (a) Grid view of available council properties (Mbare Community Hall, Rufaro Stadium, etc.) showing venue name, location, capacity, standard daily price, and thumbnail.
  - (b) Detailed venue profile showing full description, facilities list (PA system, stage, lighting), and "Request Booking" navigation.
- **Key Elements to Show:** Card-based layout, formatted currency (`US$ 100.00`), and venue images loaded securely via relative paths.

---

### Figure 5.4: Venue Booking Request with Live Pricing Calculation (Objective 2)
- **Route:** `?r=booking/customerList`
- **Role:** Customer (`customer1@coh.co.zw`)
- **Description:** Booking request form featuring interactive date and time pickers. Demonstrates AJAX-driven real-time availability verification and automatic pricing rule application.
- **Key Elements to Show:** The live pricing panel displaying:
  - Standard Charge (`US$ 100.00`)
  - Applied Pricing Rule (`Weekday Discount` or `Weekend Surcharge`)
  - Adjustment Amount (`-US$ 10.00` or `+US$ 20.00`)
  - Total Calculated Charge (`US$ 90.00` or `US$ 120.00`)
  - Real-time availability indicator message.

---

### Figure 5.5: Customer Booking Tracking & Payment Submission
- **Route:** `?r=booking/customerList` and `?r=booking/details&id=2`
- **Role:** Customer (`customer1@coh.co.zw`)
- **Description:** 
  - (a) Customer booking history table displaying reference numbers (`COH-YYYYMMDD-XXXX`), event details, charges, booking statuses (`Pending`, `Approved`, `Confirmed`), and payment statuses (`Pending Verification`, `Verified`).
  - (b) Inline payment submission form for `Approved` bookings (selecting method: Cash, Bank Transfer, Mobile Money, Card, and entering transaction reference).
  - (c) "Cancel" button enabled only for eligible statuses.
- **Key Elements to Show:** Distinct status badge colours, payment submission dropdown, and direct link to booking details.

---

### Figure 5.6: Official Confirmation & Printable Receipt (Objective 1)
- **Route:** `?r=payment/receipt&booking_id=4`
- **Role:** Customer or Staff
- **Description:** Official municipal booking confirmation and payment receipt generated upon verification.
- **Key Elements to Show:** Official Receipt Number (`RCT-YYYYMMDD-XXXX`), booking reference, customer name, venue details, charge breakdown, payment method, transaction reference, verifying Revenue Officer name, verification timestamp, and print CSS formatting.

---

### Figure 5.7: Booking Officer Dashboard & Booking Review Screen
- **Route:** `?r=dashboard/index` and `?r=booking/index`
- **Role:** Booking Officer (`booking.officer@coh.co.zw`)
- **Description:** 
  - (a) Role-tailored dashboard displaying metric cards for Pending Bookings and Today's Bookings.
  - (b) Booking review queue with contextual state transition buttons: **Approve**, **Reject**, **Cancel**, and **Mark Completed** (only available post-event date).
- **Key Elements to Show:** Reviewer action buttons reflecting strict state machine rules (no manual selection of Confirmed), attendee counts against venue capacity, and flash confirmation messages.

---

### Figure 5.8: Revenue Officer Dashboard & Payment Verification Screen
- **Route:** `?r=dashboard/index` and `?r=payment/index`
- **Role:** Revenue Officer (`revenue.officer@coh.co.zw`)
- **Description:** 
  - (a) Role-tailored dashboard displaying Payments Awaiting Verification and Monthly Verified Revenue.
  - (b) Payment verification queue with status filter (Pending Verification, Verified, Rejected). Each pending row provides one-click **Verify** and **Reject** actions.
- **Key Elements to Show:** Verified revenue summary card, payment method details, transaction reference, and instantaneous status change to Verified with receipt generation.

---

### Figure 5.9: Administrator User Management & Pricing Rules
- **Route:** `?r=user/index` and `?r=pricingrule/index`
- **Role:** Administrator (`admin@coh.co.zw`)
- **Description:** 
  - (a) Staff management screen for provisioning new staff accounts (Booking Officer, Revenue Officer, Administrator, Council Management) and toggling Active/Inactive status with self-deactivation protection.
  - (b) Municipal pricing rules CRUD interface showing active discount and surcharge rules, priority weighting, dates, and days of week.
- **Key Elements to Show:** Role selection dropdown, status toggle buttons, rule priority orders, and rule activation switches.

---

### Figure 5.10: Administrator Venue Management with Image Upload
- **Route:** `?r=venue/index`
- **Role:** Administrator (`admin@coh.co.zw`) or Booking Officer
- **Description:** Venue administration interface with add venue form, image upload input, and editable table rows for existing venues.
- **Key Elements to Show:** Venue type dropdown, capacity and standard daily price fields, image replacement selector, and status toggle (`Available`, `Unavailable`, `Under Maintenance`).

---

### Figure 5.11: Council Management Reports Dashboard & CSV Export (Objective 3)
- **Route:** `?r=report/index` and `?r=report/exportCsv`
- **Role:** Council Management (`management@coh.co.zw`) or Administrator
- **Description:** Executive reporting dashboard presenting the 4 required analytical reports:
  - (a) **Booking Report:** Filterable by date range, venue, and booking status.
  - (b) **Facility Usage Report:** Displays total bookings, booked hours (decimal precision), and usage percentage based on 12-hour operational days.
  - (c) **Payment Report:** Breakdown of payment transactions by method and status.
  - (d) **Revenue Report:** Purely verified municipal revenue broken down by venue and by month, accompanied by a CSS bar chart visualizer.
  - (e) **CSV Export:** Button to export clean, Excel-compatible CSVs with UTF-8 BOM and formula injection mitigation.
- **Key Elements to Show:** Tabbed report navigation, date range filter forms, summary totals row, pure CSS bar chart, and Excel spreadsheet screenshot demonstrating clean columns.
