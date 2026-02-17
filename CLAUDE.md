# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Context

This is **Easy!Appointments v1.5.2** (CodeIgniter 3 / PHP 8.4) customized for **Expert Medical Center** — a UAE medical booking system with Dubai and Abu Dhabi branches. The Next.js frontend consumes the REST API.

**9 Medical Specialties:** Dental, Dermatology, Gynecology, Pediatrics, Sensory Room (Autism), + 4 TBD. In the backend these are **`service_categories`** — use `GET /api/v1/service_categories` filtered by branch for the department selection screen.

**Consultation fees are fixed per service** (not variable). When creating a Stripe checkout session, the `amount` must be sourced from the service's `price` field — never supplied freely by the frontend.

**Patient intake fields:** The booking form collects symptoms and insurance information. These map to `customField1` and `customField2` on the customer record.

## Development Commands

```bash
# Start dev environment
php -S localhost:8000 -t .     # PHP dev server
npm start                       # Gulp file watcher (auto-compiles SCSS → assets/css/)
brew services start mysql       # MySQL if not running

# Database migrations
php index.php console migrate          # Run pending migrations
php index.php console migrate:status   # Check migration status

# Alternatively, migrations auto-run by visiting:
# http://localhost:8000/index.php/update

# Tests
composer test                          # All tests (APP_ENV=testing)
APP_ENV=testing php vendor/bin/phpunit tests/Unit/Helper/ArrayHelperTest.php  # Single test file

# Logs
tail -f storage/logs/log-*.php
```

**DB:** MySQL, database = `easyappointments`, user = `root`, no password
**Admin:** `http://localhost:8000/index.php/login` → `raizer` / `admin123`

## Architecture Overview

### Framework: CodeIgniter 3 (non-Laravel, despite directory name)
- Entry point: `index.php` at project root
- No `.env` file — config lives in `application/config/config.php`
- All tables use `ea_` prefix (e.g. `ea_appointments`, `ea_users`) **except** the custom tables added in this project (`branches`, `provider_branches`, `patient_feedback`) which have no prefix

### Request Flow
```
HTTP → index.php → system/core/CodeIgniter.php
     → application/config/routes.php (routing)
     → application/controllers/        (web UI controllers)
     → application/controllers/api/v1/ (REST API controllers)
     → application/models/             (data layer)
```

### API Layer (`application/controllers/api/v1/`)
Every API controller follows this exact pattern:
```php
class Foo_api_v1 extends EA_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('foo_model');
        $this->load->library('api');
        $this->api->auth();           // required — enforces Bearer token
        $this->api->model('foo_model');
    }
    // index(), show($id), store(), update($id), destroy($id)
}
```
- Use `request()` to get decoded JSON body
- Use `json_response($data, $status)` to respond
- Use `json_exception($e)` in all catch blocks
- HTTP status constants: `CREATED` (201), `NO_CONTENT` (204)

### Model Layer (`application/models/`)
Every model extends `EA_Model` and must declare:
```php
protected array $casts = ['id' => 'integer', 'is_boolean_field' => 'boolean'];
protected array $api_resource = ['camelCaseKey' => 'db_column_name'];
```
Standard methods: `save(array)`, `validate(array)`, `find(int)`, `get(?array $where, $limit, $offset, $order_by)`, `delete(int)`, `api_encode(array &$row)`, `api_decode(array &$row)`

Reference implementations: `Branches_model.php` (simplest complete example), `Providers_model.php` (includes relations).

### Routing
API resources are registered with a helper in `application/config/routes.php`:
```php
route_api_resource($route, 'resource_name', 'api/v1/');
// Auto-creates: GET /api/v1/resource_name, GET /{id}, POST, PUT /{id}, DELETE /{id}
```
Non-standard routes are declared explicitly below the resource registrations.

### Migrations (`application/migrations/`)
Numbered sequentially (061–066 implemented). Pattern:
```php
class Migration_Descriptive_name extends EA_Migration {
    public function up(): void { ... }
    public function down(): void { ... }
}
```

### Custom Libraries (`application/libraries/`)
- `Stripe_payment.php` — wraps `stripe/stripe-php` SDK; methods: `create_checkout_session()`, `verify_payment()`, `get_session()`, `get_payment_intent()`
- `Multilingual_notifications.php` — extends `Notifications`; sends branded AR/EN HTML emails from `views/emails/{lang}/`

All libraries use `$this->CI =& get_instance();` for CI access.

### Email Templates (`application/views/emails/`)
```
emails/en/appointment_confirmation.php   ← LTR, English
emails/ar/appointment_confirmation.php   ← RTL (dir="rtl"), Arabic
```
Branding: Primary `#3D2814` (header), Secondary `#654321` (accents/buttons), "Expert Medical Center" / "مركز الخبراء الطبي". Variables are passed via PHP `extract()` and rendered with `ob_start()/ob_get_clean()`.

### Stripe Configuration
Stripe keys are set as `Config` class constants in `application/config/config.php` (not in the `settings` table). Accessed via `config('stripe_secret_key')`. **Do not** `composer update` — PHP 8.4 only, all deps installed.

## What's Built vs. Pending

**Implemented (migrations 061–066):** branches CRUD, provider-branch M2M, Stripe payment + verification, payment fields on appointments, doctor profile fields (photo/bio/qualifications/specialty), preferred language on customers.

**Pending (see `AGENT_HANDOFF.md` for full task specs):**
1. **Patient feedback system** — migration 067, `Patient_feedback_model`, `Feedback_api_v1`. Required for ISO 9001 audit compliance.
2. **Additional email templates** — cancelled, rescheduled, payment_pending, feedback_request (EN + AR)
3. **Cancellation/refund flow** — full sequence: cancel appointment → `create_refund()` in `Stripe_payment` → update `payment_status='refunded'` on appointment → release slot → send cancellation email. Route: `POST /api/v1/payments/{id}/refund`.
4. **Availabilities API branch filtering** — `GET /api/v1/availabilities?branchId={id}` is not yet implemented but required by the booking flow (each branch must show only its doctors' slots).
5. **Production Stripe keys** — current config has sandbox keys; swap `STRIPE_SECRET_KEY` / `STRIPE_PUBLISHABLE_KEY` in `config.php` before go-live.

## Key Conventions

- API field names are **camelCase** in JSON, **snake_case** in DB — mapping defined in `$api_resource`
- `preferred_language` values: `'en'` or `'ar'`
- Default currency: `AED`
- No patient portal — booking is guest-only (no patient login)
- Rate limiting is disabled (`rate_limiting = FALSE` in config)
- SCSS changes auto-compile via `npm start` (Gulp watcher)
- `openapi.yml` must be updated whenever API endpoints or schemas change
