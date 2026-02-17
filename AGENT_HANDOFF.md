# Agent Handoff Document
## Expert Medical Center - Easy!Appointments

**Date:** Feb 15, 2026
**Project Root:** `/Users/mkuzayez/Documents/Development/laravel/easyappointments`
**PHP Version:** 8.4.18 (via Homebrew)
**Framework:** CodeIgniter 3

---

## 🚀 Running the Project

```bash
# Dependencies already installed. Just start:
npm start                          # Gulp file watcher (auto-compiles SCSS)
php -S localhost:8000 -t .         # PHP dev server
brew services start mysql          # MySQL (if not running)
```

**DB:** MySQL, database = `easyappointments`, user = `root`, no password
**Admin login:** http://localhost:8000/index.php/login → username: `raizer`, password: `admin123`

---

## 📚 Reference Documents (READ THESE FIRST)

| File | Purpose |
|------|---------|
| `BACKEND_REQUIREMENTS_GAP_ANALYSIS.md` | Full backend spec with DB schemas, models, controllers - **primary reference** |
| `FEATURES_ROADMAP.md` | Overall feature plan and priorities |
| `USER_TYPES.md` | All user roles and permissions |
| `API_DOCUMENTATION.md` | Existing API endpoints and usage |
| `openapi.yml` | OpenAPI spec (keep updated after changes) |

---

## ✅ What's Already Built (DO NOT REBUILD)

### Migrations (run & applied)
- `061_create_branches_table.php` - Dubai + Abu Dhabi branches
- `062_add_branch_to_services.php` - Branch FK on services
- `063_create_provider_branches_table.php` - Provider-branch M2M
- `064_add_payment_fields_to_appointments.php` - Stripe fields on appointments
- `065_add_preferred_language.php` - `preferred_language` column on `ea_users`
- `066_add_doctor_profile_fields.php` - `photo`, `bio`, `qualifications`, `specialty` on `ea_users`

### Models
- `application/models/Branches_model.php` - Full CRUD
- `application/models/Appointments_model.php` - Updated with payment fields
- `application/models/Customers_model.php` - Updated with `preferredLanguage`
- `application/models/Providers_model.php` - Updated with doctor profile fields + branch methods

### API Controllers
- `application/controllers/api/v1/Branches_api_v1.php` - Full CRUD
- `application/controllers/api/v1/Payments_api_v1.php` - `create_session` + `verify`
- `application/controllers/api/v1/Appointments_api_v1.php` - Updated with Stripe + multilingual email

### Libraries
- `application/libraries/Stripe_payment.php` - `create_checkout_session()`, `verify_payment()`, `get_session()`, `get_payment_intent()`
- `application/libraries/Multilingual_notifications.php` - `send_appointment_confirmation()` (AR/EN)

### Email Templates (only these two exist)
- `application/views/emails/en/appointment_confirmation.php`
- `application/views/emails/ar/appointment_confirmation.php`

### Config
- `config.php` - Has `STRIPE_SECRET_KEY`, `STRIPE_PUBLISHABLE_KEY`, `STRIPE_SUCCESS_URL`, `STRIPE_CANCEL_URL`
- `config-sample.php` - Same structure (no real keys)

### Routes (in `application/config/routes.php`)
```php
route_api_resource($route, 'branches', 'api/v1/');
$route['api/v1/payments/create-session']['post'] = 'api/v1/payments_api_v1/create_session';
$route['api/v1/payments/verify']['post'] = 'api/v1/payments_api_v1/verify';
```

---

## 🔨 What Needs to Be Built (YOUR TASKS)

---

### TASK 1: Patient Feedback System

**Schema is already defined in `BACKEND_REQUIREMENTS_GAP_ANALYSIS.md` → Migration 068**

#### 1a. Migration
**File to create:** `application/migrations/067_create_patient_feedback_table.php`

Table: `patient_feedback`
Columns: `id`, `id_appointments`, `id_users_customer`, `id_users_provider`, `rating` (1-5), `feedback_text`, `feedback_category` (ENUM: service/doctor/facility/overall), `is_approved`, `submitted_date`, `approved_date`, `create_datetime`, `update_datetime`

Look at existing migrations (e.g. `065_add_preferred_language.php`) for the correct CodeIgniter migration pattern.

#### 1b. Model
**File to create:** `application/models/Patient_feedback_model.php`

- Extend `EA_Model`
- Follow same pattern as `Branches_model.php` (has `casts`, `api_resource`, `save()`, `validate()`, `find()`, `get()`, `delete()`, `api_encode()`, `api_decode()`)
- Add extra methods: `approve(int $id)`, `get_average_rating(int $provider_id)`, `exists_for_appointment(int $appointment_id)`, `get_approved_by_provider(int $provider_id)`
- Full schema in `BACKEND_REQUIREMENTS_GAP_ANALYSIS.md` → Section 2.4

#### 1c. API Controller
**File to create:** `application/controllers/api/v1/Feedback_api_v1.php`

- Extend `EA_Controller`
- Follow same pattern as `Branches_api_v1.php`
- Methods: `index()` (with filters: providerId, customerId, appointmentId, approved), `show(id)`, `store()`, `update(id)`, `destroy(id)`
- Extra methods: `approve(id)` (admin only), `provider_rating(id)`
- Prevent duplicate feedback per appointment
- Full spec in `BACKEND_REQUIREMENTS_GAP_ANALYSIS.md` → Section 3.5

#### 1d. Routes
**File to modify:** `application/config/routes.php`

Add:
```php
route_api_resource($route, 'feedback', 'api/v1/');
$route['api/v1/feedback/(:num)/approve']['patch'] = 'api/v1/feedback_api_v1/approve/$1';
$route['api/v1/feedback/provider/(:num)/rating']['get'] = 'api/v1/feedback_api_v1/provider_rating/$1';
```

#### 1e. Email - Feedback Request
Two templates needed (see Task 2 below for format reference).
Triggered after appointment is marked complete - to send to patient asking for feedback.

---

### TASK 2: Email Templates

**All templates must follow the same pattern as the existing:**
- `application/views/emails/en/appointment_confirmation.php` ← **copy this structure**
- `application/views/emails/ar/appointment_confirmation.php` ← **copy this structure for RTL/Arabic**

Branding: `#3D2814` header color, "Expert Medical Center" / "مركز الخبراء الطبي"

**Files to create (8 total):**

#### English Templates:
- `application/views/emails/en/appointment_cancelled.php`
- `application/views/emails/en/appointment_rescheduled.php`
- `application/views/emails/en/payment_pending.php`
- `application/views/emails/en/feedback_request.php`

#### Arabic Templates (RTL, `dir="rtl"`):
- `application/views/emails/ar/appointment_cancelled.php`
- `application/views/emails/ar/appointment_rescheduled.php`
- `application/views/emails/ar/payment_pending.php`
- `application/views/emails/ar/feedback_request.php`

**PHP variables available in each template** (pass from controller):
```php
$customer_name, $provider_name, $appointment_date, $appointment_time,
$service_name, $branch_name, $branch_phone,
$payment_amount, $payment_currency,  // payment_pending only
$reschedule_link, $cancel_link,       // rescheduled only
$feedback_link                        // feedback_request only
```

#### After creating templates, update `Multilingual_notifications.php` to add:
- `send_appointment_cancelled(array $appointment, array $provider, array $service, array $customer, ?string $language = null): void`
- `send_appointment_rescheduled(array $appointment, array $provider, array $service, array $customer, ?string $language = null): void`
- `send_payment_pending(array $appointment, array $provider, array $service, array $customer, ?string $language = null): void`
- `send_feedback_request(array $appointment, array $provider, array $customer, string $feedback_link, ?string $language = null): void`

Follow the exact same pattern as `send_appointment_confirmation()` already in that file.

---

### TASK 3: Refund Processing

**Spec already defined in `BACKEND_REQUIREMENTS_GAP_ANALYSIS.md` → Section 4.1 (Stripe_payment) and Section 3.2 (Payments_api_v1)**

#### 3a. Extend `Stripe_payment.php`
**File to modify:** `application/libraries/Stripe_payment.php`

Add method:
```php
public function create_refund(string $payment_intent_id, ?float $amount = null): array
// Returns: ['refund_id' => ..., 'status' => ..., 'amount' => ..., 'currency' => ...]
// If $amount is null → full refund
// If $amount is specified → partial refund
// Uses \Stripe\Refund::create()
// Throws RuntimeException on Stripe API error
```

#### 3b. Extend `Payments_api_v1.php`
**File to modify:** `application/controllers/api/v1/Payments_api_v1.php`

Add method:
```php
public function refund(int $id): void
// POST /api/v1/payments/{id}/refund
// Body: { "appointmentId": 123, "amount": 250.00, "reason": "Patient cancelled" }
// amount is optional (null = full refund)
// Calls $this->stripe_payment->create_refund(payment_intent_id, amount)
// Updates appointment: payment_status = 'refunded', refund_amount, refund_status = 'completed', refund_reason
// Loads appointments_model to update the record
// Returns refund confirmation JSON
```

#### 3c. Add Route
**File to modify:** `application/config/routes.php`

Add:
```php
$route['api/v1/payments/(:num)/refund']['post'] = 'api/v1/payments_api_v1/refund/$1';
```

---

## 📐 Code Patterns to Follow

### Migration Pattern
```php
<?php defined('BASEPATH') or exit('No direct script access allowed');
class Migration_Xyz extends EA_Migration {
    public function up(): void { ... }
    public function down(): void { ... }
}
```
Reference: `application/migrations/065_add_preferred_language.php`

### Model Pattern
Reference: `application/models/Branches_model.php`
- Must have: `$casts`, `$api_resource`, `save()`, `validate()`, `find()`, `get()`, `delete()`, `api_encode()`, `api_decode()`

### API Controller Pattern
Reference: `application/controllers/api/v1/Branches_api_v1.php`
- `__construct()` must call `$this->api->auth()`
- Use `request()` to get payload
- Use `json_response()` to return data
- Use `json_exception($e)` in catch blocks
- HTTP constants: `CREATED`, `NO_CONTENT`

### Library Pattern
Reference: `application/libraries/Stripe_payment.php` and `Multilingual_notifications.php`
- `$this->CI = &get_instance();` in constructor

---

## ⚠️ Important Notes

1. **Run migrations** after creating them:
   - The app auto-runs migrations on page load if migration is enabled
   - Or trigger via `/index.php/update`

2. **No patient portal** - system uses guest-only booking (no patient accounts)

3. **Stripe keys** are in `config.php` - already configured for sandbox

4. **Theme** is set to `flatly` in `ea_settings` DB table

5. **Rate limiting** is disabled in `application/config/config.php` (`rate_limiting = FALSE`)

6. **SCSS changes** auto-compile via Gulp watcher

7. **Table prefix** - All tables use `ea_` prefix (e.g. `ea_appointments`, `ea_users`) EXCEPT the new custom tables (branches, patient_feedback) which have no prefix - follow existing pattern in migration files

8. **Do NOT modify** `composer.lock` or run `composer update` - PHP 8.4 only, Stripe SDK already installed

---

## 🔗 API Endpoints After Completion

```
# Feedback
GET    /api/v1/feedback
GET    /api/v1/feedback/{id}
POST   /api/v1/feedback
PUT    /api/v1/feedback/{id}
DELETE /api/v1/feedback/{id}
PATCH  /api/v1/feedback/{id}/approve
GET    /api/v1/feedback/provider/{id}/rating

# Refunds
POST   /api/v1/payments/{id}/refund
```

---

## 📋 Checklist

### Feedback System
- [ ] `application/migrations/067_create_patient_feedback_table.php`
- [ ] `application/models/Patient_feedback_model.php`
- [ ] `application/controllers/api/v1/Feedback_api_v1.php`
- [ ] `application/config/routes.php` - add feedback routes

### Email Templates
- [ ] `application/views/emails/en/appointment_cancelled.php`
- [ ] `application/views/emails/ar/appointment_cancelled.php`
- [ ] `application/views/emails/en/appointment_rescheduled.php`
- [ ] `application/views/emails/ar/appointment_rescheduled.php`
- [ ] `application/views/emails/en/payment_pending.php`
- [ ] `application/views/emails/ar/payment_pending.php`
- [ ] `application/views/emails/en/feedback_request.php`
- [ ] `application/views/emails/ar/feedback_request.php`
- [ ] `application/libraries/Multilingual_notifications.php` - add 4 new methods

### Refund Processing
- [ ] `application/libraries/Stripe_payment.php` - add `create_refund()`
- [ ] `application/controllers/api/v1/Payments_api_v1.php` - add `refund()`
- [ ] `application/config/routes.php` - add refund route

### Documentation
- [ ] `openapi.yml` - add new endpoints (feedback + refund)

---

*End of Handoff Document*
