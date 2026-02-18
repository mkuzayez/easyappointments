# Expert Medical Center PRD Checklist

Generated: 2026-02-18
Scope: Compare `Expert_Medical_Center_PRD.md` against current backend repo state.

## Legend
- `Done`: Implemented in current codebase.
- `Partial`: Some implementation exists, but not complete for PRD acceptance.
- `Deferred`: Intentionally to be implemented in the external Next.js frontend.
- `Missing`: Not implemented.

## Feature Checklist (F-01 .. F-11)

| ID | PRD Requirement | Status | Evidence | Gap / Action |
|---|---|---|---|---|
| F-01 | Custom branded frontend (Next.js, luxury UI) | Deferred | Current stack is CodeIgniter + jQuery (`package.json`, `application/views/...`) | Build in external Next.js app; deprecate current public UI. |
| F-02 | Bilingual AR/EN with RTL/LTR, language toggle, persistence | Partial | Language system exists (`application/controllers/Localization.php`, `assets/js/utils/lang.js`), AR email RTL templates exist | Full RTL/LTR mirrored UX should be delivered in Next.js frontend. |
| F-03 | Easy Appointments API integration (real-time availability, booking, branch-aware) | Partial | APIs exist for availabilities/services/providers/appointments/branches | Public frontend consumption blocked by API auth model (admin/basic bearer). Add public booking-safe API surface or BFF pattern. |
| F-04 | Stripe payment integration, payment before appointment confirmation | Done | Stripe APIs + enforcement in API flow and legacy booking flow (`application/controllers/api/v1/Appointments_api_v1.php`, `application/controllers/Booking.php`) | Completed: payment gate now enforced in `Booking::register()` with online/offline/free/reschedule handling. |
| F-05 | Branch selection early in booking flow (Dubai/Abu Dhabi separation) | Partial | Branch DB + APIs + filters implemented (`branches`, `provider_branches`, `branchId` filters) | Public flow is not branch-first today. Implement in Next.js flow and keep branch filtering in API calls. |
| F-06 | Department pages (9 specialties), SEO URLs/meta | Deferred | No dedicated public department pages in this backend UI | Implement in Next.js content layer. |
| F-07 | Doctor profiles page (photo, specialty, qualifications, bio, direct booking) | Partial | Provider model/API contains `photo`, `bio`, `qualifications`, `specialty` | Backend admin page currently does not expose full profile fields; no public profile pages. Add admin inputs/API usage + Next.js profile pages. |
| F-08 | Branded email confirmations with date/time/doctor/branch/amount, AR/EN | Done | Multilingual templates + notification library + links exist | Keep SMTP production config and test end-to-end. |
| F-09 | Cancellation/rescheduling + refund handling policy | Partial | Cancellation/reschedule links, cancellation endpoint, Stripe refund endpoint exist | Add/confirm cancellation policy enforcement logic and integrate policy in frontend/business rules. |
| F-10 | Patient feedback module | Done | `patient_feedback` migration + model + API + provider rating endpoint | Add frontend form/view in Next.js if needed for launch scope. |
| F-11 | Offline payment option | Partial | API supports `paymentMethod=offline` and pending flow | Not available in legacy public booking wizard UI; include in Next.js flow if kept in scope. |

## High-Priority Fixes (Backend)

### P1 - Payment enforcement in public booking flow
- Status: Done.
- Implemented in: `application/controllers/Booking.php` (`register()` flow).
- Summary: Payment columns whitelisted; Stripe session verification + service/amount validation + replay protection; offline pending path; free-service exemption; reschedule exemption.

### P1 - Public API access model for frontend
- Problem: Current v1 API requires admin/basic auth for all booking endpoints.
- Affected: `application/libraries/Api.php` and API controllers using `$this->api->auth()`.
- Options:
  1. Add public, minimal, booking-only endpoints (service/provider/availability/read + appointment create with payment verification).
  2. Keep API private and use Next.js server/BFF to call backend with protected token.
- Recommended: Option 2 for security + cleaner exposure.

## Non-Feature PRD / NFR Check

| Area | Status | Notes |
|---|---|---|
| ISO/Compliance controls | Partial | Technical pieces exist (feedback, self-hosting), but formal ISO controls/process are outside code alone. |
| Mobile performance (<3s) | Deferred | To be measured in Next.js frontend implementation. |
| SEO for specialties | Deferred | Frontend/content implementation needed. |
| Security headers/CORS hardening | Partial | Current CORS allows all origins in routes config; restrict in production. |

## Suggested Next Execution Order
1. Decide API exposure pattern (public endpoints vs Next.js BFF) and implement.
2. Add missing provider profile fields to backend admin UI (if content will be managed here).
3. Proceed with Next.js frontend delivery for branch-first booking + department/profile pages + bilingual RTL UX.
