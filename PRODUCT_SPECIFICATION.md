# Codecartel Telecom Product Specification

## Document Information

- **Product Name:** Codecartel Telecom
- **Product Type:** Telecom recharge, balance management, reseller management, and provider API platform
- **Current Platform Stack:** Laravel 12, PHP 8.2, Blade templates, MySQL-backed data model, Tailwind/DaisyUI-style frontend
- **Document Purpose:** Describe the current functional product scope implemented in the codebase so the project can be understood, run, validated, and extended safely

---

## 1. Product Overview

Codecartel Telecom is a Bangladesh-focused telecom service platform for resellers and administrators. It combines:

1. **Retail/reseller operations** for flexiload recharge, drive offers, and internet packs
2. **Balance management** through manual mobile banking requests and SSLCommerz online payments
3. **Operational administration** for users, operators, packages, payments, security, branding, notices, and reporting
4. **Machine-to-machine provider APIs** that allow approved partner systems to submit recharge and balance requests programmatically

The product supports both web users and API consumers. It is designed for telecom businesses that need a centralized system to manage reseller hierarchies, wallets, recharge services, package fulfillment, and operational controls.

---

## 2. Product Vision and Business Goal

The platform aims to provide a single operational system for running a telecom recharge business with the following outcomes:

- Allow resellers to buy services using controlled wallet balances
- Allow admins to manage services, operators, packages, and access rules centrally
- Support both manual and online balance top-up workflows
- Provide partner API access for external provider integrations
- Improve operational control through approval queues, device logging, security policies, and reporting

---

## 3. Primary Users and Roles

### 3.1 Public Visitors

Public visitors can access the homepage, view branding content, supported operators, and provider API documentation. They can also reach authentication pages and public SSLCommerz status pages.

### 3.2 Reseller / User Accounts

Standard non-admin users act as resellers. The system includes user levels such as:

- House
- DGM
- Dealer
- Seller
- Retailer

Reseller accounts can have access to selected services based on permissions. Typical available reseller permissions include:

- Add Balance
- Drive Offers
- Internet Packs
- bKash
- Nagad
- Rocket
- Upay
- Islami Bank
- Pending Requests
- All History
- Drive History
- Profile
- Complaints

### 3.3 Admin Users

Admin users manage the platform. Permissions are stored on the user record and enforced by custom permission middleware. A first admin can bypass normal permission restrictions.

Admin responsibilities include:

- User and reseller management
- Package and operator management
- Manual request approval
- Payment gateway configuration
- API configuration
- Security and device approval controls
- Reports, complaints, notices, and branding management

### 3.4 API Partners / Provider Clients

Approved users with API access can interact with the product using API keys. API consumers can check auth, check balances, submit recharges, submit drive and internet pack requests, and create manual add-balance requests.

---

## 4. Product Scope Summary

The current application is composed of five major product areas:

| Area | Description |
|---|---|
| Public Website | Homepage, branding, operator showcase, provider API docs, login/register entry points |
| User Portal | Dashboard, add balance, flexiload, drive, internet packs, histories, complaints, profile |
| Admin Console | Operations, approvals, reports, users, packages, settings, branding, API, payment gateway, security |
| Payment Layer | Manual mobile banking deposit requests and SSLCommerz online payments |
| Provider API | API-key secured telecom transaction endpoints and routed settlement support |

---

## 5. Public Website Features

### 5.1 Homepage

The homepage serves as the product’s public landing page and currently includes:

- Company branding (name, logo, colors, content blocks)
- Sticky navigation with section anchors
- Branding image slider sourced from uploaded branding slides
- Supported telecom operator showcase
- Features and contact-style content sections
- Provider API documentation preview
- Login and registration entry points

### 5.2 Branding Slide Support

The homepage slider uses uploaded branding slide records and renders them when active slides exist. This allows administrators to change the public hero presentation without code changes.

### 5.3 Public Payment Status Page

The application exposes a public SSLCommerz payment status page. This page is used when a payment callback returns but the original authenticated session is no longer available. It displays:

- Payment status label
- Transaction ID
- Amount and status details
- Brand header/logo when configured
- Action buttons depending on whether the viewer is authenticated

---

## 6. Authentication, Access, and Account Model

### 6.1 Reseller Registration

Registration supports OTP verification before account creation. After successful verification:

- The user is created as a non-admin account
- Opening balance is assigned based on reseller level and deposit settings
- Password and PIN change timestamps are initialized
- The user is automatically logged in

### 6.2 User Login

User login supports:

- Email/password authentication
- Optional login captcha based on security settings
- Optional Google Authenticator OTP challenge
- Device approval checks before final session creation
- Session activity tracking

### 6.3 Admin Login

Admin login is stricter and supports:

- Email/password login
- Mandatory PIN verification
- Optional Google Authenticator OTP challenge
- Security captcha and reCAPTCHA depending on configuration

### 6.4 Permissions and Access Control

The product uses a mixed access model:

- Role-style split between admin and non-admin users
- Fine-grained permission lists stored on user records
- Dedicated middleware to protect admin modules and service pages
- API access approval flags and API service allowlists

---

## 7. Balance and Wallet Model

The platform uses multiple balance buckets instead of a single wallet.

### 7.1 Main Balance

Used for standard recharge and general wallet-funded services.

### 7.2 Drive Balance

Used for drive offer requests when the drive balance security toggle is enabled.

### 7.3 Bank Balance

Used for manual mobile banking add-balance operations when the bank balance security toggle is enabled.

### 7.4 Runtime Balance Resolution

Balance source is not static. It depends on security runtime settings:

- **Drive requests** use `main_bal` or `drive_bal` depending on the drive-balance toggle
- **Manual payment approval and API manual payment flows** use `main_bal` or `bank_bal` depending on the bank-balance toggle
- **Flexiload and internet pack operations** default to `main_bal` unless a routed billing rule says otherwise

This makes the balance system configurable for different business models.

---

## 8. User-Facing Functional Features

### 8.1 Add Balance

Users can fund their account using two primary methods.

#### Manual Mobile Banking

Supported manual channels include:

- bKash
- Nagad
- Rocket
- Upay
- Additional bank/manual options may be present in configuration or UI

Manual requests capture sender number, transaction ID, amount, and notes. These requests go into an approval workflow for administrators.

#### SSLCommerz Online Payment

Users can add balance through SSLCommerz when credentials are configured. The implemented flow includes:

1. User starts online payment
2. Pending SSLCommerz transaction is created
3. User is redirected to gateway
4. Gateway callback returns success/fail/cancel/ipn result
5. Transaction is verified against SSLCommerz validator API
6. Balance is credited once only (idempotent settlement behavior)
7. User is redirected to add balance or a public status page depending on session state

The product also supports sandbox/live switching for SSLCommerz.

### 8.2 Flexiload Recharge

Users can submit mobile recharge requests by providing:

- Operator
- Mobile number
- Amount
- Recharge type (for example prepaid/postpaid, where supported)

Requests are recorded and charged against the correct balance type. Operator availability controls can block submissions when an operator is switched off.

### 8.3 Drive Offers

Users can purchase drive packages by selecting a package and target mobile number. Pricing uses package price minus commission. Requests enter the drive request workflow and use the configured balance source.

### 8.4 Internet Packs

Users can purchase internet packages by selecting package and mobile number. The system validates:

- Package existence and active status
- Operator availability
- Mobile number prefix compatibility with operator rules

### 8.5 Pending Requests

Users can review pending operations, especially for services that require approval or asynchronous processing.

### 8.6 History Views

User-side history coverage includes multiple service categories such as:

- Flexiload history
- Drive history
- Internet pack history
- Manual payment history by provider
- Combined all history view

### 8.7 Profile

Users can manage account-level settings and view their account information.

### 8.8 Complaints / Support

Users can submit complaints or support-style issues for admin review.

---

## 9. Admin Functional Features

### 9.1 Admin Dashboard

The admin dashboard is the operational control center. It surfaces navigation to all core modules and includes chart/report style summaries for recent recharge and balance activity.

### 9.2 Pending Request Management

Admins can review and act on pending requests for:

- Drive requests
- Recharge requests
- Manual balance/payment requests
- Bulk approval or action flows in selected modules

### 9.3 Recharge and Service Histories

Admin history modules include:

- All History
- Flexiload history
- Drive history
- Internet Pack history
- Manual banking histories (bKash, Nagad, Rocket, Upay)

These views support filtering and summary calculations for operational review.

### 9.4 Operator and Package Management

Admins can manage:

- Telecom operators
- Regular packages
- Drive packages
- Activation states and operator-specific availability

Operator-off logic is enforced in both web and API transaction flows.

### 9.5 Reseller Management

Admin reseller management covers:

- Listing all resellers
- Filtering by level
- Viewing individual reseller profiles
- Updating reseller details
- Toggling active/inactive state
- Assigning balances
- Managing parent-child relationships in the reseller hierarchy
- Bulk action support in reseller tables

### 9.6 Payment History and Deposit Operations

The product includes payment-related admin modules for reviewing balance additions and deposit workflows.

### 9.7 Reports

Available report areas include:

- Balance Reports
- Operator Reports
- Daily Reports
- Sales Report

These modules support operational accounting, daily review, and service-level analysis.

### 9.8 Service Modules and Operational Controls

Admins can configure service-level behavior through modules such as:

- Service Modules
- Deposit settings
- Recharge Block List
- API Settings
- Payment Gateway settings
- Security Modual

### 9.9 Security Modual

The Security Modual is a key operational settings page. It manages controls related to:

- HTTPS redirect
- Captcha and reCAPTCHA
- Password strength rules
- Password/PIN expiry
- Session timeout
- OTP send channel preferences
- Alert channel preferences
- Bulk flexi limits and auto sending limits
- Operator-off toggles
- Bank/drive balance switching behavior
- Support ticket enablement

### 9.10 Branding and Notices

Admin tools also include:

- Branding management
- Device logs
- Login notice management
- General settings
- Mail configuration
- Mobile OTP configuration
- Firebase credential configuration
- Google OTP configuration

### 9.11 Admin User Management

Administrators can manage their own profile and admin accounts through:

- My Profile
- Manage Admin Users
- Change Password & PIN

### 9.12 Complaints Management

Admins can review user complaints and take action from the admin complaints module.

### 9.13 Deleted Account Management

The platform includes a deleted accounts area with restore functionality for eligible accounts.

---

## 10. Telecom Service Workflows

### 10.1 Recharge Workflow

1. User or API client submits recharge request
2. System validates operator, number, and amount
3. System determines correct balance source
4. Wallet sufficiency is checked
5. Request is created as pending
6. Balance is deducted immediately where applicable
7. Admin/provider settlement completes final status
8. History/reporting entries reflect the outcome

### 10.2 Drive Workflow

1. User/API selects drive package and mobile number
2. Package and balance are validated
3. Correct balance source is determined by runtime settings
4. Request is created
5. Balance is deducted
6. Approval/provider response updates status
7. Drive history is maintained

### 10.3 Internet Pack Workflow

1. User/API selects internet package and mobile number
2. Package and operator prefix are validated
3. Operator security checks are applied
4. Request is created and balance is deducted
5. Provider/admin updates final state
6. Internet request becomes visible in history/reporting

### 10.4 Manual Add-Balance Workflow

1. User/API submits manual payment details
2. Request is stored as pending
3. Admin reviews transaction evidence/details
4. Admin approves or rejects request
5. Wallet is credited according to active balance-routing rules
6. Payment history and audit trail are updated

### 10.5 Online Payment Workflow (SSLCommerz)

1. User starts gateway payment
2. Pending local transaction is created
3. User completes payment in SSLCommerz
4. Success/fail/cancel callbacks update local status
5. Validation API confirms legitimacy
6. Approved transaction credits balance exactly once
7. User sees add-balance page or public status page

---

## 11. Provider API Product Specification

### 11.1 API Purpose

The API allows external systems or partner resellers to integrate with the platform programmatically for telecom and balance operations.

### 11.2 API Security Model

API access depends on:

- User API key authentication
- API access enabled flag on the user account
- Service-level API allowlist checks
- Optional client-domain validation/whitelisting
- Manual approval and routing controls

### 11.3 Versioning

The current API is versioned under `/api/v1`.

### 11.4 Core Endpoints

| Endpoint | Method | Purpose |
|---|---|---|
| `/api/v1/auth-check` | POST | Validates API key and client eligibility |
| `/api/v1/balance` | POST | Returns main, drive, and bank balances |
| `/api/v1/recharge` | POST | Creates flexiload recharge request |
| `/api/v1/drive` | POST | Creates drive package request |
| `/api/v1/internet` | POST | Creates internet pack request |
| `/api/v1/bkash` | POST | Submits manual bKash add-balance request |
| `/api/v1/nagad` | POST | Submits manual Nagad add-balance request |
| `/api/v1/rocket` | POST | Submits manual Rocket add-balance request |
| `/api/v1/upay` | POST | Submits manual Upay add-balance request |
| `/api/v1/routed-settlement` | POST | Receives asynchronous routed-provider settlement updates |

### 11.5 API Response Characteristics

Common API behaviors include:

- JSON success/error responses
- Remaining balance returned after accepted requests
- Balance type returned where relevant
- Validation errors for missing or invalid payloads
- Service blocked responses when disabled by policy

### 11.6 Routed Provider Support

The product supports forwarding requests to upstream providers when matching API route rules exist. Routed flows include:

- Forwardable API route resolution
- Source request ID tracking
- Callback-based settlement through `/routed-settlement`
- Remote request ID storage
- Final approval, rejection, or cancellation sync

This makes the platform usable both as a direct service panel and as a routing hub.

---

## 12. Security Specification

### 12.1 Password Policy

The platform supports configurable strong password rules. When enabled, passwords require stronger validation than the basic minimum.

### 12.2 PIN Policy

Admin authentication uses PIN verification in addition to the password. PIN length and expiry behavior are configurable in security settings.

### 12.3 Google Authenticator OTP

The application includes its own TOTP implementation for Google Authenticator-style OTP. It supports:

- Secret generation
- OTPAuth URL generation
- 6-digit TOTP validation
- Admin and user OTP challenge flows

### 12.4 Registration/Login OTP

An OTP service supports registration and verification use cases through email or mobile-style channels.

### 12.5 Device Approval

New device login attempts can be blocked until approved. Device approval features include:

- Device log creation
- Device token cookie support
- Active/deactive device state tracking
- Automatic approval for first known device in some scenarios
- Admin review of device logs

### 12.6 Session Timeout

Authenticated sessions are monitored for inactivity. When the configured threshold is exceeded:

- The user is logged out
- Session is invalidated
- CSRF token is regenerated
- User is redirected to the correct login page with an expiry message

### 12.7 HTTPS Redirect

The product supports an HTTPS redirect security option at runtime.

### 12.8 Captcha and reCAPTCHA

The platform supports both login captcha behavior and reCAPTCHA based on admin security settings.

### 12.9 Service Blocking Controls

Security runtime rules can block operators or services, including operator-off enforcement for flexiload and internet pack transactions.

---

## 13. Reporting, Audit, and Operational Visibility

The product emphasizes operational tracking. Key visibility areas include:

- Recharge history
- Balance add history
- Pending request queues
- Balance reports
- Operator reports
- Daily reports
- Sales report
- Device logs
- Deleted account history/restore actions
- Payment transaction records

Admin notifications are also integrated with Firebase push notifications for selected request events.

---

## 14. Key Domain Entities

The exact schema is broader than this summary, but the product clearly revolves around these core entities:

| Entity | Role in Product |
|---|---|
| User | Admin/reseller identity, balances, permissions, API access |
| Operator | Telecom operator definition and availability |
| RegularPackage | Internet pack definition |
| DrivePackage | Drive offer definition |
| FlexiRequest | Recharge request record |
| RegularRequest | Internet request record |
| DriveRequest | Drive request record |
| ManualPaymentRequest | Manual add-balance request record |
| SslCommerzTransaction | Online payment transaction record |
| Api / ApiRoute | API connection and routing definitions |
| DeviceLog | Device approval and login audit data |
| Branding / HomepageSetting | Branding, public content, payment settings, security settings |
| BrandingSlide | Homepage slider image records |
| Complaint / Notice-related records | User support and communication modules |

---

## 15. Technical Architecture

### 15.1 Framework and Stack

- PHP 8.2
- Laravel 12
- Blade view layer
- Middleware-driven access control
- Service classes for OTP, Google OTP, device approval, security runtime, SSLCommerz integration

### 15.2 Architectural Style

The application is a server-rendered Laravel monolith with:

- Blade templates for public, user, and admin views
- Route-driven web features, including a number of route-closure workflows
- REST-like JSON API routes under `routes/api.php`
- Eloquent models for business entities
- Security and integration logic implemented in service classes and middleware

### 15.3 Integration Points

Current integration surfaces include:

- SSLCommerz payment gateway
- Firebase push notification service
- Mail/OTP delivery configuration
- Google Authenticator / TOTP apps
- External upstream provider APIs through routed forwarding logic

---

## 16. Functional Requirements Summary

### 16.1 User Requirements

- Users must be able to register with OTP verification
- Users must be able to log in securely
- Users must be able to add balance manually or online
- Users must be able to submit recharge, drive, and internet requests
- Users must be able to review histories and pending requests
- Users must be able to submit complaints

### 16.2 Admin Requirements

- Admins must be able to manage reseller accounts and balances
- Admins must be able to manage operators and packages
- Admins must be able to review and settle pending requests
- Admins must be able to configure payment gateway, API, security, and branding
- Admins must be able to access operational reports and logs

### 16.3 API Requirements

- Approved users must be able to authenticate via API key
- API consumers must be able to check balances and submit service requests
- The system must support routed settlement for forwarded provider requests

### 16.4 Payment Requirements

- Manual deposit requests must support approval-based wallet credit
- SSLCommerz payments must validate callback data before crediting balance
- Online payment credit must be idempotent

---

## 17. Non-Functional Requirements

### 17.1 Reliability

- Balance updates should avoid duplicate crediting or double charging
- Payment verification should be server-side validated where supported
- Routed settlement should update final state consistently

### 17.2 Security

- Sensitive flows should be protected by auth, permissions, OTP, or session controls as configured
- API endpoints should reject unauthorized requests
- Device anomalies should be reviewable by admins

### 17.3 Auditability

- Requests, histories, device logs, and transactions should remain reviewable from admin tools

### 17.4 Maintainability

- Branding and operational settings should remain configurable from admin UI
- Service toggles should allow business rule changes without code edits in common scenarios

### 17.5 Usability

- Public, user, and admin interfaces should be accessible from server-rendered pages
- Mobile and desktop usage should be supported through responsive Blade layouts

---

## 18. Assumptions, Constraints, and Known Notes

1. The codebase reflects an actively customized telecom panel and some labels intentionally preserve business wording such as **Security Modual**.
2. Some admin sidebar items appear to be placeholders or partially implemented modules, so not every visible menu entry necessarily represents a complete workflow.
3. A significant portion of business logic lives in route files, especially `routes/web.php` and `routes/api.php`, which is important for future maintenance planning.
4. Payment, OTP, SMS, Firebase, and provider routing behavior depend on proper runtime configuration.
5. Manual payment channels are only actionable when the corresponding numbers/settings are configured.
6. API behavior depends on user API approval, allowed services, and available upstream routing definitions.

---

## 19. Launch/Readiness Checklist for Product Review

Before using the project in a realistic environment, the following business-critical areas should be confirmed:

- Homepage branding and public content are configured
- Operators and packages are populated and active
- Reseller roles/levels and permissions are aligned with business policy
- Deposit and opening-balance settings are correct
- Manual payment numbers are configured
- SSLCommerz credentials are configured for sandbox or live mode
- Security Modual settings are reviewed
- Google OTP, mail, SMS, and Firebase settings are configured as needed
- API keys, API access flags, and upstream routing rules are configured if partner API use is required
- Pending request and reporting modules are accessible to the right admin accounts

---

## 20. Conclusion

Codecartel Telecom is a multi-role telecom operations platform that combines reseller wallet management, telecom service ordering, admin operational control, online/manual payment support, and provider API capabilities inside one Laravel application.

Its current implementation is strongest in these areas:

- Multi-balance telecom operations
- Admin-driven service and security control
- Flexible reseller hierarchy support
- Online and manual balance funding options
- Partner/provider API enablement

This document should be treated as the current-state product specification for the existing codebase and can be used as the foundation for future PRD, module documentation, QA planning, or deployment preparation.