# Plan: Close PRD Gaps — Expert Medical Center Backend

## Context

The 3-day implementation covered the critical path (branches, Stripe checkout, doctor profiles, bilingual emails for confirmations). This plan closes the remaining gaps between the PRD and the live codebase, in priority order. All work is backend-only (CodeIgniter 3 + PHP 8.4). The Next.js frontend is a separate repo.

**Reference files:**
- `AGENT_HANDOFF.md` — original task specs
- `BACKEND_REQUIREMENTS_GAP_ANALYSIS.md` — DB schemas and API specs
- `application/controllers/api/v1/Branches_api_v1.php` — API controller pattern to follow
- `application/models/Branches_model.php` — model pattern to follow
- `application/libraries/Stripe_payment.php` — library to extend
- `application/libraries/Multilingual_notifications.php` — library to extend
- `application/controllers/api/v1/Appointments_api_v1.php` — notify flow to update
- `application/views/emails/en/appointment_confirmation.php` — email template to copy

---

## Phase 1 — Service Price Enforcement (Security Fix)

**Why:** PRD §4 states consultation fees are fixed per service. Currently `POST /api/v1/payments/create-session` accepts `amount` from the request body — a malicious client could pay 1 AED for a 250 AED consultation.

**Files to modify:**
- `application/controllers/api/v1/Payments_api_v1.php`

**Change:** In `create_session()`, if `serviceId` is provided in the request body, load the service record via `$this->services_model->find($serviceId)` and use `$service['price']` as the amount (ignore any client-supplied `amount`). If no `serviceId`, fall back to the supplied `amount` (for admin use cases).

```php
if (!empty($request['serviceId'])) {
    $this->load->model('services_model');
    $service = $this->services_model->find((int)$request['serviceId']);
    $data['amount'] = (float)$service['price'];
    $data['currency'] = $service['currency'] ?? 'AED';
}
```

---

## Phase 2 — Refund Fields Migration + Model Update

**Why:** Refund fields (`refund_amount`, `refund_status`, `refund_reason`) were in the gap analysis schema but not added in migration 064. Required before building the refund endpoint.

### 2a. New migration
**File to create:** `application/migrations/067_add_refund_fields_to_appointments.php`

Fields to add to `ea_appointments`:
- `refund_amount` — DECIMAL(10,2), nullable, after `payment_currency`
- `refund_status` — VARCHAR(20), default `'none'`, after `refund_amount`
- `refund_reason` — TEXT, nullable, after `refund_status`

Add index on `refund_status`.

### 2b. Update Appointments model
**File to modify:** `application/models/Appointments_model.php`

Add to `$casts`:
```php
'refund_amount' => 'float',
```

Add to `$api_resource`:
```php
'refundAmount'   => 'refund_amount',
'refundStatus'   => 'refund_status',
'refundReason'   => 'refund_reason',
```

---

## Phase 3 — Stripe Refund Integration

**Why:** PRD F-09 requires refund processing on cancellation.

### 3a. Extend Stripe_payment library
**File to modify:** `application/libraries/Stripe_payment.php`

Add method:
```php
public function create_refund(string $payment_intent_id, ?float $amount = null): array
// Calls \Stripe\Refund::create(['payment_intent' => $payment_intent_id, 'amount' => $amount_cents])
// Returns: ['refund_id', 'status', 'amount', 'currency']
// null $amount = full refund; otherwise partial (convert to cents)
// Throws RuntimeException on Stripe API error
```

### 3b. Add refund endpoint to Payments API
**File to modify:** `application/controllers/api/v1/Payments_api_v1.php`

Add method:
```php
public function refund(int $id): void
// POST /api/v1/payments/{id}/refund
// Body: { "appointmentId": 123, "amount": 250.00 (optional), "reason": "Patient cancelled" }
// 1. Load appointment by appointmentId
// 2. Validate payment_status == 'paid' and stripe_payment_intent_id is set
// 3. Call $this->stripe_payment->create_refund(payment_intent_id, amount)
// 4. Update appointment: payment_status='refunded', refund_amount, refund_status='completed', refund_reason
// 5. Return refund confirmation JSON
```

### 3c. Register route
**File to modify:** `application/config/routes.php`

Add:
```php
$route['api/v1/payments/(:num)/refund']['post'] = 'api/v1/payments_api_v1/refund/$1';
```

---

## Phase 4 — Email Templates (8 files)

**Why:** PRD F-08/F-09 require confirmation, cancellation, reschedule, and offline payment emails. Feedback request email required for F-10.

Copy structure from `application/views/emails/en/appointment_confirmation.php` and `ar/appointment_confirmation.php`.
Branding: header `#3D2814`, buttons `#654321`.

### Files to create (EN — LTR, `lang="en"`):
- `application/views/emails/en/appointment_cancelled.php`
  - Variables: `$customer_name`, `$provider_name`, `$appointment_date`, `$appointment_time`, `$service_name`, `$branch_name`, `$branch_phone`, `$refund_amount` (optional), `$refund_currency`

- `application/views/emails/en/appointment_rescheduled.php`
  - Variables: same as confirmation + `$new_appointment_date`, `$new_appointment_time`, `$reschedule_link`, `$cancel_link`

- `application/views/emails/en/payment_pending.php`
  - Variables: `$customer_name`, `$provider_name`, `$appointment_date`, `$appointment_time`, `$service_name`, `$branch_name`, `$payment_amount`, `$payment_currency`, `$payment_link`

- `application/views/emails/en/feedback_request.php`
  - Variables: `$customer_name`, `$provider_name`, `$appointment_date`, `$service_name`, `$feedback_link`

### Files to create (AR — RTL, `lang="ar" dir="rtl"`):
Same 4 templates with Arabic text, `border-right` instead of `border-left` on `.details`.

### Extend Multilingual_notifications library
**File to modify:** `application/libraries/Multilingual_notifications.php`

Add 4 methods following the exact pattern of `send_appointment_confirmation()`:

```php
public function send_appointment_cancelled(array $appointment, array $service, array $provider, array $customer, ?string $language = null): void

public function send_appointment_rescheduled(array $appointment, array $service, array $provider, array $customer, ?string $language = null): void

public function send_payment_pending(array $appointment, array $service, array $provider, array $customer, string $payment_link, ?string $language = null): void

public function send_feedback_request(array $appointment, array $provider, array $customer, string $feedback_link, ?string $language = null): void
```

---

## Phase 5 — Wire Email Triggers in Appointments API

**Why:** The new notification methods need to be called at the right lifecycle points.

**File to modify:** `application/controllers/api/v1/Appointments_api_v1.php`

In `notify_and_sync_appointment(array $appointment, string $action)`:

| Condition | Email to Send |
|-----------|--------------|
| `$action == 'store'` AND `payment_method == 'offline'` | `send_payment_pending()` |
| `$action == 'update'` AND status changed to `'cancelled'` | `send_appointment_cancelled()` |
| `$action == 'update'` AND `start_datetime` changed | `send_appointment_rescheduled()` |
| `$action == 'update'` AND status changed to `'completed'` | `send_feedback_request()` with `site_url('feedback/' . $appointment['hash'])` |

Pass the **original appointment** (before update) to detect what changed — load it at the top of `update()` before calling `save()`. The `notify_and_sync_appointment()` signature must accept an optional `$original_appointment` parameter.

---

## Phase 6 — Patient Feedback System

**Why:** PRD F-10 (Should Have) + ISO 9001 audit requirement.

### 6a. Migration
**File to create:** `application/migrations/068_create_patient_feedback_table.php`

Table: `patient_feedback` (no `ea_` prefix — follow custom table convention)
Columns: `id`, `id_appointments`, `id_users_customer`, `id_users_provider`, `rating` TINYINT(1), `feedback_text` TEXT, `feedback_category` ENUM('service','doctor','facility','overall'), `is_approved` TINYINT(1) default 0, `submitted_date` DATETIME, `approved_date` DATETIME, `create_datetime`, `update_datetime`

FKs: appointments (CASCADE), users customer (CASCADE), users provider (CASCADE)
Indexes: id_appointments, id_users_provider, rating, is_approved

### 6b. Model
**File to create:** `application/models/Patient_feedback_model.php`

Extend `EA_Model`. Follow `Branches_model.php` pattern.

`$api_resource`:
```php
'id'               => 'id',
'appointmentId'    => 'id_appointments',
'customerId'       => 'id_users_customer',
'providerId'       => 'id_users_provider',
'rating'           => 'rating',
'feedbackText'     => 'feedback_text',
'feedbackCategory' => 'feedback_category',
'isApproved'       => 'is_approved',
'submittedDate'    => 'submitted_date',
'approvedDate'     => 'approved_date',
```

Extra methods:
- `approve(int $id): void` — sets is_approved=1, approved_date=now()
- `get_average_rating(int $provider_id): float`
- `exists_for_appointment(int $appointment_id): bool` — prevent duplicate submissions
- `get_approved_by_provider(int $provider_id): array`

`validate()` must enforce: rating 1-5, appointment exists, no duplicate per appointment.

### 6c. Controller
**File to create:** `application/controllers/api/v1/Feedback_api_v1.php`

Extend `EA_Controller`. Follow `Branches_api_v1.php` pattern.

Methods:
- `index()` — GET /api/v1/feedback — filter by `providerId`, `customerId`, `appointmentId`, `approved`
- `show(int $id)` — GET /api/v1/feedback/{id}
- `store()` — POST /api/v1/feedback — check `exists_for_appointment()` before saving; auto-set `submitted_date`
- `update(int $id)` — PUT /api/v1/feedback/{id}
- `destroy(int $id)` — DELETE /api/v1/feedback/{id}
- `approve(int $id)` — PATCH /api/v1/feedback/{id}/approve
- `provider_rating(int $id)` — GET /api/v1/feedback/provider/{id}/rating → returns `{ "providerId": X, "averageRating": 4.3, "totalReviews": 12 }`

### 6d. Routes
**File to modify:** `application/config/routes.php`

Add:
```php
route_api_resource($route, 'feedback', 'api/v1/');
$route['api/v1/feedback/(:num)/approve']['patch'] = 'api/v1/feedback_api_v1/approve/$1';
$route['api/v1/feedback/provider/(:num)/rating']['get'] = 'api/v1/feedback_api_v1/provider_rating/$1';
```

---

## Phase 7 — Availabilities Branch Filtering

**Why:** PRD F-05 — each branch operates independently. Low-lift addition.

**File to modify:** `application/controllers/api/v1/Availabilities_api_v1.php`

In `get()`, accept optional `branchId` query param. If provided, validate the requested provider is associated with that branch via `provider_branches` table before returning slots. Return 404/empty if provider is not at that branch.

---

## Phase 8 — openapi.yml Updates

After all phases, update `openapi.yml` with:
- `POST /api/v1/payments/{id}/refund` — request/response schema
- `GET|POST|PUT|DELETE /api/v1/feedback` — full CRUD + approve + provider_rating
- Update `Appointment` schema with `refundAmount`, `refundStatus`, `refundReason`
- Note `serviceId` param on `POST /api/v1/payments/create-session`
- Update `GET /api/v1/availabilities` with optional `branchId` param

---

## Execution Order

| # | Phase | Files Created | Files Modified |
|---|-------|--------------|----------------|
| 1 | Service price enforcement | — | Payments_api_v1 |
| 2 | Refund fields migration | 067_add_refund_fields_to_appointments | Appointments_model |
| 3 | Stripe refund | — | Stripe_payment, Payments_api_v1, routes |
| 4 | Email templates | 8 template files | Multilingual_notifications |
| 5 | Wire email triggers | — | Appointments_api_v1 |
| 6 | Feedback system | 068_create_patient_feedback_table, Patient_feedback_model, Feedback_api_v1 | routes |
| 7 | Availabilities branch filter | — | Availabilities_api_v1 |
| 8 | openapi.yml | — | openapi.yml |

**Run after migrations:** `php index.php console migrate`

---

## Verification Checklist

- [ ] `POST /api/v1/payments/create-session` with `serviceId=1` and wrong `amount` → price comes from service record
- [ ] `POST /api/v1/payments/1/refund` with a paid appointment → Stripe refund created, appointment `refundStatus='completed'`
- [ ] Cancellation email: update appointment status to `'cancelled'` → customer receives AR/EN cancel email
- [ ] Offline booking: create appointment with `paymentMethod='offline'` → customer receives payment_pending email
- [ ] Appointment completed: update status to `'completed'` → feedback_request email sent
- [ ] `POST /api/v1/feedback` → feedback saved, duplicate on same appointment rejected
- [ ] `PATCH /api/v1/feedback/1/approve` → `isApproved` becomes true
- [ ] `GET /api/v1/feedback/provider/1/rating` → average rating returned
- [ ] `GET /api/v1/availabilities?providerId=1&serviceId=1&branchId=2` → empty if provider not at branch 2
